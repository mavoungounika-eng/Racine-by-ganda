<?php

return [
  /*
  |--------------------------------------------------------------------------
  | CRM & Loyalty Configuration
  |--------------------------------------------------------------------------
  |
  | This file contains settings for the B2C CRM module, including
  | points calculation, rewards, and segmentation intervals.
  |
  */

  // Loyalty calculation rules
  'points_per_amount'     => env('CRM_POINTS_PER_AMOUNT', 1000), // 1 point per 1000 XAF
  'signup_bonus_points'   => env('CRM_SIGNUP_BONUS', 100),
  'referral_points'       => env('CRM_REFERRAL_POINTS', 500),
  'points_expiry_days'    => env('CRM_POINTS_EXPIRY_DAYS', 365),

  // Technical settings
  'metrics_cache_ttl'     => 3600, // 1 hour in seconds
  'segment_sync_batch'    => 100,

  // Default Loyalty Tiers
  'loyalty_levels' => [
    [
      'name' => 'Bronze',
      'min_points' => 0,
      'color' => '#CD7F32',
      'description' => 'Niveau de bienvenue',
      'benefits' => ['Accès aux ventes privées']
    ],
    [
      'name' => 'Silver',
      'min_points' => 500,
      'color' => '#C0C0C0',
      'description' => 'Client fidèle argent',
      'benefits' => ['Réduction de 5% sur la collection Racine']
    ],
    [
      'name' => 'Gold',
      'min_points' => 2000,
      'color' => '#FFD700',
      'description' => 'Client précieux or',
      'benefits' => ['Livraison gratuite illimitée', 'Réduction de 10%']
    ],
    [
      'name' => 'VIP',
      'min_points' => 5000,
      'color' => '#9B59B6',
      'description' => 'Exclusivité totale',
      'benefits' => ['Conciergerie dédiée', 'Cadeaux anniversaire personnalisés']
    ],
  ],
];
