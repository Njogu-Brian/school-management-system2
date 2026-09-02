<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! ($user->must_change_password ?? false)) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return $next($request);
        }

        return redirect()->route('password.change');
    }

    private function isExempt(Request $request): bool
    {
        if ($request->routeIs(
            'password.change',
            'password.change.update',
            'logout',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'password.reset.otp',
            'password.reset.otp.submit',
        )) {
            return true;
        }

        $path = ltrim($request->path(), '/');

        return str_starts_with($path, 'livewire/')
            || str_starts_with($path, 'webauthn/')
            || $path === 'logout';
    }
}
