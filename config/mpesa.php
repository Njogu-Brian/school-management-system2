<?php

return [
    /*
    |--------------------------------------------------------------------------
    | M-PESA Environment
    |--------------------------------------------------------------------------
    |
    | This determines which M-PESA environment to use: 'sandbox' or 'production'
    |
    */
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | M-PESA Credentials
    |--------------------------------------------------------------------------
    |
    | Your Safaricom Daraja API credentials from the Daraja portal
    |
    */
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode' => env('MPESA_SHORTCODE'),
    'passkey' => env('MPESA_PASSKEY'),
    'initiator_name' => env('MPESA_INITIATOR_NAME'),
    'initiator_password' => env('MPESA_INITIATOR_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    |
    | URLs where M-PESA will send payment confirmations and other callbacks
    |
    */
    'callback_url' => env('MPESA_CALLBACK_URL', env('APP_URL') . '/webhooks/payment/mpesa'),
    'timeout_url' => env('MPESA_TIMEOUT_URL', env('APP_URL') . '/webhooks/payment/mpesa/timeout'),
    'result_url' => env('MPESA_RESULT_URL', env('APP_URL') . '/webhooks/payment/mpesa/result'),
    'queue_timeout_url' => env('MPESA_QUEUE_TIMEOUT_URL', env('APP_URL') . '/webhooks/payment/mpesa/queue-timeout'),

    /*
    |--------------------------------------------------------------------------
    | C2B URLs
    |--------------------------------------------------------------------------
    |
    | URLs for Customer to Business payment confirmations
    | Note: Safaricom does NOT allow "mpesa" in callback URLs, so we use /webhooks/payment/c2b
    |
    */
    'validation_url' => env('MPESA_VALIDATION_URL', env('APP_URL') . '/webhooks/payment/c2b'),
    'confirmation_url' => env('MPESA_CONFIRMATION_URL', env('APP_URL') . '/webhooks/payment/c2b'),

    /*
    |--------------------------------------------------------------------------
    | Sandbox API URLs
    |--------------------------------------------------------------------------
    |
    | Sandbox/Testing environment URLs for development and testing
    |
    */
    'sandbox_urls' => [
        'oauth' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate',
        'stk_push' => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
        'stk_push_query' => 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
        'c2b_register' => 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl',
        'transaction_status' => 'https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query',
        'account_balance' => 'https://sandbox.safaricom.co.ke/mpesa/accountbalance/v1/query',
        'reversal' => 'https://sandbox.safaricom.co.ke/mpesa/reversal/v1/request',
        'b2c' => 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
        'b2b' => 'https://sandbox.safaricom.co.ke/mpesa/b2b/v1/paymentrequest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Production API URLs
    |--------------------------------------------------------------------------
    |
    | Production environment URLs - Use only after testing in sandbox
    |
    */
    'production_urls' => [
        // OAuth Token
        'oauth' => 'https://api.safaricom.co.ke/oauth/v1/generate',
        
        // M-PESA Express (STK Push / Lipa na M-PESA Online)
        'stk_push' => 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
        'stk_push_query' => 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query',
        
        // C2B (Customer to Business)
        'c2b_register' => 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl',
        'c2b_register_v2' => 'https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl',
        
        // Transaction Status Query
        'transaction_status' => 'https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query',
        
        // Account Balance
        'account_balance' => 'https://api.safaricom.co.ke/mpesa/accountbalance/v1/query',
        
        // Reversal
        'reversal' => 'https://api.safaricom.co.ke/mpesa/reversal/v1/request',
        
        // B2C (Business to Customer)
        'b2c' => 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
        
        // B2B (Business to Business)
        'b2b_paybill' => 'https://api.safaricom.co.ke/mpesa/b2b/v1/paymentrequest',
        'b2b_buygoods' => 'https://api.safaricom.co.ke/mpesa/b2b/v1/paymentrequest',
        
        // Dynamic QR Code
        'qr_code' => 'https://api.safaricom.co.ke/mpesa/qrcode/v1/generate',
        
        // Bill Manager
        'bill_manager_optin' => 'https://api.safaricom.co.ke/v1/billmanager-invoice/v1/billmanager-invoice/optin',
        'bill_manager_single_invoice' => 'https://api.safaricom.co.ke/v1/billmanager-invoice/v1/billmanager-invoice/single-invoicing',
        'bill_manager_bulk_invoice' => 'https://api.safaricom.co.ke/v1/billmanager-invoice/v1/billmanager-invoice/bulk-invoicing',
        'bill_manager_reconciliation' => 'https://api.safaricom.co.ke/v1/billmanager-invoice/v1/billmanager-invoice/reconciliation',
        'bill_manager_cancel_single' => 'https://api.safaricom.co.ke/v1/billmanager-invoice/v1/billmanager-invoice/cancel-single-invoice',
        'bill_manager_cancel_bulk' => 'https://api.safaricom.co.ke/v1/billmanager-invoice/v1/billmanager-invoice/cancel-bulk-invoice',
        'bill_manager_update_details' => 'https://api.safaricom.co.ke/v1/billmanager-invoice/v1/billmanager-invoice/change-optin-details',
        'bill_manager_update_single' => 'https://api.safaricom.co.ke/v1/billmanager-invoice/v1/billmanager-invoice/change-invoice',
        'bill_manager_update_bulk' => 'https://api.safaricom.co.ke/v1/billmanager-invoice/v1/billmanager-invoice/change-invoices',
    ],

    /*
    |--------------------------------------------------------------------------
    | C2B Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Customer to Business payments
    |
    */
    'c2b' => [
        'response_type' => env('MPESA_C2B_RESPONSE_TYPE', 'Completed'), // Completed or Cancelled
        'confirmation_required' => env('MPESA_C2B_CONFIRMATION_REQUIRED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Timeouts
    |--------------------------------------------------------------------------
    |
    | Timeout settings for various transaction types (in seconds)
    |
    */
    'timeouts' => [
        'stk_push' => env('MPESA_STK_PUSH_TIMEOUT', 120), // 2 minutes
        'transaction_query' => env('MPESA_TRANSACTION_QUERY_TIMEOUT', 30),
        'default' => env('MPESA_DEFAULT_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security settings for M-PESA integration
    |
    */
    'security' => [
        // Safaricom production IPs for webhook verification
        'allowed_ips' => [
            '196.201.214.200',
            '196.201.214.206',
            '196.201.213.114',
            '196.201.214.207',
            '196.201.214.208',
            '196.201.213.44',
            '196.201.212.127',
            '196.201.212.138',
            '196.201.212.129',
            '196.201.212.136',
            '196.201.212.74',
            '196.201.212.69',
        ],
        
        // Verify webhook IP in production
        'verify_webhook_ip' => env('MPESA_VERIFY_WEBHOOK_IP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure M-PESA transaction logging
    |
    */
    'logging' => [
        'enabled' => env('MPESA_LOGGING_ENABLED', true),
        'channel' => env('MPESA_LOG_CHANNEL', 'daily'),
        'level' => env('MPESA_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific M-PESA features
    |
    */
    'features' => [
        'stk_push' => env('MPESA_FEATURE_STK_PUSH', true),
        'c2b' => env('MPESA_FEATURE_C2B', true),
        'b2c' => env('MPESA_FEATURE_B2C', false),
        'b2b' => env('MPESA_FEATURE_B2B', false),
        'reversal' => env('MPESA_FEATURE_REVERSAL', false),
        'account_balance' => env('MPESA_FEATURE_ACCOUNT_BALANCE', false),
    ],
];











