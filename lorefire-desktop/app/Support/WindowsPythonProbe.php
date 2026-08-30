<?php

namespace App\Support;

/**
 * Rules for rejecting Microsoft Store Python App Execution Aliases.
 * setup.ps1 implements the same checks before `python -m venv`.
 */
class WindowsPythonProbe
{
    public static function isStoreAlias(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        $normalized = str_replace('/', '\\', $path);

        return (bool) preg_match('/\\\\WindowsApps\\\\/i', $normalized);
    }

    public static function looksLikeStoreStubMessage(?string $output): bool
    {
        if ($output === null || $output === '') {
            return false;
        }

        return (bool) preg_match('/Python was not found|Microsoft Store/i', $output);
    }

    /**
     * @param  string|null  $versionOutput  stdout/stderr from `python -c "print(sys.version_info[:2])"`
     */
    public static function isUsableInterpreter(?string $path, ?string $versionOutput, ?int $exitCode): bool
    {
        if (self::isStoreAlias($path)) {
            return false;
        }

        if ($exitCode === 9009) {
            return false;
        }

        if ($exitCode !== null && $exitCode !== 0) {
            return false;
        }

        if (self::looksLikeStoreStubMessage($versionOutput)) {
            return false;
        }

        $version = trim((string) $versionOutput);

        return (bool) preg_match('/\(\s*3\s*,\s*(9|1[0-3])\s*\)/', $version);
    }
}
