<?php

namespace App\Support;

/**
 * PowerShell 5.1 Start-Process often leaves ExitCode null after HasExited
 * unless WaitForExit + Refresh run first. setup.ps1 Get-TimedCommandExitCode
 * implements the same rules: never treat a null/empty code as failure.
 */
class WindowsTimedCommandExit
{
    public static function looksLikeSuccessOutput(?string $output): bool
    {
        if ($output === null || $output === '') {
            return false;
        }

        return (bool) preg_match(
            '/Successfully installed|Successfully uninstalled|Requirement already satisfied/i',
            $output
        );
    }

    /**
     * Normalize a Start-Process ExitCode after the child has exited.
     */
    public static function resolve(mixed $exitCode, ?string $output = null): int
    {
        if ($exitCode === null || $exitCode === '') {
            return 0;
        }

        return (int) $exitCode;
    }

    public static function failed(mixed $exitCode, ?string $output = null): bool
    {
        return self::resolve($exitCode, $output) !== 0;
    }
}
