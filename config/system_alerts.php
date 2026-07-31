<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Alert Escalation Channels
    |--------------------------------------------------------------------------
    |
    | Web (database) notifications and mobile push are always delivered.
    | Email and SMS escalation are opt-in because they consume SMS credits
    | and flood admin inboxes when a fault repeats.
    |
    */

    'escalation' => [
        'email' => env('SYSTEM_ALERT_ESCALATION_EMAIL', false),
        'sms' => env('SYSTEM_ALERT_ESCALATION_SMS', false),
    ],

];
