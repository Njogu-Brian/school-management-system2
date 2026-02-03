<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;

class DirectorRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if ($user && $user->hasRole('Director')) {
            return $next($request);
        }

        return app(RoleMiddleware::class)->handle($request, $next, ...$roles);
    }
}
