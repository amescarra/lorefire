<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Services\PythonSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PythonSetupServiceTest extends TestCase
{
    use RefreshDatabase;

    private PythonSetupService $service;

    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PythonSetupService::class);
        $this->logPath = $this->service->setupLogPath();
        if (! is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0755, true);
        }
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
        parent::tearDown();
    }

    public function test_missing_setup_script_marks_failed_with_error(): void
    {
        $service = new class extends PythonSetupService
        {
            public function setupScriptPath(): string
            {
                return '/tmp/lorefire-missing-setup-script.sh';
            }
        };

        $service->runSetup();

        $this->assertSame(PythonSetupService::STATUS_FAILED, AppSetting::get(PythonSetupService::SETTING_STATUS));
        $this->assertStringContainsString('Setup script not found', (string) AppSetting::get(PythonSetupService::SETTING_ERROR));
    }

    public function test_async_missing_script_does_not_stay_running(): void
    {
        $service = new class extends PythonSetupService
        {
            public function setupScriptPath(): string
            {
                return '/tmp/lorefire-missing-setup-script.sh';
            }
        };

        $service->runSetupAsync();

        $this->assertSame(PythonSetupService::STATUS_FAILED, AppSetting::get(PythonSetupService::SETTING_STATUS));
        $this->assertNotSame(PythonSetupService::STATUS_RUNNING, $service->getStatus());
    }

    public function test_watchdog_fails_legacy_running_with_no_log(): void
    {
        AppSetting::set(PythonSetupService::SETTING_STATUS, PythonSetupService::STATUS_RUNNING);
        AppSetting::set(PythonSetupService::SETTING_ERROR, '');

        $this->assertSame(PythonSetupService::STATUS_FAILED, $this->service->getStatus());
        $this->assertStringContainsString('no progress', (string) $this->service->getLastError());
    }

    public function test_watchdog_fails_when_hard_timeout_elapsed(): void
    {
        AppSetting::set(PythonSetupService::SETTING_STATUS, PythonSetupService::STATUS_RUNNING);
        AppSetting::set(PythonSetupService::SETTING_STARTED_AT, time() - PythonSetupService::SETUP_TIMEOUT_SECONDS - 10, 'integer');
        file_put_contents($this->logPath, "==> Installing WhisperX and dependencies...\npip still going?\n");

        $this->assertSame(PythonSetupService::STATUS_FAILED, $this->service->getStatus());
        $this->assertStringContainsString('timed out', (string) $this->service->getLastError());
        $this->assertStringContainsString('Installing WhisperX', (string) $this->service->getLastError());
    }

    public function test_watchdog_fails_when_log_stops_growing(): void
    {
        AppSetting::set(PythonSetupService::SETTING_STATUS, PythonSetupService::STATUS_RUNNING);
        AppSetting::set(
            PythonSetupService::SETTING_STARTED_AT,
            time() - PythonSetupService::STALE_LOG_SECONDS - 30,
            'integer'
        );
        file_put_contents($this->logPath, "==> Installing WhisperX and dependencies...\n");
        touch($this->logPath, time() - PythonSetupService::STALE_LOG_SECONDS - 30);

        $this->assertSame(PythonSetupService::STATUS_FAILED, $this->service->getStatus());
        $this->assertStringContainsString('stopped writing progress', (string) $this->service->getLastError());
    }

    public function test_watchdog_keeps_recent_running_job(): void
    {
        AppSetting::set(PythonSetupService::SETTING_STATUS, PythonSetupService::STATUS_RUNNING);
        AppSetting::set(PythonSetupService::SETTING_STARTED_AT, time() - 20, 'integer');
        file_put_contents($this->logPath, "==> Installing WhisperX and dependencies...\nCollecting whisperx\n");
        touch($this->logPath, time());

        $this->assertSame(PythonSetupService::STATUS_RUNNING, $this->service->getStatus());
        $this->assertNull($this->service->getLastError());
    }

    public function test_get_setup_log_returns_tail(): void
    {
        file_put_contents($this->logPath, str_repeat('A', 100)."TAIL_MARKER\n");

        $this->assertStringContainsString('TAIL_MARKER', $this->service->getSetupLog(50));
        unlink($this->logPath);
        $this->assertSame('', $this->service->getSetupLog());
    }

    public function test_fail_setup_includes_log_and_stops_running(): void
    {
        AppSetting::set(PythonSetupService::SETTING_STATUS, PythonSetupService::STATUS_RUNNING);
        file_put_contents($this->logPath, "ERROR: Could not find a version that satisfies the requirement torch\n");

        $this->service->failSetup('pip install failed');

        $this->assertSame(PythonSetupService::STATUS_FAILED, AppSetting::get(PythonSetupService::SETTING_STATUS));
        $error = (string) $this->service->getLastError();
        $this->assertStringContainsString('pip install failed', $error);
        $this->assertStringContainsString('Could not find a version', $error);
    }

    public function test_windows_async_command_merges_stderr_into_the_log(): void
    {
        $cmd = $this->service->windowsAsyncCommand(
            'C:\\Program Files\\php\\php.exe',
            'C:\\Users\\DM\\Lorefire\\artisan',
            'C:\\Users\\DM\\Lorefire\\storage\\logs\\python_setup.log',
            false
        );

        $this->assertStringContainsString('cmd /c start /b', $cmd);
        $this->assertStringContainsString('2>&1', $cmd);
        $this->assertStringContainsString('python:setup', $cmd);
        $this->assertStringNotContainsString('RedirectStandardOutput', $cmd);
        $this->assertStringContainsString('"C:\\Program Files\\php\\php.exe"', $cmd);
    }

    public function test_windows_gpu_flag_is_appended(): void
    {
        $cmd = $this->service->windowsAsyncCommand('php.exe', 'artisan', 'setup.log', true);

        $this->assertStringContainsString('--gpu', $cmd);
    }

    public function test_shared_payload_includes_log_and_reaps_stale_status(): void
    {
        AppSetting::set(PythonSetupService::SETTING_STATUS, PythonSetupService::STATUS_RUNNING);
        AppSetting::set(PythonSetupService::SETTING_STARTED_AT, time() - PythonSetupService::SETUP_TIMEOUT_SECONDS - 5, 'integer');
        file_put_contents($this->logPath, "pip hang line\n");

        $payload = $this->service->sharedPayload();

        $this->assertSame(PythonSetupService::STATUS_FAILED, $payload['status']);
        $this->assertStringContainsString('pip hang line', $payload['log']);
        $this->assertNotEmpty($payload['error']);
    }

    public function test_onboarding_page_shares_setup_log(): void
    {
        AppSetting::set(PythonSetupService::SETTING_STATUS, PythonSetupService::STATUS_RUNNING);
        AppSetting::set(PythonSetupService::SETTING_STARTED_AT, time() - 15, 'integer');
        file_put_contents($this->logPath, "==> Installing WhisperX and dependencies...\n");

        $this->get('/onboarding')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Onboarding/Index')
                ->where('python_setup.status', 'running')
                ->where('python_setup.log', fn ($log) => is_string($log) && str_contains($log, 'Installing WhisperX'))
            );
    }

    public function test_setup_scripts_are_hang_resistant(): void
    {
        $ps1 = file_get_contents(base_path('resources/python/setup.ps1'));
        $this->assertIsString($ps1);
        $this->assertStringContainsString('PYTHONUNBUFFERED', $ps1);
        $this->assertStringContainsString('--prefer-binary', $ps1);
        $this->assertStringContainsString('--only-binary=:all:', $ps1);
        $this->assertStringContainsString('AllowFailure', $ps1);
        $this->assertStringContainsString('TIMEOUT', $ps1);
        $this->assertStringContainsString('Installing WhisperX and dependencies', $ps1);

        $sh = file_get_contents(base_path('resources/python/setup.sh'));
        $this->assertIsString($sh);
        $this->assertStringContainsString('PYTHONUNBUFFERED', $sh);
        $this->assertStringContainsString('--prefer-binary', $sh);
        $this->assertStringContainsString('model preload skipped', $sh);
    }
}
