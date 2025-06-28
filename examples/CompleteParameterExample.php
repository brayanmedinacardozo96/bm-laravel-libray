<?php

namespace Tests\Examples\Complete;

use BMCLibrary\Repository\AutoModelRepository;
use BMCLibrary\Contracts\BaseRepositoryInterface;
use BMCLibrary\Models\Base\BaseModel;
use BMCLibrary\Http\Requests\Base\GeneralFormRequest;
use BMCLibrary\Contracts\ApiResponseInterface;
use Illuminate\Http\JsonResponse;

// =====================================================
// CONSTANTES
// =====================================================

class ParameterConstants
{
    public const PARAMETER_NOT_FOUND = 'Parámetro no encontrado';
    public const PARAMETER_CREATED = 'Parámetro creado exitosamente';
    public const PARAMETER_UPDATED = 'Parámetro actualizado exitosamente';
    public const PARAMETER_DELETED = 'Parámetro eliminado exitosamente';
}

// =====================================================
// MODELO CON BaseModel
// =====================================================

class Parameter extends BaseModel
{
    protected $fillable = ['name', 'value', 'type', 'description', 'status'];
    
    protected $casts = [
        'value' => 'json'
    ];
    
    // Ahora puedes usar Parameter::name() para obtener 'parameters'
    // útil para queries dinámicas o logs
}

// =====================================================
// INTERFAZ DEL REPOSITORIO
// =====================================================

interface ParameterRepositoryInterface extends BaseRepositoryInterface
{
    public function getByType(string $type): \Illuminate\Database\Eloquent\Collection;
    public function getActiveParameters(): \Illuminate\Database\Eloquent\Collection;
    public function getParametersByTypes(array $types): \Illuminate\Database\Eloquent\Collection;
}

// =====================================================
// REPOSITORIO
// =====================================================

class ParameterRepository extends AutoModelRepository implements ParameterRepositoryInterface
{
    protected string $modelClass = Parameter::class;

    public function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getAllWhere(['type' => $type]);
    }

    public function getActiveParameters(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getAllWhere(['status' => 'active']);
    }

    public function getParametersByTypes(array $types): \Illuminate\Database\Eloquent\Collection
    {
        // Usando whereIn automático
        return $this->whereQuery(['*'], [
            'type' => $types,  // Se convierte automáticamente en whereIn
            'status' => 'active'
        ])->get();
    }
}

// =====================================================
// FORM REQUESTS
// =====================================================

class CreateParameterRequest extends GeneralFormRequest
{
    public function authorize(): bool
    {
        return true; // o tu lógica de autorización
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:' . Parameter::name() . ',name',
            'value' => 'required',
            'type' => 'required|in:config,setting,option,feature',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del parámetro es obligatorio.',
            'name.unique' => 'Ya existe un parámetro con este nombre.',
            'value.required' => 'El valor del parámetro es obligatorio.',
            'type.required' => 'El tipo de parámetro es obligatorio.',
            'type.in' => 'El tipo debe ser: config, setting, option o feature.',
            'status.in' => 'El estado debe ser activo o inactivo.'
        ];
    }
}

class UpdateParameterRequest extends GeneralFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $parameterId = $this->route('parameter')?->id;
        
        return [
            'name' => 'required|string|max:255|unique:' . Parameter::name() . ',name,' . $parameterId,
            'value' => 'required',
            'type' => 'required|in:config,setting,option,feature',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del parámetro es obligatorio.',
            'name.unique' => 'Ya existe otro parámetro con este nombre.',
            'value.required' => 'El valor del parámetro es obligatorio.',
            'type.required' => 'El tipo de parámetro es obligatorio.',
            'type.in' => 'El tipo debe ser: config, setting, option o feature.',
            'status.in' => 'El estado debe ser activo o inactivo.'
        ];
    }
}

// =====================================================
// CONTROLADOR
// =====================================================

class ParameterController
{
    public function __construct(
        private ParameterRepositoryInterface $parameterRepository,
        private ApiResponseInterface $response
    ) {}

    public function index(): JsonResponse
    {
        $parameters = $this->parameterRepository->all(15);
        
        return $this->response
            ->message('Parámetros obtenidos exitosamente')
            ->data($parameters)
            ->build();
    }

