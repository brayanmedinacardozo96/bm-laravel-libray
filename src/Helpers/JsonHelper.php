<?php

namespace BMCLibrary\Helpers;

class JsonHelper
{
    /**
     * Verifica si un string es un JSON válido.
     *
     * @param string|null $value
     * @return bool
     */
    public static function isValid(?string $value): bool
    {
        if (empty($value) || !is_string($value)) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Intenta convertir un string JSON a array asociativo.
     *
     * @param string|null $value
     * @return array|null
     */
    public static function parse(?string $value): ?array
    {
        if (empty($value) || !is_string($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }
}
