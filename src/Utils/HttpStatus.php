<?php

namespace BMCLibrary\Utils;

class HttpStatus
{
    public const OK = 200;
    public const CREATED = 201;
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const UNPROCESSABLE_ENTITY = 422;
    public const SERVER_ERROR = 500;

    public static function message(int $status): string
    {
        return match ($status) {
            self::OK => 'OK',
            self::CREATED => 'Created',
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            self::SERVER_ERROR => 'Internal Server Error',
            default => 'Unknown Status'
        };
    }
}
