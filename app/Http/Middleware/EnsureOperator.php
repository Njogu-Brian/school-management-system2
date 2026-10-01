<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $allowed = config('edulynk.operator_emails', []);
        $isOperator = in_array(strtolower((string) $user->email), array_map('strtolower', $allowed), true)
            || $user->hasRole('Super Admin');

        if (! $isOperator) {
            abort(403, 'Operator access only.');
        }

        return $next($request);
    }
}
