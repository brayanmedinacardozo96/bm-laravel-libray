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
}
