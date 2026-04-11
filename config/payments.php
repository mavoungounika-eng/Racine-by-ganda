<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy Webhooks
    |--------------------------------------------------------------------------
    |
    | Control whether legacy webhook endpoints are enabled.
    | When disabled, legacy endpoints will return 410 Gone.
    |
    */
    'legacy_webhooks_enabled' => env('LEGACY_WEBHOOKS_ENABLED', true),
    /*
    |--------------------------------------------------------------------------
    | Payment Events Retention Policy
    |--------------------------------------------------------------------------
    |
    | Durée de conservation des événements webhook/callback avant purge.
    |
    */

    'events' => [
        'retention_days' => env('PAYMENTS_EVENTS_RETENTION_DAYS', 90),
        'keep_failed' => env('PAYMENTS_EVENTS_KEEP_FAILED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Transactions Retention Policy
    |--------------------------------------------------------------------------
    |
    | Politique de rétention des transactions (conservation totale en v1.1).
    |
    */

    'transactions' => [
        'retention_years' => env('PAYMENTS_TRANSACTIONS_RETENTION_YEARS', 'unlimited'),
        'archive_enabled' => env('PAYMENTS_TRANSACTIONS_ARCHIVE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Audit Logs Retention Policy
    |--------------------------------------------------------------------------
    |
    | Durée de conservation des logs d'audit avant purge.
    |
    */

    'audit_logs' => [
        'retention_days' => env('PAYMENTS_AUDIT_LOGS_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks Stuck Requeue Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le requeue automatique des événements stuck.
    |
    */

    'webhooks' => [
        'stuck_requeue_enabled' => env('PAYMENTS_STUCK_REQUEUE_ENABLED', true),
        'stuck_requeue_minutes' => env('PAYMENTS_STUCK_REQUEUE_MINUTES', 10),
        'stuck_requeue_schedule' => env('PAYMENTS_STUCK_REQUEUE_SCHEDULE', 'everyFiveMinutes'),
    ],
];


