<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alerts Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des alertes système (Slack, Email, Logs)
    |
    */

    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => env('SLACK_ALERT_CHANNEL', '#alerts'),
        'username' => env('SLACK_ALERT_USERNAME', 'RACINE Monitoring'),
    ],

    'email' => [
        'recipients' => array_filter(explode(',', env('ALERT_EMAIL_RECIPIENTS', ''))),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'alerts@racine.com'),
            'name' => env('MAIL_FROM_NAME', 'RACINE Alerts'),
        ],
    ],

    'thresholds' => [
        'queue_size' => [
            'warning' => env('ALERT_QUEUE_SIZE_WARNING', 500),
            'critical' => env('ALERT_QUEUE_SIZE_CRITICAL', 1000),
        ],
        'processing_time' => [
            'warning' => env('ALERT_PROCESSING_TIME_WARNING', 5.0),
            'critical' => env('ALERT_PROCESSING_TIME_CRITICAL', 10.0),
        ],
        'failure_rate' => [
            'warning' => env('ALERT_FAILURE_RATE_WARNING', 0.05), // 5%
            'critical' => env('ALERT_FAILURE_RATE_CRITICAL', 0.10), // 10%
        ],
        'error_rate' => [
            'warning' => env('ALERT_ERROR_RATE_WARNING', 0.01), // 1%
            'critical' => env('ALERT_ERROR_RATE_CRITICAL', 0.05), // 5%
        ],
    ],

    'enabled' => env('ALERTS_ENABLED', true),
];
