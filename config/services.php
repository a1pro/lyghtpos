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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'procharge' => [
        'base_url' => env('PROCHARGE_BASE_URL'),
        'username' => env('PROCHARGE_USERNAME'),
        'password' => env('PROCHARGE_PASSWORD'),
        'merchant_id' => env('PROCHARGE_MERCHANT_ID'),
    ],

    'cyclos' => [
        'base_url' => env('CYCLOS_BASE_URL'),
        'username' => env('CYCLOS_API_USERNAME'),
        'password' => env('CYCLOS_API_PASSWORD'),
        'default_group' => env('CYCLOS_DEFAULT_GROUP'),
        'agreement' => env('CYCLOS_ACCEPT_AGREEMENT'),
        'mock_mode' => env('CYCLOS_MOCK_MODE', false),
        'fallback_to_mock' => env('CYCLOS_FALLBACK_TO_MOCK', true),
        'use_api_password' => env('CYCLOS_USE_API_PASSWORD', true),
        'channel' => env('CYCLOS_CHANNEL', 'webServices'),

        // Must come only from .env
        'payment_type_id' => env('CYCLOS_PAYMENT_TYPE_ID'),
        'order_payment_type_id' => env('CYCLOS_ORDER_PAYMENT_TYPE_ID'),
        'admin_user_id' => env('CYCLOS_ADMIN_USER_ID'),

        // Currency configuration
        'currency_code' => env('CYCLOS_CURRENCY_CODE', 'unit'),
        'currency_symbol' => env('CYCLOS_CURRENCY_SYMBOL', 'TT'),
        'currency_name' => env('CYCLOS_CURRENCY_NAME', 'TradeTokens'),
    ],
    'authorize' => [
        'api_login_id' => env('AUTHORIZE_API_LOGIN_ID'),
        'transaction_key' => env('AUTHORIZE_TRANSACTION_KEY'),
        'sandbox_mode' => env('AUTHORIZE_SANDBOX_MODE', true),
        'currency' => env('AUTHORIZE_CURRENCY', 'USD'),
        'mock_mode' => env('AUTHORIZE_MOCK_MODE', false),
        'fallback_to_mock' => env('AUTHORIZE_FALLBACK_TO_MOCK', true),
    ],  
    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'sandbox' => [
            'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
            'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
            'app_id' => env('PAYPAL_SANDBOX_APP_ID', 'APP-80W284485P519543T'),
        ],
        'live' => [
            'client_id' => env('PAYPAL_LIVE_CLIENT_ID'),
            'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET'),
            'app_id' => env('PAYPAL_LIVE_APP_ID', 'APP-80W284485P519543T'),
        ],
        'payment_action' => env('PAYPAL_PAYMENT_ACTION', 'Sale'),
        'currency' => env('PAYPAL_CURRENCY', 'USD'),
        'notify_url' => env('PAYPAL_NOTIFY_URL'),
        'locale' => env('PAYPAL_LOCALE', 'en_US'),
        'validate_ssl' => env('PAYPAL_VALIDATE_SSL', true),
        'mock_mode' => env('PAYPAL_MOCK_MODE', false),
        'fallback_to_mock' => env('PAYPAL_FALLBACK_TO_MOCK', true),
    ],

];