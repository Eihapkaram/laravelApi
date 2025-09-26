<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure settings for cross-origin resource sharing or "CORS".
    | This controls what cross-origin operations are allowed in browsers.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // السماح بكل الـ methods (GET, POST, PUT, DELETE ...)
    'allowed_methods' => ['*'],

    // تقدر تحدد دومينات معينة بدل * لو حبيت
    'allowed_origins' => [
        'http://192.168.1.8:8080', // Vue dev server
        'http://localhost:8080',   // Vue محلي
        'https://web-production-3711f.up.railway.app', // API Railway
        '*' // fallback
    ],

    'allowed_origins_patterns' => [],

    // السماح بكل الهيدرز
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // لو بتحتاج ترسل cookies أو Authorization header
    'supports_credentials' => true,
];
