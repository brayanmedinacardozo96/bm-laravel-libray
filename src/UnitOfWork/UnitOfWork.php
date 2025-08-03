<?php

namespace BMCLibrary\UnitOfWork;

use Illuminate\Support\Facades\DB;

class UnitOfWork implements UnitOfWorkInterface
{
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    public function commit(): void
    {
        DB::commit();
    }

    public function rollback(): void
    {
        DB::rollBack();
    }

    /**
     * Ejecuta un callback dentro de una transacción
     * Si ocurre una excepción, se hace rollback automáticamente
     */
    public function execute(callable $callback)
    {
        return DB::transaction($callback);
    }
}
