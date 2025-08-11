<?php

namespace BMCLibrary\AppHttpClient\Abstractions;

interface HttpResponseInterface
{
    public function getStatusCode(): int;
    public function getBody(): string;
    public function getHeaders(): array;
    public function isSuccessful(): bool;
    public function toArray(): array;
    public function toObject(): object;
}
