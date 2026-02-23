<?php
// config/paypal.php

return [
    'mode' => env('PAYPAL_MODE', 'sandbox'),
    'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID', ''),
    'secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET', ''),
    
    'settings' => [
        'log.LogEnabled' => true,
        'log.FileName' => storage_path('logs/paypal.log'),
        'log.LogLevel' => 'DEBUG',
    ],
];