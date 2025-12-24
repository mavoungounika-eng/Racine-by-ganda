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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'XAF'), // XAF = Franc CFA (CEMAC)
    ],

    'monetbil' => [
        'service_key' => env('MONETBIL_SERVICE_KEY'),
        'service_secret' => env('MONETBIL_SERVICE_SECRET'),
        'widget_version' => env('MONETBIL_WIDGET_VERSION', 'v2.1'),
        'country' => env('MONETBIL_COUNTRY', 'CG'),
        'currency' => env('MONETBIL_CURRENCY', 'XAF'),
        'notify_url' => env('MONETBIL_NOTIFY_URL'),
        'return_url' => env('MONETBIL_RETURN_URL'),
        'allowed_ips' => env('MONETBIL_ALLOWED_IPS'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', config('app.url') . '/auth/google/callback'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI', config('app.url') . '/auth/apple/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', config('app.url') . '/auth/facebook/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile Money Services
    |--------------------------------------------------------------------------
    */

    'mtn_momo' => [
        'enabled' => env('MTN_MOMO_ENABLED', false),
        'api_key' => env('MTN_MOMO_API_KEY'),
        'api_secret' => env('MTN_MOMO_API_SECRET'),
        'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
        'environment' => env('MTN_MOMO_ENVIRONMENT', 'sandbox'), // sandbox|production
        'collection_id' => env('MTN_MOMO_COLLECTION_ID'),
        'webhook_secret' => env('MTN_MOMO_WEBHOOK_SECRET'),
        'currency' => env('MTN_MOMO_CURRENCY', 'XAF'),
        'base_url' => [
            'sandbox' => 'https://sandbox.momodeveloper.mtn.com',
            'production' => 'https://momodeveloper.mtn.com',
        ],
    ],

    'airtel_money' => [
        'enabled' => env('AIRTEL_MONEY_ENABLED', false),
        'client_id' => env('AIRTEL_MONEY_CLIENT_ID'),
        'client_secret' => env('AIRTEL_MONEY_CLIENT_SECRET'),
        'environment' => env('AIRTEL_MONEY_ENVIRONMENT', 'sandbox'), // sandbox|production
        'webhook_secret' => env('AIRTEL_MONEY_WEBHOOK_SECRET'),
        'currency' => env('AIRTEL_MONEY_CURRENCY', 'XAF'),
        'base_url' => [
            'sandbox' => 'https://openapiuat.airtel.africa',
            'production' => 'https://openapi.airtel.africa',
        ],
    ],

];
