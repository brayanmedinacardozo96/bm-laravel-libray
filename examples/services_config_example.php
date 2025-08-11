<?php

/**
 * Configuración para services.php de Laravel
 *
 * Agrega esta configuración a tu archivo config/services.php
 */

return [
    // ... otras configuraciones de servicios

    'api' => [
        'base_url' => env('API_BASE_URL', 'https://qa-enlinea.electrohuila.com.co:8013'),
        'timeout' => env('API_TIMEOUT', 60),
        'ssl_verify' => env('API_SSL_VERIFY', false), // false para QA, true para producción
        'ssl_options' => env('APP_ENV') === 'local' ? [
            'verify' => false,
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]
        ] : [],
    ],

    // Para múltiples APIs puedes configurar así:
    'electrohuila_api' => [
        'base_url' => env('ELECTROHUILA_API_URL', 'https://qa-enlinea.electrohuila.com.co:8013'),
        'timeout' => env('ELECTROHUILA_API_TIMEOUT', 60),
        'ssl_verify' => env('ELECTROHUILA_SSL_VERIFY', false),
    ],

    'production_api' => [
        'base_url' => env('PRODUCTION_API_URL', 'https://api.production.com'),
        'timeout' => env('PRODUCTION_API_TIMEOUT', 30),
        'ssl_verify' => env('PRODUCTION_SSL_VERIFY', true),
    ],
];
