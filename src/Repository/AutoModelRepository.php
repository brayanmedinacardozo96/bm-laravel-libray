<?php

namespace BMCLibrary\Repository;

use BMCLibrary\Exceptions\RepositoryException;

abstract class AutoModelRepository extends GenericRepository
{
    protected string $modelClass;

    public function __construct()
    {
        if (!isset($this->modelClass)) {
            throw RepositoryException::modelClassNotDefined(static::class);
        }

        $model = app($this->modelClass);
        parent::__construct($model);
    }
}
