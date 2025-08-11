<?php

namespace BMCLibrary\Providers;

use BMCLibrary\AppHttpClient\Abstractions\HttpClientInterface;
use BMCLibrary\AppHttpClient\AppHttpClient;
use BMCLibrary\Contracts\ApiResponseInterface;
use BMCLibrary\Contracts\MediatorInterface;
use BMCLibrary\Mediator\Mediator;
use Illuminate\Support\ServiceProvider;
use BMCLibrary\Utils\ApiResponse;

class ConfigManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar ApiResponse como singleton
        $this->app->singleton(ApiResponseInterface::class, ApiResponse::class);
        $this->app->singleton('bm-library.api-response', ApiResponse::class);

        // Registrar Mediator como singleton
        $this->app->singleton(MediatorInterface::class, Mediator::class);
        $this->app->singleton('bm-library.mediator', Mediator::class);

        $this->app->singleton(HttpClientInterface::class, function ($app) {
            $client = new AppHttpClient();

            // Configurar desde el .env
            if ($baseUrl = config('services.api.base_url')) {
                $client->setBaseUrl($baseUrl);
            }

            if ($timeout = config('services.api.timeout')) {
                $client->setTimeout($timeout);
            }

            // Headers por defecto
            $client->setDefaultHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

            // Configuración SSL
            $sslVerify = config('services.api.ssl_verify', true);
            if (!$sslVerify) {
                $client->disableSSLVerification();
            }

            // Configurar opciones SSL personalizadas si existen
            if ($sslOptions = config('services.api.ssl_options')) {
                $client->setSSLOptions($sslOptions);
            }

            return $client;
        });
    }

    public function boot(): void
    {
        // Aquí puedes agregar configuraciones si las necesitas en el futuro
    }
}
