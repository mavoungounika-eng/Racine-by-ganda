<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Project
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API project. This is used optionally in
    | situations where you are using a legacy user API key and need association
    | with a project. This is not required for the newer API keys.
    */
    'project' => env('OPENAI_PROJECT'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Base URL
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API base URL used to make requests. This
    | is needed if using a custom API endpoint. Defaults to: api.openai.com/v1
    */
    'base_uri' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    */
    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the HTTP client options. You can adjust retries and other
    | Guzzle HTTP client options here.
    */
    'http' => [
        'retries' => 3,
        'retry_delay' => 100, // milliseconds
        'timeout' => env('OPENAI_REQUEST_TIMEOUT', 60),
        'connect_timeout' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Model Configuration
    |--------------------------------------------------------------------------
    |
    | Specify the default model to use for various operations.
    */
    'defaults' => [
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        'top_p' => env('OPENAI_TOP_P', 1),
        'frequency_penalty' => env('OPENAI_FREQUENCY_PENALTY', 0),
        'presence_penalty' => env('OPENAI_PRESENCE_PENALTY', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Models
    |--------------------------------------------------------------------------
    |
    | List of available OpenAI models with their configurations.
    | You can customize this list based on your needs.
    */
    'models' => [
        'gpt-3.5-turbo' => [
            'name' => 'GPT-3.5 Turbo',
            'description' => 'Fast and cost-effective for most tasks',
            'max_tokens' => 4096,
            'context_length' => 16385,
            'supports_functions' => true,
            'supports_json_mode' => true,
        ],
        'gpt-3.5-turbo-16k' => [
            'name' => 'GPT-3.5 Turbo 16K',
            'description' => 'Same as GPT-3.5 Turbo but with 16K context',
            'max_tokens' => 16384,
            'context_length' => 16384,
            'supports_functions' => true,
            'supports_json_mode' => true,
        ],
        'gpt-4-turbo-preview' => [
            'name' => 'GPT-4 Turbo Preview',
            'description' => 'Latest GPT-4 model with 128K context',
            'max_tokens' => 4096,
            'context_length' => 128000,
            'supports_functions' => true,
            'supports_json_mode' => true,
        ],
        'gpt-4' => [
            'name' => 'GPT-4',
            'description' => 'Most capable GPT-4 model',
            'max_tokens' => 8192,
            'context_length' => 8192,
            'supports_functions' => true,
            'supports_json_mode' => true,
        ],
        'gpt-4-32k' => [
            'name' => 'GPT-4 32K',
            'description' => 'GPT-4 with 32K context',
            'max_tokens' => 32768,
            'context_length' => 32768,
            'supports_functions' => true,
            'supports_json_mode' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Recommendations
    |--------------------------------------------------------------------------
    |
    | Suggest which model to use for different types of tasks.
    */
    'recommendations' => [
        'chat' => 'gpt-3.5-turbo',
        'analysis' => 'gpt-4-turbo-preview',
        'code' => 'gpt-4-turbo-preview',
        'creative' => 'gpt-4',
        'summarization' => 'gpt-3.5-turbo',
        'translation' => 'gpt-3.5-turbo',
        'complex_reasoning' => 'gpt-4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent hitting OpenAI limits.
    */
    'rate_limiting' => [
        'enabled' => env('OPENAI_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('OPENAI_REQUESTS_PER_MINUTE', 60),
        'tokens_per_minute' => env('OPENAI_TOKENS_PER_MINUTE', 150000),
        'retry_after' => env('OPENAI_RETRY_AFTER', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for OpenAI requests and responses.
    */
    'logging' => [
        'enabled' => env('OPENAI_LOGGING_ENABLED', false),
        'channel' => env('OPENAI_LOGGING_CHANNEL', 'stack'),
        'level' => env('OPENAI_LOGGING_LEVEL', 'info'),
        'log_requests' => env('OPENAI_LOG_REQUESTS', false),
        'log_responses' => env('OPENAI_LOG_RESPONSES', false),
        'log_errors' => env('OPENAI_LOG_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for OpenAI responses to reduce costs and latency.
    */
    'cache' => [
        'enabled' => env('OPENAI_CACHE_ENABLED', true),
        'ttl' => env('OPENAI_CACHE_TTL', 3600), // seconds
        'store' => env('OPENAI_CACHE_STORE', 'file'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Configure fallback behavior when OpenAI API fails.
    */
    'fallback' => [
        'enabled' => env('OPENAI_FALLBACK_ENABLED', false),
        'model' => env('OPENAI_FALLBACK_MODEL', 'gpt-3.5-turbo'),
        'max_retries' => env('OPENAI_FALLBACK_RETRIES', 2),
    ],

];