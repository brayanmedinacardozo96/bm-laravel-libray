<?php

namespace BMCLibrary\Exceptions;

use Exception;

class RepositoryException extends Exception
{
    public static function modelClassNotDefined(string $repositoryClass): self
    {
        return new self("Property \$modelClass must be defined in [{$repositoryClass}]");
    }
}
