<?php

return [
    /*
    | BioTime 9.5 lives on the school LAN. The ERP (AWS) usually cannot reach it.
    | Preferred link: the Windows BioTime PC pushes punches to
    | POST /api/integrations/biotime/punches using ingest_token.
    |
    | Optional pull: set base_url if BioTime is reachable from this server
    | (port-forward locked to this server's IP). Then `php artisan biotime:sync --pull`.
    */
    'base_url' => rtrim((string) env('BIOTIME_BASE_URL', ''), '/'),
    'username' => env('BIOTIME_USERNAME', 'admin'),
    'password' => env('BIOTIME_PASSWORD', ''),
    'timeout' => (int) env('BIOTIME_TIMEOUT', 25),

    /** Shared secret for the office-PC push script. Header: X-BioTime-Token */
    'ingest_token' => env('BIOTIME_INGEST_TOKEN', ''),

    /** Keep GPS clock-in API off now that gates are the source of truth. */
    'gps_clock_enabled' => filter_var(env('STAFF_GPS_CLOCK_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
];
