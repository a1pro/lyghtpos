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
        'admin_user_id' => env('CYCLOS_ADMIN_USER_ID'),

        // Currency configuration
        'currency_code' => env('CYCLOS_CURRENCY_CODE', 'TT'),
        'currency_symbol' => env('CYCLOS_CURRENCY_SYMBOL', 'TT'),
        'currency_name' => env('CYCLOS_CURRENCY_NAME', 'TradeTokens'),
    ],

];