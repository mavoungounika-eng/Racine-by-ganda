<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IA Décisionnelle - Configuration Globale
    |--------------------------------------------------------------------------
    | Configuration centralisée de tous les modules d'IA décisionnelle
    */

    'enabled' => env('AI_DECISIONAL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Modules activables/désactivables
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'churn_prediction' => env('AI_MODULE_CHURN_PREDICTION', true),
        'creator_scoring' => env('AI_MODULE_CREATOR_SCORING', true),
        'recommendation_engine' => env('AI_MODULE_RECOMMENDATION', true),
        'product_performance' => env('AI_MODULE_PRODUCT_PERFORMANCE', false),
        'stock_prediction' => env('AI_MODULE_STOCK_PREDICTION', false),
        'anomaly_detection' => env('AI_MODULE_ANOMALY_DETECTION', false),
        'conversion_optimization' => env('AI_MODULE_CONVERSION_OPT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seuils configurables
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        // Stock
        'stock_critical_days' => env('AI_STOCK_CRITICAL_DAYS', 7),
        'stock_warning_days' => env('AI_STOCK_WARNING_DAYS', 14),
        
        // Créateurs
        'creator_processing_time_max' => env('AI_CREATOR_PROCESSING_MAX', 3), // jours
        'creator_return_rate_max' => env('AI_CREATOR_RETURN_RATE_MAX', 15), // %
        'creator_dispute_rate_max' => env('AI_CREATOR_DISPUTE_RATE_MAX', 5), // %
        
        // Produits
        'product_performance_min_score' => env('AI_PRODUCT_MIN_SCORE', 40),
        'product_rotation_min' => env('AI_PRODUCT_ROTATION_MIN', 0.5),
        
        // Ventes
        'sales_anomaly_drop_percent' => env('AI_SALES_ANOMALY_DROP', 50),
        'conversion_rate_min' => env('AI_CONVERSION_RATE_MIN', 2), // %
        
        // Churn
        'churn_risk_threshold' => env('AI_CHURN_RISK_THRESHOLD', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Planification des jobs
    |--------------------------------------------------------------------------
    */
    'schedule' => [
        'product_performance' => 'daily',      // Tous les jours à 2h
        'stock_prediction' => 'daily',         // Tous les jours à 3h
        'anomaly_detection' => 'hourly',       // Toutes les heures
        'churn_prediction' => 'weekly',        // Tous les lundis
        'recommendations' => 'daily',          // Tous les jours à 4h
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging et traçabilité
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('AI_LOGGING_ENABLED', true),
        'log_calculations' => true,
        'log_recommendations' => true,
        'log_alerts' => true,
        'retention_days' => 90, // Conservation des logs
    ],

    /*
    |--------------------------------------------------------------------------
    | Alertes
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'enabled' => env('AI_ALERTS_ENABLED', true),
        'channels' => ['database', 'mail'], // database, mail, slack
        'recipients' => [
            'critical' => array_filter(explode(',', env('AI_ALERT_CRITICAL_EMAILS', ''))),
            'warning' => array_filter(explode(',', env('AI_ALERT_WARNING_EMAILS', ''))),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'cache_enabled' => env('AI_CACHE_ENABLED', true),
        'cache_ttl' => env('AI_CACHE_TTL', 3600), // 1 heure
        'queue' => env('AI_QUEUE', 'ai-processing'), // Queue dédiée
        'timeout' => env('AI_TIMEOUT', 300), // 5 minutes max par job
    ],

    /*
    |--------------------------------------------------------------------------
    | Pondérations configurables
    |--------------------------------------------------------------------------
    */
    'weights' => [
        'creator_scoring' => [
            'financial_health' => env('AI_WEIGHT_FINANCIAL', 0.30),
            'operational_health' => env('AI_WEIGHT_OPERATIONAL', 0.25),
            'engagement_level' => env('AI_WEIGHT_ENGAGEMENT', 0.20),
            'growth_potential' => env('AI_WEIGHT_GROWTH', 0.15),
            'risk_factor' => env('AI_WEIGHT_RISK', 0.10),
        ],
    ],
];
