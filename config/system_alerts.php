<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Alert Escalation Channels
    |--------------------------------------------------------------------------
    |
    | Web (database) notifications and mobile push are always delivered.
    | Email and SMS escalation stay off until explicitly re-enabled in code.
    | Do not read these from the environment while they are paused.
    |
    */

    'escalation' => [
        'email' => false,
        'sms' => false,
    ],

];
