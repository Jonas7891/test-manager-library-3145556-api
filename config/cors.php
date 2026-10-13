<?php

// api-contract §4: solo el origen del frontend configurado por entorno.
return [
    'paths' => ['api/*', 'health'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('CORS_ORIGIN', 'http://localhost:5173')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
