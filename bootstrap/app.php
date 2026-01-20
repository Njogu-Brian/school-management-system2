<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'user-access' => \App\Http\Middleware\UserAccess::class,
            'log.activity' => \App\Http\Middleware\LogActivity::class,

            // Spatie Laravel-Permission middleware
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Exempt M-PESA webhook endpoints from CSRF verification
        // M-PESA sends webhooks from external servers without CSRF tokens
        $middleware->validateCsrfTokens(except: [
            'webhooks/payment/mpesa',
            'webhooks/payment/mpesa/c2b',
            'webhooks/payment/c2b', // Alternative route without "mpesa" in path (Safaricom requirement)
            'webhooks/payment/mpesa/timeout',
            'webhooks/payment/mpesa/result',
            'webhooks/payment/mpesa/queue-timeout',
            'webhooks/payment/mpesa/validation',
            'webhooks/payment/mpesa/confirmation',
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
