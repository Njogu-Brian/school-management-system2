<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sms' => [
        'api_url' => env('SMS_API_URL', 'https://smsportal.hostpinnacle.co.ke/SMSApi/send'),
        'api_key' => env('SMS_API_KEY'),
        'user_id' => env('SMS_USER_ID'),
        'password' => env('SMS_PASSWORD'),
        'sender_id' => env('SMS_SENDER_ID', 'ROYAL_KINGS'),
        'sender_id_finance' => env('SMS_SENDER_ID_FINANCE', 'RKS_FINANCE'),
    ],

    'mpesa' => [
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'passkey' => env('MPESA_PASSKEY'),
        'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),
        'webhook_secret' => env('MPESA_WEBHOOK_SECRET'),
    ],

    'stripe' => [
        'key' => env('PAYMENT_STRIPE_KEY'),
        'secret' => env('PAYMENT_STRIPE_SECRET'),
        'webhook_secret' => env('PAYMENT_STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'wasender' => [
        'base_url' => env('WASENDER_API_BASE', 'https://www.wasenderapi.com/api'),
        'api_key' => env('WASENDER_API_KEY'),
        // PAT is required for account-level calls such as creating sessions
        'personal_access_token' => env('WASENDER_PERSONAL_ACCESS_TOKEN'),
        'webhook_token' => env('WASENDER_WEBHOOK_TOKEN'),
    ],

];
