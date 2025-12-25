<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_origins' => ['https://jomlet.netlify.app'],
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'Origin'],
    'exposed_headers' => ['Authorization'],
    'supports_credentials' => true,
];
