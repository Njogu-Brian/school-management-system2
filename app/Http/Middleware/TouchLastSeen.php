<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bump users.last_seen_at at most once every 10 minutes for authenticated API traffic.
 */
class TouchLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if (! $user) {
            return $response;
        }

        try {
            $last = $user->last_seen_at;
            if ($last === null || $last->lt(now()->subMinutes(10))) {
                $user->forceFill(['last_seen_at' => now()])->saveQuietly();
            }
        } catch (\Throwable $e) {
            // Never break the request for telemetry.
        }

        return $response;
    }
}
