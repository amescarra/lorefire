<?php

namespace Tests\Unit;

use App\Support\NativeSqliteMigrator;
use Tests\TestCase;

class NativeSqliteMigratorTest extends TestCase
{
    public function test_testing_env_does_not_migrate_nativephp_sqlite(): void
    {
        $this->assertTrue(app()->environment('testing'));
        $this->assertFalse(NativeSqliteMigrator::shouldMigrateFromArtisan());
    }

    public function test_native_sqlite_path_is_under_database(): void
    {
        $this->assertSame(database_path('nativephp.sqlite'), NativeSqliteMigrator::nativeSqlitePath());
        $this->assertStringNotContainsString('storage/app', NativeSqliteMigrator::nativeSqlitePath());
    }
}
