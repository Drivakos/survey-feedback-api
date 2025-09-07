<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Elasticsearch connection used for logging survey submissions.
    |
    */

    'hosts' => env('ELASTICSEARCH_HOSTS', [
        'https://elasticsearch:9200', // Docker internal
        'http://localhost:9200',      // Local fallback
    ]),

    'index' => [
        'survey_submissions' => env('ELASTICSEARCH_SURVEY_SUBMISSIONS_INDEX', 'survey_submissions'),
        'prefix' => env('ELASTICSEARCH_INDEX_PREFIX', ''),
    ],

    'authentication' => [
        'username' => env('ELASTICSEARCH_USERNAME', null),
        'password' => env('ELASTICSEARCH_PASSWORD', null),
        'api_key' => env('ELASTICSEARCH_API_KEY', null),
    ],

    'ssl' => [
        'enabled' => env('ELASTICSEARCH_SSL_ENABLED', false),
        'verification' => env('ELASTICSEARCH_SSL_VERIFICATION', true),
        'ca_bundle' => env('ELASTICSEARCH_SSL_CA_BUNDLE', null),
    ],

    'retries' => env('ELASTICSEARCH_RETRIES', 2),

    'timeout' => env('ELASTICSEARCH_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | When Elasticsearch is unavailable, fall back to file logging.
    |
    */

    'fallback' => [
        'enabled' => env('ELASTICSEARCH_FALLBACK_ENABLED', true),
        'path' => env('ELASTICSEARCH_FALLBACK_PATH', storage_path('logs/elasticsearch-fallback')),
    ],
];