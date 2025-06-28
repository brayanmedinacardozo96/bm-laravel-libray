<?php

namespace BMCLibrary\Repository;

use BMCLibrary\Contracts\GenericRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class GenericRepository implements GenericRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function allForSelect(array $columns = ['*'], int $perPage = 1000): LengthAwarePaginator
    {
        return $this->model->select($columns)->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function insert(array $data): bool
    {
        return $this->model->insert($data);
    }

    public function find($id): ?Model
    {
        return $this->model->find($id);
    }

    public function update($id, array $data): ?Model
    {
        $record = $this->find($id);

        if ($record) {
            $record->update($data);
            return $record->fresh();
        }

        return null;
    }

    public function delete($id): bool
    {
        $record = $this->find($id);

        if ($record) {
            return $record->delete();
        }

        return false;
    }

    public function whereQuery(array $columns = ['*'], array $where = []): EloquentBuilder
    {
        $query = $this->model->select($columns);

        foreach ($where as $column => $value) {
            if (is_array($value)) {
                $operator = $value[0] ?? '=';
                $conditionValue = $value[1] ?? null;
                $query->where($column, $operator, $conditionValue);
            } else {
                $query->where($column, '=', $value);
            }
        }

        return $query;
    }

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
     * @return EloquentBuilder
     */
    protected function buildQuery(EloquentBuilder $query, string $key, $value): EloquentBuilder
    {
        if ($key === 'id') {
            return $query->where($key, $value);
        }

        return $query->where($key, 'like', '%' . $value . '%');
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

    /**
     * Get records with specific conditions
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
     * Check if record exists
     *
     * @param mixed $id
     * @return bool
     */
    public function exists($id): bool
    {
        return $this->model->where($this->model->getKeyName(), $id)->exists();
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
}
