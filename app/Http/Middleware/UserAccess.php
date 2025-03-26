<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class UserAccess
{
    public function handle($request, Closure $next, $role)
    {
        // ✅ Check if the authenticated user has the right role
        if (Auth::check() && Auth::user()->role == $role) {
            return $next($request);
        }

        // ❌ Return error if unauthorized
        return response(['You do not have permission to access for this page.']);
    }
}
