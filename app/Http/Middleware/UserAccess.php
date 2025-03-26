<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class UserAccess
{
    public function handle($request, Closure $next, $role)
{
    if (Auth::check() && Auth::user()->hasRole($role)) {
        return $next($request);
    }

    return response(['You do not have permission to access for this page.']);
}

}
