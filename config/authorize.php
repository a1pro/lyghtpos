<?php

return [
    'api_login_id' => env('AUTHORIZE_API_LOGIN_ID'), // Changed from API_LOGIN_ID
    'transaction_key' => env('AUTHORIZE_TRANSACTION_KEY'), // Changed from TRANSACTION_KEY
    'sandbox_mode' => env('AUTHORIZE_SANDBOX_MODE', true),
    'currency' => env('AUTHORIZE_CURRENCY', 'USD'),
];