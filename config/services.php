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
    ],

];
