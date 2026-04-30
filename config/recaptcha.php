<?php

return [

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable reCAPTCHA validation globally.
    | Set to false in development to bypass validation.
    |
    */
    'enabled' => env('RECAPTCHA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Site Key
    |--------------------------------------------------------------------------
    |
    | Your reCAPTCHA v3 site key (public key).
    | Get it from: https://www.google.com/recaptcha/admin
    |
    */
    'site_key' => env('RECAPTCHA_SITE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Secret Key
    |--------------------------------------------------------------------------
    |
    | Your reCAPTCHA v3 secret key (private key).
    | NEVER expose this in frontend code.
    |
    */
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Score Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum score required to pass validation (0.0 - 1.0).
    | Recommended: 0.5 for login, 0.3 for less critical actions.
    |
    | Score interpretation:
    | - 1.0: Very likely a good interaction
    | - 0.5: Neutral
    | - 0.0: Very likely a bot
    |
    */
    'threshold' => env('RECAPTCHA_THRESHOLD', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Verification URL
    |--------------------------------------------------------------------------
    |
    | Google reCAPTCHA API verification endpoint.
    |
    */
    'verify_url' => env('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | HTTP request timeout in seconds for reCAPTCHA verification.
    |
    */
    'timeout' => 5,

    /*
    |--------------------------------------------------------------------------
    | Skip for Testing
    |--------------------------------------------------------------------------
    |
    | Automatically disable reCAPTCHA when running tests.
    |
    */
    'skip_for_testing' => env('APP_ENV') === 'testing',

];
