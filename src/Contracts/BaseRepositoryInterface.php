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
     * Get all records for select dropdown
     */
    public function allForSelect(array $columns = ['*'], int $perPage = 1000): LengthAwarePaginator;

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
}
