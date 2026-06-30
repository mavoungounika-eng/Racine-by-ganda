<?php

return [
  'default'   => env('DEFAULT_CURRENCY', 'XAF'),
  'supported' => ['XAF', 'XOF', 'EUR'],
  'reference' => 'XAF',

  'rates' => [
    'XAF' => ['XOF' => 1.0,       'EUR' => 1/655.957],
    'XOF' => ['XAF' => 1.0,       'EUR' => 1/655.957],
    'EUR' => ['XAF' => 655.957,   'XOF' => 655.957  ],
  ],

  'symbols' => [
    'XAF' => 'FCFA',
    'XOF' => 'CFA',
    'EUR' => '€',
  ],

  'decimals' => [
    'XAF' => 0,
    'XOF' => 0,
    'EUR' => 2,
  ],

  'stripe_supported'   => ['EUR'],
  'monetbil_supported' => ['XAF', 'XOF'],

  'phone_prefixes' => [
    '+225' => 'XOF', // Côte d'Ivoire
    '+221' => 'XOF', // Sénégal
    '+223' => 'XOF', // Mali
    '+226' => 'XOF', // Burkina Faso
    '+242' => 'XAF', // Congo
    '+237' => 'XAF', // Cameroun
    '+241' => 'XAF', // Gabon
    '+236' => 'XAF', // RCA
    '+235' => 'XAF', // Tchad
    '+240' => 'XAF', // Guinée Équatoriale
  ],
];
