<?php

namespace BMCLibrary\Facades;

use Illuminate\Support\Facades\Facade;

class ApiResponse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bm-library.api-response';
    }
}
