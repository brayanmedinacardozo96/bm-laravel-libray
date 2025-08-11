<?php

namespace BMCLibrary\AppHttpClient\Abstractions;

interface HttpClientInterface
{
    public function get(string $url, array $headers = []): HttpResponseInterface;
    public function post(string $url, array $data = [], array $headers = []): HttpResponseInterface;
    public function put(string $url, array $data = [], array $headers = []): HttpResponseInterface;
    public function delete(string $url, array $headers = []): HttpResponseInterface;
    public function setBaseUrl(string $baseUrl): self;
    public function setTimeout(int $timeout): self;
    public function setDefaultHeaders(array $headers): self;
    public function disableSSLVerification(): self;
    public function enableSSLVerification(): self;
    public function setSSLOptions(array $options): self;
}
