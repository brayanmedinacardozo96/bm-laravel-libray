<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el cliente HTTP AppHttpClient
    |
    */

    'default_timeout' => env('HTTP_TIMEOUT', 30),
    
    'ssl_verify' => env('HTTP_SSL_VERIFY', true),
    
    'default_headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'User-Agent' => 'Laravel-App/1.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | URLs base para diferentes APIs
    |
    */
    
    'apis' => [
        'electrohuila' => [
            'base_url' => env('ELECTROHUILA_API_URL', 'https://qa-enlinea.electrohuila.com.co:8013'),
            'timeout' => env('ELECTROHUILA_API_TIMEOUT', 60),
            'ssl_verify' => env('ELECTROHUILA_SSL_VERIFY', false), // Desactivado para QA
        ],
        
        'production_api' => [
            'base_url' => env('PRODUCTION_API_URL', 'https://api.production.com'),
            'timeout' => env('PRODUCTION_API_TIMEOUT', 30),
            'ssl_verify' => env('PRODUCTION_SSL_VERIFY', true), // Activado para producción
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL Options
    |--------------------------------------------------------------------------
    |
    | Opciones SSL para diferentes entornos
    |
    */
    
    'ssl_options' => [
        'development' => [
            'verify' => false,
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]
        ],
        
        'production' => [
            'verify' => true,
            // 'cafile' => '/path/to/cacert.pem',
            // 'cert' => '/path/to/client.pem',
            // 'ssl_key' => '/path/to/client.key',
        ],
    ],
];
