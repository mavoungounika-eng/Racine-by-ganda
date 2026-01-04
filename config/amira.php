<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Amira - Configuration
    |--------------------------------------------------------------------------
    | Configuration de l'assistante commerciale Amira
    */

    'enabled' => env('AMIRA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | NLP Provider
    |--------------------------------------------------------------------------
    */
    'nlp_provider' => env('AMIRA_NLP_PROVIDER', 'knowledge_base'), // 'knowledge_base' ou 'openai'
    'nlp_api_key' => env('AMIRA_NLP_API_KEY'),
    'model' => env('AMIRA_MODEL', 'gpt-4'),
    'max_tokens' => 150,
    'temperature' => 0.7,

    /*
    |--------------------------------------------------------------------------
    | Scope autorisé
    |--------------------------------------------------------------------------
    */
    'scope' => [
        'products' => true,
        'orders' => true,
        'shipping' => true,
        'support' => true,
        'returns' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Restrictions strictes
    |--------------------------------------------------------------------------
    */
    'restrictions' => [
        'no_ai_mention' => true, // Ne jamais mentionner "IA", "algorithme", "système"
        'no_business_advice' => true, // Pas de conseils business
        'no_creator_comparison' => true, // Pas de comparaison entre créateurs
        'no_internal_data' => true, // Pas de données internes
    ],

    /*
    |--------------------------------------------------------------------------
    | Ton de la charte
    |--------------------------------------------------------------------------
    */
    'tone' => [
        'calm' => true,
        'helpful' => true,
        'clear' => true,
        'professional' => true,
        'not_familiar' => true,
        'not_enthusiastic' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback
    |--------------------------------------------------------------------------
    */
    'fallback_message' => "Je ne peux pas répondre à cette question. Contactez notre support : support@racinebyganda.com",
];
