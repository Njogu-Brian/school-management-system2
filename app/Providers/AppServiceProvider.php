<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade; // <-- add this import

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\CommunicationService::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        // Register the @canAccess directive here (helpers.php no longer touches Blade)
        Blade::if('canAccess', function ($a, $b = null, $c = null) {
            return \can_access($a, $b, $c);
        });
    }
}
