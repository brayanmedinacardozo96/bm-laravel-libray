<?php

namespace BMCLibrary\Utils;

use BMCLibrary\AppHttpClient\AppHttpClient;

class HttpClientFactory
{
    /**
     * Crear cliente HTTP para ElectroHuila
     */
    public static function createElectroHuilaClient(): AppHttpClient
    {
        $config = config('httpclient.apis.electrohuila');
        
        $client = new AppHttpClient();
        $client->setBaseUrl($config['base_url'])
               ->setTimeout($config['timeout'])
               ->setDefaultHeaders(config('httpclient.default_headers'));

        if (!$config['ssl_verify']) {
            $client->disableSSLVerification();
        }

        return $client;
    }

    /**
     * Crear cliente HTTP genérico
     */
    public static function createClient(string $apiKey = 'default'): AppHttpClient
    {
        $config = config("httpclient.apis.{$apiKey}");
        
        if (!$config) {
            throw new \InvalidArgumentException("API configuration '{$apiKey}' not found");
        }

        $client = new AppHttpClient();
        $client->setBaseUrl($config['base_url'])
               ->setTimeout($config['timeout'])
               ->setDefaultHeaders(config('httpclient.default_headers'));

        if (!$config['ssl_verify']) {
            $client->disableSSLVerification();
        }

        return $client;
    }

    /**
     * Crear cliente con configuración personalizada
     */
    public static function createCustomClient(
        string $baseUrl,
        bool $sslVerify = true,
        int $timeout = 30,
        array $headers = []
    ): AppHttpClient {
        $client = new AppHttpClient();
        $client->setBaseUrl($baseUrl)
               ->setTimeout($timeout)
               ->setDefaultHeaders(array_merge(
                   config('httpclient.default_headers'),
                   $headers
               ));

        if (!$sslVerify) {
            $client->disableSSLVerification();
        }

        return $client;
    }
}
