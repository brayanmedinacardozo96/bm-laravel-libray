<?php

namespace BMCLibrary\Utils;

class Result
{
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $error = null,
        public ?string $message = null,
        public ?int $status = null
    ) {}

    public static function ok(mixed $data = null, string $message = ""): self
    {
        return new self(true, $data, message: $message);
    }

    public static function fail(string $error, int $status = 500): self
    {
        return new self(success: false, data: null, error: $error, status: $status);
    }
}
