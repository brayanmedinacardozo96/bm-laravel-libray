<?php

namespace BMCLibrary\UnitOfWork;

interface UnitOfWorkInterface
{
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
    public function execute(callable $callback);
}
