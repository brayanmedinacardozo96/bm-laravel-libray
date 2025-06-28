<?php

namespace BMCLibrary\Mediator;

use BMCLibrary\Utils\Result;

abstract class CommandHandler extends Handler
{
    /**
     * Handle a command
     *
     * @param object $request Should be a Command instance
     * @return Result
     */
    abstract public function handle(object $request): Result;
}
