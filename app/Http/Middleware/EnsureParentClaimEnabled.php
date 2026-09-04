<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns off first-time "Claim access" without a new mobile app.
 * The existing app still shows the button; the API returns this message instead of sending OTP.
 */
class EnsureParentClaimEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.parent_claim_enabled')) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Claim access is not available. Sign in with your phone number or email and the password your school sent (your child\'s admission number and year, for example RKS001-2026).',
        ], 403);
    }
}
