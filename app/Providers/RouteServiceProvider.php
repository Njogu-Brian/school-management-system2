<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        $this->routes(function () {
            // Web
            Route::middleware('web')
                ->group(function () {
                    require base_path('routes/web.php');
                    require base_path('routes/teacher.php'); // web only
                });

            // API
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
