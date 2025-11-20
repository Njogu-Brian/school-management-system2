<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

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

        $this->ensureCriticalPermissions();
    }

    protected function ensureCriticalPermissions(): void
    {
        if (!class_exists(Permission::class) || !Schema::hasTable('permissions')) {
            return;
        }

        $required = [
            'curriculum_designs.view',
            'curriculum_designs.view_own',
            'curriculum_designs.create',
            'curriculum_designs.edit',
            'curriculum_designs.delete',
        ];

        foreach ($required as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
