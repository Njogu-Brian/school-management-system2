<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Avoid "Allowed memory size exhausted" in production (increase if you still hit limits)
if (ini_get('memory_limit') !== '-1') {
    $limit = (int) ini_get('memory_limit');
    if ($limit > 0 && $limit < 256) {
        @ini_set('memory_limit', '256M');
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
