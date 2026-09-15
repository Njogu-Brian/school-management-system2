<?php

namespace App\Http\Middleware;

use App\Support\ParentWebPortalGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectParentToMobileApp
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->mustUseMobileApp()) {
            return ParentWebPortalGate::reject($user);
        }

        return $next($request);
    }

    private function isExempt(Request $request): bool
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return true;
        }

        return $request->routeIs(
            'login',
            'logout',
            'privacy',
            'terms',
            'app.play-store',
            'app.apk',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'password.reset.otp',
            'password.reset.otp.submit',
            'webauthn.login.options',
            'webauthn.login',
        );
    }
}
