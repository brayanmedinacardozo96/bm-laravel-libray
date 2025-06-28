<?php

namespace BMCLibrary\Mediator;

use BMCLibrary\Contracts\MediatorInterface;
use BMCLibrary\Exceptions\MediatorException;
use Illuminate\Container\Container;

class Mediator implements MediatorInterface
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function send(object $request)
    {
        $handlerClass = get_class($request) . 'Handler';
        
        if (!class_exists($handlerClass)) {
            throw MediatorException::handlerNotFound($handlerClass, get_class($request));
        }
        
        $handler = $this->container->make($handlerClass);
        
        if (!method_exists($handler, 'handle')) {
            throw MediatorException::handlerMethodNotFound($handlerClass);
        }
        
        return $handler->handle($request);
    }
}
