<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\CommunicationService::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrap(); // ✅ ensures Laravel pagination matches Bootstrap styling
    }
}
