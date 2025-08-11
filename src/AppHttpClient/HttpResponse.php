<?php

namespace BMCLibrary\AppHttpClient;

use BMCLibrary\AppHttpClient\Abstractions\HttpResponseInterface;

class HttpResponse implements HttpResponseInterface
{
    public function __construct(
        private int $statusCode,
        private string $body,
        private array $headers = []
    ) {}

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function toArray(): array
    {
        $decoded = json_decode($this->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Response body is not valid JSON');
        }

        return $decoded ?? [];
    }

    public function toObject(): object
    {
        $decoded = json_decode($this->body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Response body is not valid JSON');
        }

        return $decoded ?? new \stdClass();
    }
}
