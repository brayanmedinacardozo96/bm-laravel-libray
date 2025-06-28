<?php

namespace BMCLibrary\Mediator;

use BMCLibrary\Utils\Result;

abstract class QueryHandler extends Handler
{
    /**
     * Handle a query
     *
     * @param object $request Should be a Query instance
     * @return Result
     */
    abstract public function handle(?object $request): Result;
}
