<?php

namespace Tests\Examples;

use BMCLibrary\Repository\AutoModelRepository;
use BMCLibrary\Contracts\BaseRepositoryInterface;
use BMCLibrary\Models\Base\BaseModel;

// Ejemplo de modelo extendiendo BaseModel
class Parameter extends BaseModel
{
    protected $fillable = ['name', 'value', 'type', 'description'];
    
    // Ahora puedes usar Parameter::name() para obtener el nombre de la tabla
}

// Interfaz específica del repositorio
interface ParameterRepositoryInterface extends BaseRepositoryInterface
{
    // Métodos específicos para parámetros si los necesitas
    public function getByType(string $type): \Illuminate\Database\Eloquent\Collection;
}

// Implementación del repositorio
class ParameterRepository extends AutoModelRepository implements ParameterRepositoryInterface
{
    protected string $modelClass = Parameter::class;

    /**
     * Método específico para obtener parámetros por tipo
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getAllWhere(['type' => $type]);
    }
}

// Ejemplo de uso
class ParameterController
{
    private ParameterRepositoryInterface $parameterRepository;

    public function __construct(ParameterRepositoryInterface $parameterRepository)
    {
        $this->parameterRepository = $parameterRepository;
    }

    public function index()
    {
        // Todos los métodos de la interfaz están disponibles
        $allParameters = $this->parameterRepository->all(10);
        
        $activeParameters = $this->parameterRepository->getAllWhere(['status' => 'active']);
        
        $parameterOptions = $this->parameterRepository->getAllForSelect(['id', 'name']);
        
        // whereIn con array automático
        $specificParameters = $this->parameterRepository->whereQuery(['*'], [
            'id' => [1, 2, 3, 4, 5]  // Se convierte automáticamente en whereIn
        ])->get();
        
        // whereIn explícito con múltiples condiciones
        $complexQuery = $this->parameterRepository->whereQuery(['*'], [
            'type' => ['in', ['config', 'setting', 'option']],
            'status' => ['not_in', ['deleted', 'deprecated']],
            'created_at' => ['>=', '2024-01-01'],
            'value' => ['not_null']
        ])->paginate(15);
        
        return response()->json([
            'all' => $allParameters,
            'active' => $activeParameters,
            'options' => $parameterOptions,
            'specific' => $specificParameters,
            'complex' => $complexQuery
        ]);
    }

    public function getByType(string $type)
    {
        return $this->parameterRepository->getByType($type);
    }

    public function store(array $data)
    {
        return $this->parameterRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->parameterRepository->update($id, $data);
    }

    public function destroy(int $id)
    {
        return $this->parameterRepository->delete($id);
    }

    public function search(array $filters)
    {
        return $this->parameterRepository->search($filters);
    }
}
