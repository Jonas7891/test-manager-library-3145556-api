<?php

// Configuración por entorno de Manager Library (plan §2). Ningún secreto tiene valor por defecto.
return [
    'jwt_secret' => env('JWT_SECRET'),
    'admin_username' => env('ADMIN_USERNAME'),
    'admin_password' => env('ADMIN_PASSWORD'),
    'covers_dir' => env('COVERS_DIR', storage_path('app/covers')),
    'cors_origin' => env('CORS_ORIGIN', 'http://localhost:5173'),
    'clock_offset_days' => (int) env('CLOCK_OFFSET_DAYS', 0),
];
