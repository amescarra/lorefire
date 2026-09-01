<?php

namespace App\Support;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * NativePHP stores the live DB at database/nativephp.sqlite.
 * Plain `php artisan migrate` uses database/database.sqlite unless DB_DATABASE is set,
 * so pending columns never reach the app the desktop actually opens.
 */
class NativeSqliteMigrator
{
    public static function nativeSqlitePath(): string
    {
        return database_path('nativephp.sqlite');
    }

    public static function shouldMigrateFromArtisan(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return is_file(self::nativeSqlitePath());
    }

    public static function afterMigrateCommand(CommandFinished $event): void
    {
        if (! self::shouldMigrateFromArtisan()) {
            return;
        }

        $command = (string) $event->command;
        if (! in_array($command, ['migrate', 'migrate:fresh', 'migrate:refresh'], true)) {
            return;
        }

        if ($event->exitCode !== null && (int) $event->exitCode !== 0) {
            return;
        }

        self::migrateForce();
    }

    public static function migrateForce(): int
    {
        try {
            return Artisan::call('native:migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            Log::warning('[NativeSqliteMigrator] native:migrate failed: '.$e->getMessage());

            return 1;
        }
    }
}
