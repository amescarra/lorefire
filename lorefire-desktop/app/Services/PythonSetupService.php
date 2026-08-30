<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * PythonSetupService
 *
 * Manages the lifecycle of the bundled Python venv used for WhisperX.
 *
 * Status values stored in AppSetting under 'python_setup_status':
 *   'not_started'  — fresh install, setup has never run
 *   'running'      — setup is currently executing (async)
 *   'ready'        — venv exists and whisperx import succeeds
 *   'failed'       — setup exited non-zero, timed out, stalled, or verification failed
 *
 * Windows first-run used to sit on `running` forever (stdout-only spawn +
 * unbounded pip). Status is now reaped if the job exceeds the hard cap or
 * the setup log stops growing.
 */
class PythonSetupService
{
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_RUNNING     = 'running';
    public const STATUS_READY       = 'ready';
    public const STATUS_FAILED      = 'failed';

    public const SETTING_STATUS     = 'python_setup_status';
    public const SETTING_ERROR      = 'python_setup_error';
    public const SETTING_STARTED_AT = 'python_setup_started_at';

    /** Hard cap for a single setup attempt (PHP process + watchdog). */
    public const SETUP_TIMEOUT_SECONDS = 1800;

    /** If the log file has not grown for this long, treat the job as stuck. */
    public const STALE_LOG_SECONDS = 600;

    /** After spawn, a missing log means the background process never started. */
    public const SPAWN_GRACE_SECONDS = 90;

