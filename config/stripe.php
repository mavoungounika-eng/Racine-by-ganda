<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Public Key
    |--------------------------------------------------------------------------
    |
    | Your Stripe publishable API key. This is used for client-side operations.
    |
    */
    'public_key' => env('STRIPE_PUBLIC_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Stripe Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Stripe secret API key. This is used for server-side operations.
    |
    */
    'secret_key' => env('STRIPE_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The webhook signing secret for verifying Stripe webhook events.
    |
    */
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for Stripe payments. Should be a valid ISO currency code.
    |
    */
    'currency' => env('STRIPE_CURRENCY', 'XAF'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable Stripe payment processing. Set to false to disable.
    |
    */
    'enabled' => env('STRIPE_ENABLED', false),
];
