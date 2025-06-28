<?php

namespace BMCLibrary\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for advanced repository operations
 */
trait AdvancedRepositoryOperations
{
    /**
     * Get distinct values for a column
     *
     * @param string $column
     * @param array $conditions
     * @return Collection
     */
    public function distinct(string $column, array $conditions = []): Collection
    {
        return $this->whereQuery([$column], $conditions)
                    ->distinct()
                    ->pluck($column);
    }

    /**
     * Get maximum value of a column
     *
     * @param string $column
     * @param array $conditions
     * @return mixed
     */
    public function max(string $column, array $conditions = []): mixed
    {
        return $this->whereQuery(['*'], $conditions)->max($column);
    }

    /**
     * Get minimum value of a column
     *
     * @param string $column
     * @param array $conditions
     * @return mixed
     */
    public function min(string $column, array $conditions = []): mixed
    {
        return $this->whereQuery(['*'], $conditions)->min($column);
    }

    /**
     * Get average value of a column
     *
     * @param string $column
     * @param array $conditions
     * @return mixed
     */
    public function avg(string $column, array $conditions = []): mixed
    {
        return $this->whereQuery(['*'], $conditions)->avg($column);
    }

    /**
     * Get sum of a column
     *
     * @param string $column
     * @param array $conditions
     * @return mixed
     */
    public function sum(string $column, array $conditions = []): mixed
    {
        return $this->whereQuery(['*'], $conditions)->sum($column);
    }

    /**
     * Update multiple records matching conditions
     *
     * @param array $conditions
     * @param array $data
     * @return int Number of affected rows
     */
    public function updateWhere(array $conditions, array $data): int
    {
        return $this->whereQuery(['*'], $conditions)->update($data);
    }

    /**
     * Delete multiple records matching conditions
     *
     * @param array $conditions
     * @return int Number of affected rows
     */
    public function deleteWhere(array $conditions): int
    {
        return $this->whereQuery(['*'], $conditions)->delete();
    }

    /**
     * Find or create a record
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->firstOrCreate($attributes, $values);
    }

    /**
     * Update or create a record
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Insert multiple records at once
     *
     * @param array $data
     * @return bool
     */
    public function insertBatch(array $data): bool
    {
        return $this->model->insert($data);
    }

    /**
     * Get random records
     *
     * @param int $count
     * @param array $conditions
     * @return Collection
     */
    public function random(int $count = 1, array $conditions = []): Collection
    {
        return $this->whereQuery(['*'], $conditions)
                    ->inRandomOrder()
                    ->limit($count)
                    ->get();
    }

    /**
     * Get latest records
     *
     * @param int $count
     * @param string $column
     * @param array $conditions
     * @return Collection
     */
    public function latest(int $count = 10, string $column = 'created_at', array $conditions = []): Collection
    {
        return $this->whereQuery(['*'], $conditions)
                    ->latest($column)
                    ->limit($count)
                    ->get();
    }

    /**
     * Get oldest records
     *
     * @param int $count
     * @param string $column
     * @param array $conditions
     * @return Collection
     */
    public function oldest(int $count = 10, string $column = 'created_at', array $conditions = []): Collection
    {
        return $this->whereQuery(['*'], $conditions)
                    ->oldest($column)
                    ->limit($count)
                    ->get();
    }

    /**
     * Chunk through large datasets
     *
     * @param int $chunkSize
     * @param callable $callback
     * @param array $conditions
     * @return bool
     */
    public function chunk(int $chunkSize, callable $callback, array $conditions = []): bool
    {
        return $this->whereQuery(['*'], $conditions)->chunk($chunkSize, $callback);
    }
}
