<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | هنا تقدر تحدد إعدادات CORS الخاصة بتطبيقك.
    | أي دومين أو Origin هيكون مسموح بيه لما تستخدم ['*'].
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // ✅ يسمح لكل الـ origins

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // ✅ لو هتحتاج Cookies أو Tokens

];
