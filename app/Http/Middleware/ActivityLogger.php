<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
    * Log user activity for accountability/debugging.
    * Failures (e.g. DB disconnect) are logged but do not break the response.
    */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log authenticated user actions; skip OPTIONS and assets
        if (auth()->check() && !in_array($request->method(), ['OPTIONS'])) {
            $route = $request->route();
            $routeName = $route?->getName();

            // Avoid logging log viewers themselves to reduce noise
            $excludedRoutes = [
                'activity-logs.index',
                'system-logs.index',
                'system-logs.download',
                'system-logs.clear',
            ];

            if (!in_array($routeName, $excludedRoutes, true)) {
                try {
                    $input = collect($request->except(['password', 'password_confirmation', '_token']))->toArray();

                    ActivityLog::create([
                        'user_id'     => auth()->id(),
                        'action'      => strtolower($request->method()),
                        'model_type'  => null,
                        'model_id'    => null,
                        'description' => $routeName ?: $request->path(),
                        'old_values'  => null,
                        'new_values'  => $input,
                        'ip_address'  => $request->ip(),
                        'user_agent'  => $request->userAgent(),
                        'route'       => $routeName,
                        'method'      => $request->method(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Activity log write failed (request still succeeded)', [
                        'route' => $routeName,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $response;
    }
}

