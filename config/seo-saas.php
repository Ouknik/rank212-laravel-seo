<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rank212 / SEO SaaS API Key
    |--------------------------------------------------------------------------
    |
    | Your store's unique API Key obtained from the Rank212 Dashboard.
    |
    */
    'api_key' => env('RANK212_API_KEY', env('SEO_SAAS_API_KEY', '')),

    /*
    |--------------------------------------------------------------------------
    | Rank212 / SEO SaaS Base API URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the Headless SEO SaaS endpoint.
    |
    */
    'api_url' => env('RANK212_API_URL', env('SEO_SAAS_API_URL', 'https://api.your-seo-saas.com')),

    /*
    |--------------------------------------------------------------------------
    | Automatic Sync on Model Save
    |--------------------------------------------------------------------------
    |
    | When set to true, Eloquent models using the HasDynamicSeo trait will
    | automatically queue a background optimization job whenever they are saved.
    |
    */
    'auto_sync' => env('RANK212_AUTO_SYNC', env('SEO_SAAS_AUTO_SYNC', true)),

    /*
    |--------------------------------------------------------------------------
    | Background Queue Connection & Name
    |--------------------------------------------------------------------------
    |
    | The queue on which background SEO synchronization jobs should run.
    |
    */
    'queue' => env('RANK212_QUEUE', env('SEO_SAAS_QUEUE', 'default')),

    /*
    |--------------------------------------------------------------------------
    | Local Cache Duration (TTL in seconds)
    |--------------------------------------------------------------------------
    |
    | Default is 86400 seconds (24 hours).
    |
    */
    'cache_ttl' => (int) env('RANK212_CACHE_TTL', env('SEO_SAAS_CACHE_TTL', 86400)),

    /*
    |--------------------------------------------------------------------------
    | Default Defaults & Fallbacks
    |--------------------------------------------------------------------------
    */
    'default_language' => env('RANK212_DEFAULT_LANGUAGE', env('SEO_SAAS_DEFAULT_LANGUAGE', 'ar')),
    'default_currency' => env('RANK212_DEFAULT_CURRENCY', env('SEO_SAAS_DEFAULT_CURRENCY', 'MAD')),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Request Configuration
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => (int) env('SEO_SAAS_TIMEOUT', 10),
        'retry_times' => (int) env('SEO_SAAS_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('SEO_SAAS_RETRY_SLEEP_MS', 500),
    ],
];
