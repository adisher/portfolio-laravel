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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'safepay' => [
        'environment'    => env('SAFEPAY_ENVIRONMENT', 'sandbox'),
        'api_key'        => env('SAFEPAY_API_KEY'),
        'api_secret'     => env('SAFEPAY_API_SECRET'),
        'webhook_secret' => env('SAFEPAY_WEBHOOK_SECRET'),
        'base_url'       => env('SAFEPAY_ENVIRONMENT', 'sandbox') === 'production'
            ? 'https://api.getsafepay.com'
            : 'https://sandbox.api.getsafepay.com',
    ],

    'pexels' => [
        'api_key' => env('PEXELS_API_KEY'),
    ],

    'gsc' => [
        'site_url'    => env('GSC_SITE_URL'),
        'credentials' => env('GSC_CREDENTIALS_PATH'),
    ],

    'brave' => [
        'key' => env('BRAVE_SEARCH_API_KEY'),
    ],

];
