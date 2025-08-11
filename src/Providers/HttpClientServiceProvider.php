<?php

namespace BMCLibrary\Providers;

use BMCLibrary\AppHttpClient\Abstractions\HttpClientInterface;
use BMCLibrary\AppHttpClient\AppHttpClient;
use Illuminate\Support\ServiceProvider;

class HttpClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Cliente HTTP por defecto
        $this->app->singleton(HttpClientInterface::class, function ($app) {
            return $this->createHttpClient('api');
        });

        // Cliente específico para ElectroHuila
        $this->app->singleton('httpclient.electrohuila', function ($app) {
            return $this->createHttpClient('electrohuila_api');
        });

        // Cliente específico para API de producción
        $this->app->singleton('httpclient.production', function ($app) {
            return $this->createHttpClient('production_api');
        });

        // Alias para facilitar el uso
        $this->app->alias(HttpClientInterface::class, 'httpclient.default');
    }

    public function boot(): void
    {
        // Publicar configuración si necesitas
        $this->publishes([
            __DIR__.'/../../config/httpclient.php' => config_path('httpclient.php'),
        ], 'httpclient-config');
    }

    /**
     * Crear cliente HTTP con configuración específica
     */
    private function createHttpClient(string $configKey): HttpClientInterface
    {
        $config = config("services.{$configKey}");
        
        if (!$config) {
            throw new \InvalidArgumentException("Configuration for '{$configKey}' not found in services.php");
        }

        $client = new AppHttpClient();

        // Configurar URL base
        if (isset($config['base_url'])) {
            $client->setBaseUrl($config['base_url']);
        }

        // Configurar timeout
        if (isset($config['timeout'])) {
            $client->setTimeout($config['timeout']);
        }

        // Headers por defecto
        $defaultHeaders = $config['headers'] ?? [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $client->setDefaultHeaders($defaultHeaders);

        // Configuración SSL
        $sslVerify = $config['ssl_verify'] ?? true;
        if (!$sslVerify) {
            $client->disableSSLVerification();
        }

        // Opciones SSL personalizadas
        if (isset($config['ssl_options']) && !empty($config['ssl_options'])) {
            $client->setSSLOptions($config['ssl_options']);
        }

        return $client;
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            HttpClientInterface::class,
            'httpclient.default',
            'httpclient.electrohuila',
            'httpclient.production',
        ];
    }
}
