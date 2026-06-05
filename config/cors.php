<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure CORS settings for your application. This
    | configuration is used by the HandleCors middleware to handle
    | preflight requests and set proper CORS headers.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development
        'http://localhost:3000',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080',
        
        // Production - Update dengan domain mobile app Anda
        // 'https://yourmobileapp.com',
        // 'https://api.yourmobileapp.com',
    ],

    'allowed_origins_patterns' => [
        // Pattern untuk development dengan port dinamis
        // '#^http://localhost:\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
