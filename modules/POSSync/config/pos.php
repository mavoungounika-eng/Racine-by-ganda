<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS Sync Configuration
    |--------------------------------------------------------------------------
    */

    // Email admin pour notifications critiques
    'admin_email' => env('POS_ADMIN_EMAIL', 'admin@racine-by-ganda.com'),

    // SMS admin pour alertes fraude
    'admin_phone' => env('POS_ADMIN_PHONE', '+242XXXXXXXXX'),

    // Seuil de variance de prix (%)
    'price_variance_threshold' => env('POS_PRICE_VARIANCE_THRESHOLD', 10),

    // Action si variance prix dépassée
    'price_variance_action' => env('POS_PRICE_VARIANCE_ACTION', 'accept_pos_price'), // ou 'reject'

    // Durée de vie JWT (secondes)
    'jwt_ttl' => env('POS_JWT_TTL', 7 * 24 * 60 * 60), // 7 jours

    // Durée conservation logs audit (jours)
    'audit_logs_retention' => env('POS_AUDIT_LOGS_RETENTION', 730), // 2 ans

    // Taille maximale batch sync
    'max_batch_size' => env('POS_MAX_BATCH_SIZE', 50),
];
