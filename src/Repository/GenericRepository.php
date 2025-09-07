<?php

namespace BMCLibrary\Repository;

use BMCLibrary\Contracts\GenericRepositoryInterface;
use BMCLibrary\Traits\AdvancedRepositoryOperations;
use BMCLibrary\Traits\BasicRepositoryOperations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class GenericRepository implements GenericRepositoryInterface
{
    use AdvancedRepositoryOperations, BasicRepositoryOperations;

    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Create a repository instance for a specific model
     *
     * @param string $modelClass
     * @return static
     */
    public static function for(string $modelClass): static
    {
        return new static(app($modelClass));
    }

    /**
     * Create a repository instance with a model instance
     *
     * @param Model $model
     * @return static
     */
    public static function withModel(Model $model): static
    {
        return new static($model);
    }

    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function getAllRecords(): Collection
    {
        return $this->model->all();
    }

    public function getAllWhere(array $conditions): Collection
    {
        return $this->whereQuery(['*'], $conditions)->get();
    }

    public function getAllForSelect(array $columns = ['id', 'name']): Collection
    {
        return $this->model->select($columns)->get();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function insert(array $data): bool
    {
        return $this->model->insert($data);
    }

    public function find(mixed $id): ?Model
    {
        return $this->model->find($id);
    }

    public function update(mixed $id, array $data): ?Model
    {
        $record = $this->find($id);

        if ($record) {
            $record->update($data);
            return $record->fresh();
        }

        return null;
    }

    public function delete(mixed $id): bool
    {
        $record = $this->find($id);

        if ($record) {
            return $record->delete();
        }

        return false;
    }

    public function whereQuery(?array $columns = ['*'], array $where = []): EloquentBuilder
    {

        $query = $this->model;

        if (!empty($columns)) {
            $query = $this->model->select($columns);
        }

        foreach ($where as $column => $value) {
            $this->applyWhereCondition($query, $column, $value);
        }

        return $query;
    }

    /**
     * Apply where condition to query
     *
     * @param EloquentBuilder $query
     * @param string $column
     * @param mixed $value
     * @return void
     */
    protected function applyWhereCondition(EloquentBuilder $query, string $column, mixed $value): void
    {
        if (is_array($value)) {
            $this->applyArrayCondition($query, $column, $value);
        } else {
            $query->where($column, '=', $value);
        }
    }

    /**
     * Apply array-based where condition
     *
     * @param EloquentBuilder $query
     * @param string $column
     * @param array $value
     * @return void
     */
    protected function applyArrayCondition(EloquentBuilder $query, string $column, array $value): void
    {
        if (isset($value[0]) && is_string($value[0])) {
            $this->applyOperatorCondition($query, $column, $value);
        } else {
            // Si no hay operador, asumimos que es whereIn
            $query->whereIn($column, $value);
        }
    }

    /**
     * Apply operator-based condition
     *
     * @param EloquentBuilder $query
     * @param string $column
     * @param array $value
     * @return void
     */
    protected function applyOperatorCondition(EloquentBuilder $query, string $column, array $value): void
    {
        $operator = strtolower($value[0]);
        $conditionValue = $value[1] ?? null;

        switch ($operator) {
            case 'in':
                $query->whereIn($column, $conditionValue);
                break;
            case 'not_in':
            case 'notin':
                $query->whereNotIn($column, $conditionValue);
                break;
            case 'between':
                if (is_array($conditionValue) && count($conditionValue) === 2) {
                    $query->whereBetween($column, $conditionValue);
                }
                break;
            case 'not_between':
            case 'notbetween':
                if (is_array($conditionValue) && count($conditionValue) === 2) {
                    $query->whereNotBetween($column, $conditionValue);
                }
                break;
            case 'null':
                $query->whereNull($column);
                break;
            case 'not_null':
            case 'notnull':
                $query->whereNotNull($column);
                break;
            case 'like':
                $query->where($column, 'LIKE', $conditionValue);
                break;
            case 'ilike':
                $query->where($column, 'ILIKE', $conditionValue);
                break;
            default:
                // Operadores estándar: =, !=, <, >, <=, >=, etc.
                $query->where($column, $operator, $conditionValue);
                break;
        }
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
     * Check if record exists
     *
     * @param mixed $id
     * @return bool
     */
    public function exists(mixed $id): bool
    {
        return $this->model->where($this->model->getKeyName(), $id)->exists();
    }

    public function model(): Model
    {
        return $this->model;
    }
}
