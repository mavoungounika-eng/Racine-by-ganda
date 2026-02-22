<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Queue Overload Protection Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la protection contre la surcharge des queues.
    | Inclut circuit breaker, rate limiting, et monitoring.
    |
    */

    'circuit_breaker' => [
        /*
         * Nombre d'échecs consécutifs avant ouverture du circuit
         */
        'failure_threshold' => env('QUEUE_CB_FAILURE_THRESHOLD', 10),

        /*
         * Nombre de succès consécutifs requis pour fermer le circuit
         */
        'success_threshold' => env('QUEUE_CB_SUCCESS_THRESHOLD', 5),

        /*
         * Durée du cooldown en secondes avant de tenter de fermer le circuit
         */
        'timeout' => env('QUEUE_CB_TIMEOUT', 60),

        /*
         * Durée de rétention des données du circuit breaker (secondes)
         */
        'ttl' => env('QUEUE_CB_TTL', 3600),
    ],

    'rate_limits' => [
        /*
         * Limites de jobs par minute pour chaque type de queue
         * Format: 'nombre/période' (ex: '100/minute', '10/second')
         */
        'webhooks' => env('QUEUE_RATE_WEBHOOKS', '100/minute'),
        'emails' => env('QUEUE_RATE_EMAILS', '50/minute'),
        'notifications' => env('QUEUE_RATE_NOTIFICATIONS', '200/minute'),
        'default' => env('QUEUE_RATE_DEFAULT', '500/minute'),
        'pos' => env('QUEUE_RATE_POS', '100/minute'),
    ],

    'monitoring' => [
        /*
         * Activer la collecte de métriques Prometheus
         */
        'enabled' => env('QUEUE_MONITORING_ENABLED', true),

        /*
         * Intervalle de collecte des métriques (secondes)
         */
        'interval' => env('QUEUE_MONITORING_INTERVAL', 60),

        /*
         * Seuils d'alerte
         */
        'thresholds' => [
            'queue_size' => [
                'warning' => 500,
                'critical' => 1000,
            ],
            'processing_time' => [
                'warning' => 5.0,  // secondes
                'critical' => 10.0,
            ],
            'failure_rate' => [
                'warning' => 0.05,  // 5%
                'critical' => 0.10, // 10%
            ],
        ],
    ],

    'dead_letter_queue' => [
        /*
         * Nombre maximum de tentatives avant envoi en DLQ
         */
        'max_attempts' => env('QUEUE_DLQ_MAX_ATTEMPTS', 3),

        /*
         * Durée de rétention des jobs en DLQ (jours)
         */
        'retention_days' => env('QUEUE_DLQ_RETENTION_DAYS', 7),

        /*
         * Activer les notifications pour les jobs en DLQ
         */
        'notify' => env('QUEUE_DLQ_NOTIFY', true),

        /*
         * Canaux de notification (slack, email, log)
         */
        'notification_channels' => ['slack', 'log'],
    ],
];
