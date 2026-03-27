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

        $rolesString = implode('|', $roles);
        if ($user && $this->routeAllowsTeachingStaff($rolesString)
            && ($user->hasTeacherLikeRole() || $user->hasTeachingAssignments())) {
            return $next($request);
        }

        return app(RoleMiddleware::class)->handle($request, $next, ...$roles);
    }

    /**
     * When a route lists Teacher/Senior Teacher etc., allow users with teaching assignments
     * even if Spatie role names from HR do not match exactly.
     */
    private function routeAllowsTeachingStaff(string $rolesString): bool
    {
        return str_contains($rolesString, 'Teacher')
            || str_contains($rolesString, 'teacher')
            || str_contains($rolesString, 'Senior Teacher')
            || str_contains($rolesString, 'Supervisor');
    }
}
