<?php

namespace BMCLibrary\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface GenericRepositoryInterface
{
    /**
     * Get all records with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function all(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all records for select dropdown
     *
     * @param array $columns
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function allForSelect(array $columns = ['*'], int $perPage = 1000): LengthAwarePaginator;

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Insert multiple records
     *
     * @param array $data
     * @return bool
     */
    public function insert(array $data): bool;

    /**
     * Find a record by ID
     *
     * @param mixed $id
     * @return Model|null
     */
    public function find($id): ?Model;

    /**
     * Update a record
     *
     * @param mixed $id
     * @param array $data
     * @return Model|null
     */
    public function update($id, array $data): ?Model;

    /**
     * Delete a record
     *
     * @param mixed $id
     * @return bool
     */
    public function delete($id): bool;

    /**
     * Search records
     *
     * @param array $search
     * @return LengthAwarePaginator
     */
    public function search(array $search): LengthAwarePaginator;

    /**
     * Build query with where conditions
     *
     * @param array $columns
     * @param array $where
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function whereQuery(array $columns = ['*'], array $where = []): \Illuminate\Database\Eloquent\Builder;
}
