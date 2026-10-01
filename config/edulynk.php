<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application role
    |--------------------------------------------------------------------------
    |
    | control_plane — operator portal, schools resolve API, billing (edulynk_control)
    | tenant        — full school ERP (default). Uses path/slug → per-school MySQL.
    |
    */
    'app_role' => env('APP_ROLE', 'tenant'),

    'is_control_plane' => env('APP_ROLE', 'tenant') === 'control_plane',

    /*
    |--------------------------------------------------------------------------
    | Public base URL for tenant ERP paths (no trailing slash)
    |--------------------------------------------------------------------------
    */
    'tenant_base_url' => rtrim(env('EDULYNK_TENANT_BASE_URL', env('APP_URL', 'https://edulynk.co.ke')), '/'),

    /*
    |--------------------------------------------------------------------------
    | Path segments that are NOT school slugs (when ResolveTenantFromPath runs)
    |--------------------------------------------------------------------------
    */
    'reserved_path_segments' => [
        'api', 'operator', 'login', 'logout', 'password', 'up', 'storage', 'build',
        'css', 'js', 'vendor', 'webhooks', 'pay', 'receipt', 'invoice', 'statement',
        'family-update', 'family', 'media', 'app', 'privacy', 'terms', 'home',
        'auth', 'sanctum', 'livewire',
    ],

    /*
    |--------------------------------------------------------------------------
    | cPanel UAPI (MySQL provisioning)
    |--------------------------------------------------------------------------
    */
    'cpanel' => [
        'host' => env('CPANEL_HOST', ''),
        'user' => env('CPANEL_USER', ''),
        'api_token' => env('CPANEL_API_TOKEN', ''),
        'port' => (int) env('CPANEL_PORT', 2083),
        /** Account DB prefix, e.g. "edulynk_" → creates edulynk_demo */
        'db_prefix' => env('CPANEL_DB_PREFIX', ''),
        /** Absolute path to public_html for wiring /{slug} (optional) */
        'public_html' => env('CPANEL_PUBLIC_HTML', ''),
        /** Absolute path to shared ERP app root on the server */
        'erp_root' => env('CPANEL_ERP_ROOT', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo school defaults (first tenant)
    |--------------------------------------------------------------------------
    */
    'demo' => [
        'code' => 'DEMO001',
        'slug' => 'demo',
        'name' => 'EduLynk Demo Academy',
        'dump_relative' => 'backups/demo_school_20260930.sql.gz',
        'admin_email' => 'admin@demo.school',
        'admin_password' => env('DEMO_SCHOOL_ADMIN_PASSWORD', 'Demo@12345'),
        'monthly_fee' => (float) env('DEMO_SCHOOL_MONTHLY_FEE', 0),
    ],

    'default_monthly_fee' => (float) env('EDULYNK_DEFAULT_MONTHLY_FEE', 5000),

    'operator_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('EDULYNK_OPERATOR_EMAILS', 'operator@edulynk.co.ke'))
    ))),
];
