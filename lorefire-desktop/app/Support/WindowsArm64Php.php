<?php

namespace App\Support;

/**
 * NativePHP php-bin ships Windows x64 PHP only. On Windows ARM64,
 * `native:serve` must launch the system ARM64 PHP (winget PHP.PHP.8.4)
 * instead of unzipping a missing win/arm64 zip or falling back to x64
 * php-bin under Prism.
 */
class WindowsArm64Php
{
    public static function osIsWindowsArm64(
        ?string $processorArchitecture = null,
        ?string $processorArchitectureW6432 = null,
        ?string $osFamily = null
    ): bool {
        $osFamily ??= PHP_OS_FAMILY;
        if ($osFamily !== 'Windows') {
            return false;
        }

        $arch = strtoupper($processorArchitecture ?? (string) getenv('PROCESSOR_ARCHITECTURE'));
        $wow = strtoupper($processorArchitectureW6432 ?? (string) getenv('PROCESSOR_ARCHITEW6432'));

        return str_contains($arch, 'ARM') || str_contains($wow, 'ARM');
    }

    public static function phpIsArm64(?string $machine = null): bool
    {
        $machine = strtolower($machine ?? php_uname('m'));

        return str_contains($machine, 'arm') || str_contains($machine, 'aarch');
    }

    public static function shouldUseSystemPhpForServe(): bool
    {
        return self::osIsWindowsArm64() && self::phpIsArm64();
    }

    public static function executable(): ?string
    {
        $configured = getenv('NATIVEPHP_PHP_EXECUTABLE') ?: ($_ENV['NATIVEPHP_PHP_EXECUTABLE'] ?? null);
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        if (! self::shouldUseSystemPhpForServe()) {
            return null;
        }

        return PHP_BINARY;
    }

    /**
     * @return array<string, string>
     */
    public static function serveEnvironment(): array
    {
        $executable = self::executable();
        if ($executable === null) {
            return [];
        }

        return ['NATIVEPHP_PHP_EXECUTABLE' => $executable];
    }

    public static function applyToEnvironment(): void
    {
        foreach (self::serveEnvironment() as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
        }
    }
}
