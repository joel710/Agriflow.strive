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
    | a conventional file to locate thevarious service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Fictional PayGate Configuration
    'paygate' => [
        'api_key' => env('PAYGATE_API_KEY'),
        'secret_key' => env('PAYGATE_SECRET_KEY'),
        'endpoint_url' => env('PAYGATE_ENDPOINT_URL', 'https://api.simulated-paygate.com/v1'), // Example
        'webhook_secret' => env('PAYGATE_WEBHOOK_SECRET'),
    ],

    // Fictional TMoney Configuration
    'tmoney' => [
        'api_key' => env('TMONEY_API_KEY'),
        'api_secret' => env('TMONEY_API_SECRET'),
        'merchant_code' => env('TMONEY_MERCHANT_CODE'),
        'endpoint_url' => env('TMONEY_ENDPOINT_URL', 'https://api.simulated-tmoney.tg/v1'), // Example
        'webhook_secret' => env('TMONEY_WEBHOOK_SECRET'),
    ],

    // Fictional Moov Configuration
    'moov' => [
        'client_id' => env('MOOV_CLIENT_ID'),
        'client_secret' => env('MOOV_CLIENT_SECRET'),
        'endpoint_url' => env('MOOV_ENDPOINT_URL', 'https://api.simulated-moovmoney.africa/v1'), // Example
        'webhook_secret' => env('MOOV_WEBHOOK_SECRET'),
    ],

];