    public function store(CreateParameterRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $parameter = $this->parameterRepository->create($validatedData);
        
        return $this->response
            ->status(201)
            ->message(ParameterConstants::PARAMETER_CREATED)
            ->data($parameter)
            ->build();
    }

    public function show(int $id): JsonResponse
    {
        $parameter = $this->parameterRepository->find($id);
        
        if (!$parameter) {
            return $this->response
                ->status(404)
                ->message(ParameterConstants::PARAMETER_NOT_FOUND)
                ->success(false)
                ->build();
        }

        return $this->response
            ->message('Parámetro obtenido exitosamente')
            ->data($parameter)
            ->build();
    }

    public function update(UpdateParameterRequest $request, int $id): JsonResponse
    {
        $validatedData = $request->validated();
        $parameter = $this->parameterRepository->update($id, $validatedData);
        
        if (!$parameter) {
            return $this->response
                ->status(404)
                ->message(ParameterConstants::PARAMETER_NOT_FOUND)
                ->success(false)
                ->build();
        }

        return $this->response
            ->message(ParameterConstants::PARAMETER_UPDATED)
            ->data($parameter)
            ->build();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->parameterRepository->delete($id);
        
        if (!$deleted) {
            return $this->response
                ->status(404)
                ->message(ParameterConstants::PARAMETER_NOT_FOUND)
                ->success(false)
                ->build();
        }

        return $this->response
            ->message(ParameterConstants::PARAMETER_DELETED)
            ->build();
    }

    public function getByType(string $type): JsonResponse
    {
        $parameters = $this->parameterRepository->getByType($type);
        
        return $this->response
            ->message('Parámetros del tipo "' . $type . '" obtenidos exitosamente')
            ->data($parameters)
            ->build();
    }

    public function getActive(): JsonResponse
    {
        $parameters = $this->parameterRepository->getActiveParameters();
        
        return $this->response
            ->message('Parámetros activos obtenidos exitosamente')
            ->data($parameters)
            ->build();
    }

    public function search(): JsonResponse
    {
        $filters = request()->validate([
            'filters' => 'nullable|array',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $parameters = $this->parameterRepository->search($filters);
        
        return $this->response
            ->message('Búsqueda de parámetros realizada exitosamente')
            ->data($parameters)
            ->build();
    }

    public function advancedSearch(): JsonResponse
    {
        // Ejemplo de búsqueda avanzada con whereQuery
        $parameters = $this->parameterRepository->whereQuery(['*'], [
            'type' => ['in', ['config', 'setting']],        // whereIn
            'status' => 'active',                            // where normal
            'created_at' => ['>=', '2024-01-01'],           // where con operador
            'description' => ['not_null'],                   // whereNotNull
            'name' => ['like', '%cache%']                    // where like
        ])->with(['user', 'category'])->paginate(20);

        return $this->response
            ->message('Búsqueda avanzada realizada exitosamente')
            ->data($parameters)
            ->build();
    }

    public function getByMultipleTypes(): JsonResponse
    {
        $types = request()->validate([
            'types' => 'required|array',
            'types.*' => 'string'
        ])['types'];

        $parameters = $this->parameterRepository->getParametersByTypes($types);
        
        return $this->response
            ->message('Parámetros obtenidos exitosamente')
            ->data($parameters)
            ->build();
    }

    public function statistics(): JsonResponse
    {
        // Usando métodos disponibles en los traits
        $stats = [
            'total' => $this->parameterRepository->count(),
            'active' => $this->parameterRepository->count(['status' => 'active']),
            'by_type' => [
                'config' => $this->parameterRepository->count(['type' => 'config']),
                'setting' => $this->parameterRepository->count(['type' => 'setting']),
                'option' => $this->parameterRepository->count(['type' => 'option']),
                'feature' => $this->parameterRepository->count(['type' => 'feature']),
            ],
            'recent' => $this->parameterRepository->whereQuery(['*'], [])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];

        return $this->response
            ->message('Estadísticas obtenidas exitosamente')
            ->data($stats)
            ->build();
    }
}
