<?php

namespace BMCLibrary\Traits;

use BMCLibrary\Repository\GenericRepository;

trait HasRepository
{
    protected GenericRepository $repository;

    /**
     * Set the repository instance
     *
     * @param GenericRepository $repository
     * @return self
     */
    public function setRepository(GenericRepository $repository): self
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * Get the repository instance
     *
     * @return GenericRepository
     */
    public function getRepository(): GenericRepository
    {
        return $this->repository;
    }
}
