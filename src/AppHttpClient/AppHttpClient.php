<?php


namespace BMCLibrary\AppHttpClient;

use BMCLibrary\AppHttpClient\Abstractions\HttpClientInterface;
use BMCLibrary\AppHttpClient\Abstractions\HttpResponseInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class AppHttpClient implements HttpClientInterface
{
    private string $baseUrl = '';
    private int $timeout = 30;
    private array $defaultHeaders = [];
    private bool $verifySSL = true;
    private array $sslOptions = [];

    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;
        return $this;
    }

    public function disableSSLVerification(): self
    {
        $this->verifySSL = false;
        return $this;
    }

    public function enableSSLVerification(): self
    {
        $this->verifySSL = true;
        return $this;
    }

    public function setSSLOptions(array $options): self
    {
        $this->sslOptions = $options;
        return $this;
    }

    public function get(string $url, array $headers = []): HttpResponseInterface
    {
        try {
            $response = $this->buildHttpClient()
                ->withHeaders($this->mergeHeaders($headers))
                ->get($this->buildUrl($url));

            return new HttpResponse(
                $response->status(),
                $response->body(),
                $response->headers()
            );
        } catch (RequestException $e) {
            throw new \RuntimeException('HTTP GET request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function post(string $url, array $data = [], array $headers = []): HttpResponseInterface
    {
        try {
            $response = $this->buildHttpClient()
                ->withHeaders($this->mergeHeaders($headers))
                ->post($this->buildUrl($url), $data);

            return new HttpResponse(
                $response->status(),
                $response->body(),
                $response->headers()
            );
        } catch (RequestException $e) {
            throw new \RuntimeException('HTTP POST request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function put(string $url, array $data = [], array $headers = []): HttpResponseInterface
    {
        try {
            $response = $this->buildHttpClient()
                ->withHeaders($this->mergeHeaders($headers))
                ->put($this->buildUrl($url), $data);

            return new HttpResponse(
                $response->status(),
                $response->body(),
                $response->headers()
            );
        } catch (RequestException $e) {
            throw new \RuntimeException('HTTP PUT request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function delete(string $url, array $headers = []): HttpResponseInterface
    {
        try {
            $response = $this->buildHttpClient()
                ->withHeaders($this->mergeHeaders($headers))
                ->delete($this->buildUrl($url));

            return new HttpResponse(
                $response->status(),
                $response->body(),
                $response->headers()
            );
        } catch (RequestException $e) {
            throw new \RuntimeException('HTTP DELETE request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function buildHttpClient()
    {
        $client = Http::timeout($this->timeout);

        if (!$this->verifySSL) {
            $client = $client->withOptions([
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]
            ]);
        }

        if (!empty($this->sslOptions)) {
            $client = $client->withOptions($this->sslOptions);
        }

        return $client;
    }

    private function buildUrl(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        return $this->baseUrl . '/' . ltrim($url, '/');
    }

    private function mergeHeaders(array $headers): array
    {
        return array_merge($this->defaultHeaders, $headers);
    }
}
