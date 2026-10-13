<?php

// Manager Library configuration from the environment (plan §2). No secret has a default value.
return [
    'jwt_secret' => env('JWT_SECRET'),
    'admin_username' => env('ADMIN_USERNAME'),
    'admin_password' => env('ADMIN_PASSWORD'),
    'covers_dir' => env('COVERS_DIR', storage_path('app/covers')),
    'cors_origin' => env('CORS_ORIGIN', 'http://localhost:5173'),
    'clock_offset_days' => (int) env('CLOCK_OFFSET_DAYS', 0),
];
