<?php

namespace BMCLibrary\Http\Requests\Base;

use BMCLibrary\Contracts\ApiResponseInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class GeneralFormRequest extends FormRequest
{
    protected ApiResponseInterface $response;

    public function __construct(ApiResponseInterface $response)
    {
        $this->response = $response;
        parent::__construct();
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->response->status(422)
                ->validation($validator->errors())
                ->success(false)
                ->build()
        );
    }

    protected function validateJsonField(string $attribute, $value, $fail): void
    {
        if ($value === null) {
            return; // Campo nullable
        }

        // Si es un array/objeto, es válido
        if (is_array($value)) {
            return;
        }

        // Si es string, validar que sea JSON válido
        if (is_string($value)) {
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $fail("El campo {$attribute} debe ser un objeto válido o una cadena JSON válida.");
            }
            return;
        }

        // Si no es array ni string, es inválido
        $fail("El campo {$attribute} debe ser un objeto o una cadena JSON válida.");
    }
}
