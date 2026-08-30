<?php

namespace App\Providers;

use App\Support\WindowsArm64Php;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (PHP_SAPI === 'cli') {
            WindowsArm64Php::applyToEnvironment();
        }
    }
}
