<?php

namespace BMCLibrary\Traits;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait for basic repository search and query operations
 */
trait BasicRepositoryOperations
{
    /**
     * Get first record matching conditions
     *
     * @param array $conditions
     * @param array $columns
     * @return Model|null
     */
    public function first(array $conditions = [], array $columns = ['*']): ?Model
    {
        return $this->whereQuery($columns, $conditions)->first();
    }

    /**
     * Get first record or fail
     *
     * @param array $conditions
     * @param array $columns
     * @return Model
     * @throws ModelNotFoundException
     */
    public function firstOrFail(array $conditions = [], array $columns = ['*']): Model
    {
        return $this->whereQuery($columns, $conditions)->firstOrFail();
    }

    /**
     * Check if any record exists matching conditions
     *
     * @param array $conditions
     * @return bool
     */
    public function existsWhere(array $conditions = []): bool
    {
        return $this->whereQuery(['*'], $conditions)->exists();
    }

    /**
     * Get count of records
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            return $this->model->count();
        }

        return $this->whereQuery(['*'], $conditions)->count();
    }

    /**
     * Search with pagination
     *
     * @param array $search
     * @return LengthAwarePaginator
     */
    public function search(array $search): LengthAwarePaginator
    {
        $perPage = $search['per_page'] ?? 15;
        $searchData = $search['filters'] ?? [];

        return $this->model::when(!empty($searchData), function ($query) use ($searchData) {
            foreach ($searchData as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $query->whereHas($key, function (EloquentBuilder $query) use ($subKey, $subValue) {
                            $this->buildQuery($query, $subKey, $subValue);
                        });
                    }
                } else {
                    $this->buildQuery($query, $key, $value);
                }
            }
        })->paginate($perPage);
    }

    /**
     * Build query conditions
     *
     * @param EloquentBuilder $query
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function buildQuery(EloquentBuilder $query, string $key, mixed $value): void
    {
        if (is_array($value)) {
            $query->whereIn($key, $value);
        } else {
            $query->where($key, 'like', '%' . $value . '%');
        }
    }

    /**
     * Get all records without pagination
     *
     * @param array $conditions
     * @param array $columns
     * @return Collection
     */
    public function getAllRecords(array $conditions = [], array $columns = ['*']): Collection
    {
        return $this->whereQuery($columns, $conditions)->get();
    }

    /**
     * Get all records where conditions match
     *
     * @param array $where
     * @param array $columns
     * @return Collection
     */
    public function getAllWhere(array $where, array $columns = ['*']): Collection
    {
        return $this->whereQuery($columns, $where)->get();
    }

    /**
     * Get all records for select dropdown (id, name)
     *
     * @param array $conditions
     * @param string $valueField
     * @param string $textField
     * @return Collection
     */
    public function getAllForSelect(array $conditions = [], string $valueField = 'id', string $textField = 'name'): Collection
    {
        return $this->whereQuery([$valueField, $textField], $conditions)->get();
    }

    /**
     * Get records with specific conditions and pagination
     *
     * @param array $conditions
     * @param array $columns
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getWhere(array $conditions, array $columns = ['*'], int $perPage = 15): LengthAwarePaginator
    {
        return $this->whereQuery($columns, $conditions)->paginate($perPage);
    }

    /**
     * Get first record matching conditions
     *
     * @param array $conditions
     * @return Model|null
     */
    public function getFirstWhere(array $conditions): ?Model
    {
        return $this->whereQuery(['*'], $conditions)->first();
    }

    /**
     * Build select query
     *
     * @param array $columns
     * @return EloquentBuilder
     */
    public function buildSelect(array $columns = ['*']): EloquentBuilder
    {
        return $this->model->select($columns);
    }

    /**
     * Get the model instance
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set a new model instance
     *
     * @param Model $model
     * @return self
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;
        return $this;
    }
}
