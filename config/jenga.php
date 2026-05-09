<?php

return [
    'environment' => env('JENGA_ENVIRONMENT', 'sandbox'),

    'base_urls' => [
        'sandbox' => env('JENGA_SANDBOX_BASE_URL', 'https://uat.finserve.africa'),
        'production' => env('JENGA_PRODUCTION_BASE_URL', 'https://api.finserve.africa'),
    ],

    'auth' => [
        'api_key' => env('JENGA_API_KEY'),
        'merchant_code' => env('JENGA_MERCHANT_CODE'),
        'consumer_secret' => env('JENGA_CONSUMER_SECRET'),
        'token_cache_ttl_seconds' => env('JENGA_TOKEN_CACHE_TTL_SECONDS', 3300),
        'timeout' => env('JENGA_AUTH_TIMEOUT', 30),
    ],

    'security' => [
        'private_key_path' => env('JENGA_PRIVATE_KEY_PATH'),
    ],

    'timeouts' => [
        'default' => env('JENGA_TIMEOUT', 60),
    ],

    'endpoints' => [
        'authenticate_merchant' => '/authentication/api/v3/authenticate/merchant',
        'account_inquiry' => '/v3-apis/account-api/v3.0/search/{countryCode}/{accountNumber}',
        'account_balance' => '/v3-apis/account-api/v3.0/accounts/balances/{countryCode}/{accountId}',
        'account_mini_statement' => '/v3-apis/account-api/v3.0/accounts/miniStatement/{countryCode}/{accountNumber}',
        'account_full_statement' => '/v3-apis/account-api/v3.0/accounts/fullStatement',
        'send_mobile' => '/v3-apis/transaction-api/v3.0/remittance/sendmobile',
        'internal_bank_transfer' => '/v3-apis/transaction-api/v3.0/remittance/internalBankTransfer',
        'rtgs' => '/v3-apis/transaction-api/v3.0/remittance/rtgs',
        'rtgs_payment_purposes' => '/v3-apis/transaction-api/v3.0/rtgs/paymentPurpose',
        'stk_ussd_push_initiate' => '/v3-apis/payment-api/v3.0/stkussdpush/initiate',
        'query_transaction_details' => '/v3-apis/transaction-api/v3.0/transactions/details/{ref}',
        'billers' => '/v3-apis/transaction-api/v3.0/billers',
        'merchants' => '/v3-apis/transaction-api/v3.0/merchants',

        // Optional endpoint overrides
        'custom_query_transaction_details' => env('JENGA_QUERY_TRANSACTION_ENDPOINT', ''),
        'custom_mpesa_stk_push' => env('JENGA_MPESA_STK_PUSH_ENDPOINT', ''),
    ],
];

