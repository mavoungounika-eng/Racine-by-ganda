<?php

return [
  'driver'  => env('AI_DRIVER', 'openai'),
  'model'   => env('AI_MODEL', 'gpt-4o'),
  'timeout' => (int) env('AI_TIMEOUT', 30),

  'quotas' => [
    'description_per_day'     => 20,
    'price_suggestion_per_day'=> 10,
    'analysis_per_day'        => 10,
    'chat_per_day'            => 50,
  ],

  'cache_ttl' => [
    'description'      => 86400,
    'analysis'         => 3600,
    'price_suggestion' => 3600,
    'crm_insight'      => 1800,
    'stock_anomaly'    => 900,
    'admin_summary'    => 86400,
  ],

  'costs' => [
    'gpt-4o' => [
      'input_per_1k'  => 0.005,
      'output_per_1k' => 0.015,
    ],
  ],

  'prompts' => [
    'language' => 'fr',
    'brand'    => 'RACINE BY GANDA',
    'tone'     => 'professionnel et chaleureux',
  ],
];
