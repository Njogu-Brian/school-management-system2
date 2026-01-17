<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use App\Models\OptionalFee;
use App\Observers\OptionalFeeObserver;

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

        // Global activity logging for web routes (accountability & auditing)
        $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\ActivityLogger::class);

        // Register OptionalFee observer for automatic wallet crediting/debiting
        OptionalFee::observe(OptionalFeeObserver::class);
    }

    protected function ensureCriticalPermissions(): void
    {
        try {
            if (!class_exists(Permission::class) || !Schema::hasTable('permissions')) {
                return;
            }

            // Check if permissions table has the correct structure (guard_name column)
            // Skip if table has old custom structure (module/feature columns)
            if (Schema::hasColumn('permissions', 'module') || !Schema::hasColumn('permissions', 'guard_name')) {
                return; // Skip until table structure is fixed by migrations
            }

            // Verify Spatie permission tables exist before trying to use them
            if (!Schema::hasTable('role_has_permissions') || !Schema::hasTable('model_has_permissions')) {
                return; // Skip until Spatie tables are created
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
        } catch (\Exception $e) {
            // Silently fail if permissions can't be created yet
            // This can happen during migrations or if tables aren't ready
        }
    }
}
