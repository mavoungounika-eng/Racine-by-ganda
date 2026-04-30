<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Shared Secret
    |--------------------------------------------------------------------------
    |
    | Secret used to sign and verify JWT tokens for machine-to-machine flows
    | (e.g. POSSync devices). Set JWT_SECRET in environment for production.
    |
    */
    'secret' => env('JWT_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    */
    'algo' => env('JWT_ALGO', 'HS256'),
];
