<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo quick login (EduLynk sandbox only)
    |--------------------------------------------------------------------------
    |
    | When enabled, /login shows a demo-specific screen with one-click role
    | buttons. Keep this false on Royal Kings and any live customer site.
    |
    */
    'quick_login_enabled' => (bool) env('DEMO_QUICK_LOGIN', false),

    /*
    | Preferred accounts for each persona. If missing, the first active user
    | with one of the listed Spatie roles is used.
    */
    'personas' => [
        'super_admin' => [
            'label' => 'Super Admin',
            'email' => env('DEMO_LOGIN_SUPER_ADMIN', 'admin@demo.school'),
            'roles' => ['Super Admin'],
            'color' => '#1e3a5f',
        ],
        'director' => [
            'label' => 'Director',
            'email' => env('DEMO_LOGIN_DIRECTOR'),
            'roles' => ['Director', 'admin', 'Admin'],
            'color' => '#0ea5e9',
        ],
        'teacher' => [
            'label' => 'Teacher',
            'email' => env('DEMO_LOGIN_TEACHER'),
            'roles' => ['teacher', 'Teacher'],
            'color' => '#64748b',
        ],
        'accountant' => [
            'label' => 'Accountant',
            'email' => env('DEMO_LOGIN_ACCOUNTANT'),
            'roles' => ['Accountant', 'Finance Officer'],
            'color' => '#475569',
        ],
        'senior_teacher' => [
            'label' => 'Senior Teacher',
            'email' => env('DEMO_LOGIN_SENIOR_TEACHER'),
            'roles' => ['Senior Teacher'],
            'color' => '#db2777',
        ],
        'academic_admin' => [
            'label' => 'Academic Admin',
            'email' => env('DEMO_LOGIN_ACADEMIC_ADMIN'),
            'roles' => ['Academic Administrator', 'Secretary'],
            'color' => '#16a34a',
        ],
    ],

];
