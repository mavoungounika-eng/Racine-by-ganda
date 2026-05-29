<?php

/*
|--------------------------------------------------------------------------
| POS (Point of Sale) Configuration
|--------------------------------------------------------------------------
|
| Configuration centralisée pour le module POS. Toutes les valeurs métier
| ajustables doivent passer par ce fichier plutôt que d'être hardcodées
| dans les services ou contrôleurs.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Cash Discrepancy Threshold
    |----------------------------------------------------------------------
    |
    | Seuil (en devise de base, typiquement XAF) au-delà duquel une
    | différence de caisse en fin de session déclenche un événement
    | CashDiscrepancyDetected et compte dans les métriques de rapport.
    |
    | Auparavant hardcodé à 1.00 dans PosSessionService et PosReportsService.
    |
    */
    'cash_discrepancy_threshold' => (float) env('POS_CASH_DISCREPANCY_THRESHOLD', 1.00),

    /*
    |----------------------------------------------------------------------
    | Offline Queue
    |----------------------------------------------------------------------
    |
    | TTL (en secondes) des entrées de file offline côté serveur.
    | Au-delà, les entrées non processées sont purgées par le worker
    | pos:cleanup-offline-queue.
    |
    */
    'offline_queue_ttl' => (int) env('POS_OFFLINE_QUEUE_TTL', 3600),

    /*
    |----------------------------------------------------------------------
    | Stale Payment Cleanup
    |----------------------------------------------------------------------
    |
    | Minutes après lesquelles un paiement pending (card/mobile) est
    | considéré comme abandonné et annulé automatiquement par le job
    | pos:cleanup-pending-payments.
    |
    */
    'stale_payment_threshold_minutes' => (int) env('POS_STALE_PAYMENT_MINUTES', 30),

    /*
    |----------------------------------------------------------------------
    | POS Electron — Distribution
    |----------------------------------------------------------------------
    */
    'version'     => env('POS_ELECTRON_VERSION', '1.0.0'),
    'release_url' => env('POS_ELECTRON_RELEASE_URL', '#'),

];