    /**
     * Sanitize a string to valid UTF-8, stripping any bytes that would
     * cause JSON encoding to fail (e.g. pip progress bars, terminal escapes).
     */
    public function sanitize(string $text): string
    {
        $text = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $text ?? '');
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return mb_substr($text, -8000);
    }

    public function getStatus(): string
    {
        $this->reapIfStale();

        return AppSetting::get(self::SETTING_STATUS, self::STATUS_NOT_STARTED);
    }

    public function getLastError(): ?string
    {
        return $this->nullableError();
    }

    private function nullableError(): ?string
    {
        $error = AppSetting::get(self::SETTING_ERROR);
        if ($error === null || $error === '') {
            return null;
        }

        return $this->sanitize((string) $error);
    }

    public function isReady(): bool
    {
        return $this->getStatus() === self::STATUS_READY;
    }

    /**
     * Payload shared with Inertia. Triggers the watchdog so polling cannot
     * leave the UI on `running` indefinitely.
     *
     * @return array{status: string, error: ?string, log: string, started_at: ?int, onboarding_complete: bool}
     */
    public function sharedPayload(): array
    {
        $this->reapIfStale();

        return [
            'status'              => AppSetting::get(self::SETTING_STATUS, self::STATUS_NOT_STARTED),
            'error'               => $this->nullableError(),
            'log'                 => $this->sanitize($this->getSetupLog(8000)),
            'started_at'          => AppSetting::get(self::SETTING_STARTED_AT) ? (int) AppSetting::get(self::SETTING_STARTED_AT) : null,
            'onboarding_complete' => (bool) AppSetting::get('onboarding_complete', false),
        ];
    }

    /**
     * Called on every app boot. Returns immediately in the common case so the
     * main window opens without delay.
     *
     * Fast-path rules (no blocking verify):
     *   - status=ready  + venv binary exists  → trust the DB, done
     *   - status=running (and not stale)      → setup already in progress, done
     *
     * Slow-path (async setup kicked off):
     *   - venv binary missing, or status is not_started / failed
     */
    public function bootCheck(): void
    {
        $this->reapIfStale();

        $status = AppSetting::get(self::SETTING_STATUS, self::STATUS_NOT_STARTED);

        if ($status === self::STATUS_RUNNING) {
            return;
        }

        if ($status === self::STATUS_READY && $this->venvPythonExists()) {
            return;
        }

        $this->runSetupAsync();
    }

    /**
     * Mark a stuck `running` job as `failed` with the setup log tail.
     * Safe to call on every status poll.
     */
    public function reapIfStale(): void
    {
        $status = AppSetting::get(self::SETTING_STATUS, self::STATUS_NOT_STARTED);
        if ($status !== self::STATUS_RUNNING) {
            return;
        }

        $now = time();
        $startedAt = (int) AppSetting::get(self::SETTING_STARTED_AT, 0);
        $logPath = $this->setupLogPath();
        $logExists = file_exists($logPath);
        $logMtime = $logExists ? (int) filemtime($logPath) : 0;

        if ($startedAt <= 0) {
            if ($logExists && ($now - $logMtime) <= self::STALE_LOG_SECONDS) {
                AppSetting::set(self::SETTING_STARTED_AT, $logMtime, 'integer');
                $startedAt = $logMtime;
            } else {
                $this->failSetup(
                    'Setup was left in a running state with no progress. Retry install from Settings. The previous attempt never wrote a live log (common on Windows when stderr was discarded).'
                );

                return;
            }
        }

        $elapsed = $now - $startedAt;

        if ($elapsed > self::SETUP_TIMEOUT_SECONDS) {
            $this->failSetup(
                'WhisperX setup timed out after '.(int) (self::SETUP_TIMEOUT_SECONDS / 60).' minutes. Pip may be stuck downloading or compiling. Retry from Settings; CPU wheels are used by default (GPU torch is optional).'
            );

            return;
        }

        if (! $logExists && $elapsed > self::SPAWN_GRACE_SECONDS) {
            $this->failSetup(
                'Setup process did not start within '.self::SPAWN_GRACE_SECONDS.' seconds. Lorefire could not launch `php artisan python:setup` (missing PHP binary, quoting, or PATH).'
            );

            return;
        }

        if ($logExists && $elapsed > self::STALE_LOG_SECONDS && ($now - $logMtime) > self::STALE_LOG_SECONDS) {
            $this->failSetup(
                'Setup stopped writing progress for '.(int) (self::STALE_LOG_SECONDS / 60).' minutes (stuck at pip or model download). Last log is included below.'
            );
        }
    }

    /**
     * Run the platform setup script (blocking). Used by `php artisan python:setup`.
     */
    public function runSetup(bool $gpu = false): void
    {
        $this->markRunning();
        $this->beginLog('python:setup start'.($gpu ? ' (GPU)' : ' (CPU)'));

        $setupScript = $this->setupScriptPath();
        $logPath = $this->setupLogPath();

        if (! file_exists($setupScript)) {
            $this->failSetup("Setup script not found at: {$setupScript}");

            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $args = ['powershell', '-ExecutionPolicy', 'Bypass', '-File', $setupScript];
            if ($gpu) {
                $args[] = '-Gpu';
            }
        } else {
            $args = ['/bin/bash', $setupScript];
            if ($gpu) {
                $args[] = '--gpu';
            }
        }

        $process = new Process($args);
        $process->setTimeout(self::SETUP_TIMEOUT_SECONDS);
        $process->setIdleTimeout(self::STALE_LOG_SECONDS);

        try {
            $process->run(function (string $type, string $buffer) use ($logPath) {
                file_put_contents($logPath, $buffer, FILE_APPEND | LOCK_EX);
            });

            if ($process->isSuccessful()) {
                if ($this->verifyVenv()) {
                    AppSetting::set(self::SETTING_STATUS, self::STATUS_READY);
                    AppSetting::set(self::SETTING_ERROR, '');
                    $this->appendLog('Setup complete — whisperx import OK.');
                } else {
                    $this->failSetup('Setup completed but whisperx could not be imported. Check '.$logPath);
                }
            } else {
                $stderr = $this->sanitize($process->getErrorOutput());
                $this->failSetup($stderr !== '' ? $stderr : 'Setup script exited with code '.$process->getExitCode());
            }
        } catch (ProcessTimedOutException $e) {
            $this->failSetup(
                'WhisperX setup timed out ('.self::SETUP_TIMEOUT_SECONDS.'s) with no completion. '.$e->getMessage()
            );
        } catch (\Throwable $e) {
            $this->failSetup($this->sanitize($e->getMessage()));
            Log::error('[PythonSetup] exception', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Dispatch setup as a background process so boot() returns immediately.
     */
    public function runSetupAsync(bool $gpu = false): void
    {
        $this->markRunning();
        $this->beginLog('python:setup async spawn'.($gpu ? ' (GPU)' : ' (CPU)'));

        $setupScript = $this->setupScriptPath();
        if (! file_exists($setupScript)) {
            $this->failSetup("Setup script not found at: {$setupScript}");

            return;
        }

        $cmd = $this->buildAsyncCommand($gpu);
        $this->appendLog('spawn: '.$cmd);

        try {
            $output = shell_exec($cmd);
            if (is_string($output) && trim($output) !== '') {
                $this->appendLog(trim($output));
            }
        } catch (\Throwable $e) {
            $this->failSetup('Failed to launch setup process: '.$e->getMessage());
        }
    }

    /**
     * Build the detached shell command used by runSetupAsync.
     * Windows merges stderr into the same log (`>> log 2>&1`); Start-Process
     * cannot do that with a single RedirectStandardOutput file.
     */
    public function buildAsyncCommand(bool $gpu = false, ?string $osFamily = null): string
    {
        $osFamily ??= PHP_OS_FAMILY;
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $logPath = $this->setupLogPath();
        $gpuFlag = $gpu ? '--gpu' : '';

        if ($osFamily === 'Windows') {
            return $this->windowsAsyncCommand($php, $artisan, $logPath, $gpu);
        }

        return sprintf(
            '%s %s python:setup %s >> %s 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($artisan),
            $gpuFlag,
            escapeshellarg($logPath)
        );
    }

    /**
     * Windows: `cmd /c start /b` detaches without blocking the UI, and
     * `>> log 2>&1` captures pip progress that otherwise goes to stderr.
     */
    public function windowsAsyncCommand(string $php, string $artisan, string $logPath, bool $gpu = false): string
    {
        $phpQ = $this->winQuote($php);
        $artisanQ = $this->winQuote($artisan);
        $logQ = $this->winQuote($logPath);
        $gpuArg = $gpu ? ' --gpu' : '';

        return 'cmd /c start /b "" '.$phpQ.' '.$artisanQ.' python:setup'.$gpuArg.' >> '.$logQ.' 2>&1';
    }

    public function winQuote(string $path): string
    {
        return '"'.str_replace('"', '""', $path).'"';
    }

    /**
     * Verify the venv is healthy by importing whisperx.
     */
    public function verifyVenv(): bool
    {
        $python = $this->venvPythonPath();
        if (! file_exists($python)) {
            return false;
        }

        $process = new Process([$python, '-c', 'import whisperx']);
        $process->setTimeout(30);
        $process->run();

        return $process->isSuccessful();
    }

    public function venvPythonExists(): bool
    {
        return file_exists($this->venvPythonPath());
    }

    public function venvPythonPath(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return base_path(implode(DIRECTORY_SEPARATOR, ['resources', 'python', 'venv', 'Scripts', 'python.exe']));
        }

        return base_path('resources/python/venv/bin/python');
    }

    public function setupScriptPath(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return base_path(implode(DIRECTORY_SEPARATOR, ['resources', 'python', 'setup.ps1']));
        }

        return base_path('resources/python/setup.sh');
    }

    public function setupLogPath(): string
    {
        return storage_path('logs/python_setup.log');
    }

    public function getSetupLog(int $lastBytes = 4096): string
    {
        $path = $this->setupLogPath();
        if (! file_exists($path)) {
            return '';
        }
        $size = filesize($path);
        if ($size === 0) {
            return '';
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return '';
        }
        fseek($handle, max(0, $size - $lastBytes));
        $content = fread($handle, $lastBytes) ?: '';
        fclose($handle);

        return $content;
    }

    private function markRunning(): void
    {
        AppSetting::set(self::SETTING_STATUS, self::STATUS_RUNNING);
        AppSetting::set(self::SETTING_ERROR, '');
        AppSetting::set(self::SETTING_STARTED_AT, time(), 'integer');
    }

    public function failSetup(string $message): void
    {
        $tail = trim($this->getSetupLog(2000));
        $full = $message;
        if ($tail !== '') {
            $full .= "\n\n--- setup log ---\n".$tail;
        }

        $sanitized = $this->sanitize($full);
        AppSetting::set(self::SETTING_STATUS, self::STATUS_FAILED);
        AppSetting::set(self::SETTING_ERROR, $sanitized);
        $this->appendLog('FAILED: '.$message);
        Log::error('[PythonSetup] setup failed', ['error' => $message]);
    }

    private function beginLog(string $message): void
    {
        $path = $this->setupLogPath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (file_exists($path) && filesize($path) > 512 * 1024) {
            file_put_contents($path, '');
        }
        $this->appendLog('===== '.$message.' =====');
    }

    private function appendLog(string $line): void
    {
        $path = $this->setupLogPath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, '['.date('c').'] '.$line."\n", FILE_APPEND | LOCK_EX);
    }
}
