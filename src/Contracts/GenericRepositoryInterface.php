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
     * Get all records without pagination
     *
     * @return Collection
     */
    public function getAllRecords(): Collection;

    /**
     * Get all records matching conditions without pagination
     *
     * @param array $conditions
     * @return Collection
     */
    public function getAllWhere(array $conditions): Collection;

    /**
     * Get all records for select dropdown without pagination
     *
     * @param array $columns
     * @return Collection
     */
    public function getAllForSelect(array $columns = ['id', 'name']): Collection;

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
    public function find(mixed $id): ?Model;

    /**
     * Update a record
     *
     * @param mixed $id
     * @param array $data
     * @return Model|null
     */
    public function update(mixed $id, array $data): ?Model;

    /**
     * Delete a record
     *
     * @param mixed $id
     * @return bool
     */
    public function delete(mixed $id): bool;

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
    public function whereQuery(?array $columns = ['*'], array $where = []): \Illuminate\Database\Eloquent\Builder;

    /**
     * Update or create a record with separate parameters
     */
    public function updateOrCreateWithFilters(array $attributes, array $filters);

    /**
     * Update or create a record (alternative method)
     */
    public function updateOrCreateRecord(array $attributes, array $filters);

    /**
     * Update multiple records matching conditions
     *
     * @param array $conditions  Condiciones para filtrar los registros
     * @param array $data        Datos a actualizar
     * @return int               Número de registros afectados
     */
    public function updateWhere(array $conditions, array $data): int;
}
