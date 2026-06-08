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
            'long_pdf_export' => \App\Http\Middleware\AllowLongRunningPdfExport::class,

            // Spatie Laravel-Permission middleware
            'role' => \App\Http\Middleware\DirectorRoleMiddleware::class,
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

            // Passkeys (WebAuthn) - browser posts JSON without a CSRF token
            'webauthn/*',
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (\Throwable $e) {
            if (app()->runningInConsole() || app()->runningUnitTests()) {
                return;
            }

            if (! app()->environment('production', 'staging')) {
                return;
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() < 500) {
                return;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return;
            }

            if (! \App\Services\SystemAlertService::shouldReportException($e)) {
                return;
            }

            try {
                app(\App\Services\SystemAlertService::class)->raiseProcessingError(
                    title: 'Unhandled application error',
                    message: class_basename($e).': '.$e->getMessage(),
                    category: 'system',
                    fingerprint: \App\Services\SystemAlertService::fingerprintForException($e),
                    deepLink: '/system-logs',
                    context: [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ],
                );
            } catch (\Throwable $alertError) {
                \Illuminate\Support\Facades\Log::warning('Failed to raise exception system alert', [
                    'error' => $alertError->getMessage(),
                ]);
            }
        });
    })->create();
