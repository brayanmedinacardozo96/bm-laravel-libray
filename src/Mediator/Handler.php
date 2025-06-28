<?php

namespace BMCLibrary\Mediator;

use BMCLibrary\Utils\Result;

abstract class Handler
{
    abstract public function handle(object $request): Result;
}
