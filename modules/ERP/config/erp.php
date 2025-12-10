<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration Module ERP
    |--------------------------------------------------------------------------
    |
    | Configuration centralisée pour le module ERP
    |
    */

    // Seuils de stock
    'stock' => [
        'low_threshold' => env('ERP_STOCK_LOW_THRESHOLD', 5),
        'critical_threshold' => env('ERP_STOCK_CRITICAL_THRESHOLD', 10),
        'replenishment_threshold' => env('ERP_STOCK_REPLENISHMENT_THRESHOLD', 10),
    ],

    // Cache
    'cache' => [
        'dashboard_stats_ttl' => env('ERP_CACHE_DASHBOARD_TTL', 300), // 5 minutes
        'top_materials_ttl' => env('ERP_CACHE_TOP_MATERIALS_TTL', 600), // 10 minutes
        'low_stock_ttl' => env('ERP_CACHE_LOW_STOCK_TTL', 120), // 2 minutes
        'recent_purchases_ttl' => env('ERP_CACHE_RECENT_PURCHASES_TTL', 300), // 5 minutes
    ],

    // Rate limiting
    'rate_limit' => [
        'max_attempts' => env('ERP_RATE_LIMIT_MAX', 60),
        'decay_minutes' => env('ERP_RATE_LIMIT_DECAY', 1),
    ],

    // Références
    'purchase' => [
        'reference_prefix' => env('ERP_PURCHASE_PREFIX', 'PO'),
        'reference_length' => env('ERP_PURCHASE_REF_LENGTH', 8),
    ],

    // Alertes
    'alerts' => [
        'enabled' => env('ERP_ALERTS_ENABLED', true),
        'check_interval_hours' => env('ERP_ALERTS_CHECK_INTERVAL', 24),
    ],
];

