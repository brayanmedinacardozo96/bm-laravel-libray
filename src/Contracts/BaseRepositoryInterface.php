<?php

namespace BMCLibrary\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base interface for repositories without strict type constraints
 * Use this when you need flexibility with return types
 */
interface BaseRepositoryInterface
{
    /**
     * Get all records with pagination
     */
    public function all(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all records without pagination
     */
    public function getAllRecords();

    /**
     * Get all records matching conditions without pagination
     */
    public function getAllWhere(array $conditions);

    /**
     * Get all records for select dropdown without pagination
     */
    public function getAllForSelect(array $columns = ['id', 'name']);

    /**
     * Create a new record
     */
    public function create(array $data);

    /**
     * Insert multiple records
     */
    public function insert(array $data): bool;

    /**
     * Find a record by ID
     */
    public function find(mixed $id);

    /**
     * Update a record
     */
    public function update(mixed $id, array $data);

    /**
     * Delete a record
     */
    public function delete(mixed $id): bool;

    /**
     * Search records
     */
    public function search(array $search): LengthAwarePaginator;

    /**
     * Build query with where conditions
     */
    public function whereQuery(array $columns = ['*'], array $where = []);

    /**
     * Count records
     */
    public function count(array $conditions = []): int;

    /**
     * Get first record matching conditions
     */
    public function first(array $conditions = [], array $columns = ['*']);

    /**
     * Check if record exists by ID
     */
    public function exists(mixed $id): bool;

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
