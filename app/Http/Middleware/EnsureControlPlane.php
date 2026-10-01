<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureControlPlane
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('edulynk.is_control_plane')) {
            abort(404);
        }

        return $next($request);
    }
}
