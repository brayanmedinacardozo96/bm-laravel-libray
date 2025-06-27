<?php

namespace BMCLibrary\Contracts;

use BMCLibrary\Utils\Result;
use Illuminate\Http\JsonResponse;

interface ApiResponseInterface
{

    public function call(
        Result $result,
        ?int $status = null
    ): JsonResponse;

    /**
     * Set the success status of the response.
     *
     * @param bool $success
     * @return self
     */
    public function success(bool $success): self;

    /**
     * Set the message for the response.
     *
     * @param string $message
     * @return self
     */
    public function message(string $message): self;

    /**
     * Set the HTTP status code for the response.
     *
     * @param int $status
     * @return self
     */
    public function status(int $status): self;

    /**
     * Set the data for the response.
     *
     * @param mixed $data
     * @return self
     */
    public function data($data): self;

    /**
     * Set validation data for the response.
     *
     * @param mixed $data
     * @return self
     */
    public function validation($data): self;

    /**
     * Build and return the JSON response.
     *
     * @return JsonResponse
     */
    public function build(): JsonResponse;
}
