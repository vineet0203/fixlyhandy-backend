<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ipinfo' => [
        'token' => env('IPINFO_TOKEN'),
    ],

    'geolocation' => [
        'cache_ttl' => env('GEOLOCATION_CACHE_TTL', 86400), // 24 hours
        'enabled' => env('GEOLOCATION_ENABLED', true),
        'services' => [
            'ipinfo' => [
                'enabled' => env('IPINFO_ENABLED', true),
                'priority' => 1,
            ],
            'freeipapi' => [
                'enabled' => env('FREEIPAPI_ENABLED', true),
                'priority' => 2,
            ],
            'ipapi' => [
                'enabled' => env('IPAPI_ENABLED', true),
                'priority' => 3,
            ],
        ],
    ],

];
