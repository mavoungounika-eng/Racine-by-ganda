<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERP — Gestion des stocks
    |--------------------------------------------------------------------------
    */

    // Seuil stock bas par défaut (si non configuré au niveau produit)
    'low_stock_threshold' => env('LOW_STOCK_THRESHOLD', 5),

    // Throttle des alertes stock bas : 1 email maximum par produit par intervalle
    'stock_alert_throttle_minutes' => 60,

    // Queue nommée pour les jobs ERP
    'stock_queue' => env('STOCK_QUEUE', 'stock'),

    // Channel de log dédié aux anomalies de stock
    'anomaly_log_channel' => 'erp_stock',
];
