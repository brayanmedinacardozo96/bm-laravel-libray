<?php

namespace BMCLibrary\Exceptions;

use Exception;

class MediatorException extends Exception
{
    public static function handlerNotFound(string $handlerClass, string $requestClass): self
    {
        return new self("Handler class [{$handlerClass}] does not exist for request [{$requestClass}]");
    }
    
    public static function handlerMethodNotFound(string $handlerClass): self
    {
        return new self("Handler class [{$handlerClass}] must have a handle method");
    }
}
