<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bulk invoice PDFs can take minutes (Dompdf + hundreds of pages). Default max_execution_time is often 30s.
 * Runs before the controller so limits apply for the whole request.
 *
 * If this still times out, raise PHP-FPM request_terminate_timeout / nginx fastcgi_read_timeout,
 * or php_admin_value[max_execution_time] may be overriding ini (pool config).
 */
class AllowLongRunningPdfExport
{
    public function handle(Request $request, Closure $next): Response
    {
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        return $next($request);
    }
}
