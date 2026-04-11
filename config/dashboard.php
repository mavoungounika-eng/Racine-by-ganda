<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Cache TTL (minutes)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'global_state' => 5,      // KPIs temps réel
        'alerts' => 3,            // Alertes critiques
        'commercial' => 10,       // Activité commerciale
        'marketplace' => 15,      // Marketplace (moins critique)
        'operations' => 5,        // Opérations
        'trends' => 15,           // Tendances 7j
    ],

    /*
    |--------------------------------------------------------------------------
    | Seuils d'Alertes
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'revenue' => [
            'good' => 1.0,        // > moyenne 7j
            'warning' => 0.5,     // 50-100% moyenne
            'critical' => 0.5,    // < 50% moyenne
        ],
        'orders' => [
            'good' => 30,
            'warning' => 15,
            'critical' => 15,
        ],
        'conversion' => [
            'good' => 3.0,        // %
            'warning' => 1.5,
            'critical' => 1.5,
        ],
        'pending_orders' => [
            'good' => 5,
            'warning' => 15,
            'critical' => 15,
        ],
        'stock_critical' => [
            'threshold' => 'reorder_level', // Colonne DB
        ],
        'marketplace_dominance' => 40, // % max du CA total
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets Activés
    |--------------------------------------------------------------------------
    */
    'widgets' => [
        'global_state' => true,
        'alerts' => true,
        'commercial_activity' => true,
        'marketplace' => true,
        'operations' => true,
        'trends' => true,
    ],
];
