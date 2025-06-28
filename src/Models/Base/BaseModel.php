<?php

namespace BMCLibrary\Models\Base;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * Get the table name for the model
     *
     * @return string
     */
    public static function name(): string
    {
        return with(new static)->getTable();
    }
}
