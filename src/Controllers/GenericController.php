<?php

namespace BMCLibrary\Controllers;

use BMCLibrary\Contracts\ApiResponseInterface;
use BMCLibrary\Contracts\MediatorInterface;

abstract class GenericController
{
    public function __construct(
        protected ApiResponseInterface $apiResponse,
        protected ?MediatorInterface $mediator = null
    ) {}

    /**
     * Get the API response instance
     *
     * @return ApiResponseInterface
     */
    protected function getApiResponse(): ApiResponseInterface
    {
        return $this->apiResponse;
    }

    /**
     * Get the mediator instance
     *
     * @return MediatorInterface|null
     */
    protected function getMediator(): ?MediatorInterface
    {
        return $this->mediator;
    }
}
