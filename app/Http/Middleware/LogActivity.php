<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActivityLog;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log login/logout
        if ($request->routeIs('login') || $request->routeIs('logout')) {
            $action = $request->routeIs('login') ? 'login' : 'logout';
            ActivityLog::log($action, null, "User {$action}");
        }

        return $response;
    }
}
