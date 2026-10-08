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

        if ($user && $this->hasMatchingRole($user, $roles)) {
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
     * Role names in the database are not always title case (for example "admin").
     */
    private function hasMatchingRole($user, array $roles): bool
    {
        $wanted = [];
        foreach ($roles as $role) {
            foreach (explode('|', (string) $role) as $name) {
                $name = strtolower(trim($name));
                if ($name !== '') {
                    $wanted[] = $name;
                }
            }
        }

        if ($wanted === []) {
            return false;
        }

        return $user->roles->contains(
            fn ($role) => in_array(strtolower($role->name), $wanted, true)
        );
    }

    private function routeAllowsTeachingStaff(string $rolesString): bool
    {
        return str_contains($rolesString, 'Teacher')
            || str_contains($rolesString, 'teacher')
            || str_contains($rolesString, 'Senior Teacher')
            || str_contains($rolesString, 'Supervisor');
    }
}
