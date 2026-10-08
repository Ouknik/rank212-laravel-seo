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

    /*
    |--------------------------------------------------------------------------
    | Publishing Integration Contract (Step 4 Foundation)
    |--------------------------------------------------------------------------
    |
    | Non-delivering contract foundation. Remains disabled by default.
    | Dedicated publish secret required per store (never falls back to API key).
    |
    */
    'publishing' => [
        'enabled' => (bool) env('RANK212_PUBLISHING_ENABLED', env('SEO_SAAS_PUBLISHING_ENABLED', false)),
        'auto_publish' => (bool) env('RANK212_AUTO_PUBLISH', false),
        'store_id' => env('RANK212_STORE_ID', env('SEO_SAAS_STORE_ID', null)) !== null
            ? (int) env('RANK212_STORE_ID', env('SEO_SAAS_STORE_ID', null))
            : null,
        'secret' => env('RANK212_PUBLISH_SECRET', env('SEO_SAAS_PUBLISH_SECRET', null)),
        'route_prefix' => env('RANK212_PUBLISH_ROUTE_PREFIX', 'api/rank212'),
        'blog_prefix' => env('RANK212_BLOG_PREFIX', 'blog'),
        'tolerance_seconds' => (int) env('RANK212_SIGNATURE_TOLERANCE', 300),
    ],
];
