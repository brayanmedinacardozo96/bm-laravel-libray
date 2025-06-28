<?php

namespace Examples\UpdateOrCreate;

use BMCLibrary\Repository\AutoModelRepository;
use BMCLibrary\Contracts\BaseRepositoryInterface;
use BMCLibrary\Models\Base\BaseModel;

// =====================================================
// MODELO DE EJEMPLO
// =====================================================

class ApplicationTemp extends BaseModel
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'config',
        'type',
        'status'
    ];

    protected $casts = [
        'config' => 'array'
    ];
}

// =====================================================
// INTERFAZ DEL REPOSITORIO
// =====================================================

interface ApplicationTempRepositoryInterface extends BaseRepositoryInterface
{
    public function createOrUpdateUserApplication(int $userId, array $applicationData): ApplicationTemp;
    public function updateApplicationConfig(int $userId, array $config): ApplicationTemp;
    public function createOrUpdateMultipleApplications(int $userId, array $applications): \Illuminate\Database\Eloquent\Collection;
}

// =====================================================
// REPOSITORIO CON updateOrCreate
// =====================================================

class ApplicationTempRepository extends AutoModelRepository implements ApplicationTempRepositoryInterface
{
    protected string $modelClass = ApplicationTemp::class;

    /**
     * Tu método original convertido a genérico
     */
    public function createOrUpdateUserApplication(int $userId, array $applicationData): ApplicationTemp
    {
        // Usando el nuevo método genérico
        return $this->updateOrCreateWithFilters(
            [                                          // $attributes: datos a guardar/actualizar
                'name' => $applicationData['name'],
                'description' => $applicationData['description'] ?? null,
                'config' => $applicationData['config'] ?? [],
                'status' => 'active',
                'updated_at' => now()
            ],
            [                                          // $filters: condiciones de búsqueda
                'user_id' => $userId,
                'type' => 'temporary'
            ]
        );
    }

    /**
     * Método alternativo usando updateOrCreateRecord
     */
    public function updateApplicationConfig(int $userId, array $config): ApplicationTemp
    {
        // Usando el método alternativo
        return $this->updateOrCreateRecord(
            [                                          // Datos a guardar
                'config' => $config,
                'status' => 'active',
                'updated_at' => now()
            ],
            [                                          // Filtros de búsqueda
                'user_id' => $userId,
                'type' => 'temporary'
            ]
        );
    }

    /**
     * Ejemplo usando el método estándar de Laravel
     */
    public function saveUserPreferences(int $userId, array $preferences): ApplicationTemp
    {
        // Método estándar Laravel (también disponible)
        return $this->updateOrCreate(
            [                                          // Condiciones de búsqueda
                'user_id' => $userId,
                'type' => 'preferences'
            ],
            [                                          // Datos adicionales para crear/actualizar
                'config' => $preferences,
                'status' => 'active',
                'updated_at' => now()
            ]
        );
    }

    /**
     * Método para múltiples aplicaciones
     */
    public function createOrUpdateMultipleApplications(int $userId, array $applications): \Illuminate\Database\Eloquent\Collection
    {
        $results = collect();

        foreach ($applications as $appData) {
            $application = $this->updateOrCreateWithFilters(
                [
                    'name' => $appData['name'],
                    'description' => $appData['description'] ?? null,
                    'config' => $appData['config'] ?? [],
                    'status' => $appData['status'] ?? 'active',
                    'updated_at' => now()
                ],
                [
                    'user_id' => $userId,
                    'type' => $appData['type'] ?? 'temporary',
                    'name' => $appData['name']  // Para evitar duplicados por nombre
                ]
            );
            
            $results->push($application);
        }

        return $results;
    }
}

// =====================================================
// CONTROLADOR DE EJEMPLO
// =====================================================

class ApplicationTempController
{
    public function __construct(
        private ApplicationTempRepositoryInterface $applicationRepository,
        private \BMCLibrary\Contracts\ApiResponseInterface $response
    ) {}

    public function storeOrUpdate(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'config' => 'nullable|array'
        ]);

        try {
            // Usando tu método personalizado que internamente usa updateOrCreateWithFilters
            $application = $this->applicationRepository->createOrUpdateUserApplication(
                $validated['user_id'],
                [
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'config' => $validated['config'] ?? []
                ]
            );

            return $this->response
                ->status(201)
                ->message('Aplicación creada o actualizada exitosamente')
                ->data($application)
                ->build();

        } catch (\Exception $e) {
            return $this->response
                ->status(500)
                ->message('Error al procesar la aplicación: ' . $e->getMessage())
                ->success(false)
                ->build();
        }
    }

    public function updateConfig(\Illuminate\Http\Request $request, int $userId): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'config' => 'required|array'
        ]);

        try {
            $application = $this->applicationRepository->updateApplicationConfig(
                $userId,
                $validated['config']
            );

            return $this->response
                ->message('Configuración actualizada exitosamente')
                ->data($application)
                ->build();

        } catch (\Exception $e) {
            return $this->response
                ->status(500)
                ->message('Error al actualizar configuración: ' . $e->getMessage())
                ->success(false)
                ->build();
        }
    }

    public function bulkStoreOrUpdate(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'applications' => 'required|array',
            'applications.*.name' => 'required|string|max:255',
            'applications.*.description' => 'nullable|string|max:500',
            'applications.*.config' => 'nullable|array',
            'applications.*.type' => 'nullable|string|in:temporary,permanent',
            'applications.*.status' => 'nullable|string|in:active,inactive,draft'
        ]);

        try {
            $applications = $this->applicationRepository->createOrUpdateMultipleApplications(
                $validated['user_id'],
                $validated['applications']
            );

            return $this->response
                ->status(201)
                ->message('Aplicaciones procesadas exitosamente')
                ->data($applications)
                ->build();

        } catch (\Exception $e) {
            return $this->response
                ->status(500)
                ->message('Error al procesar las aplicaciones: ' . $e->getMessage())
                ->success(false)
                ->build();
        }
    }
}

// =====================================================
// COMPARACIÓN: ANTES VS DESPUÉS
// =====================================================

/*
// ❌ ANTES (específico para ApplicationTemp):
public function updateOrCreate(array $attributes, array $filters)
{
    return ApplicationTemp::updateOrCreate(
        $filters,
        $attributes
    );
}

// ✅ DESPUÉS (genérico, reutilizable):
public function createOrUpdateUserApplication(int $userId, array $applicationData): ApplicationTemp
{
    return $this->updateOrCreateWithFilters(
        [
            'name' => $applicationData['name'],
            'description' => $applicationData['description'] ?? null,
            'config' => $applicationData['config'] ?? [],
            'status' => 'active',
            'updated_at' => now()
        ],
        [
            'user_id' => $userId,
            'type' => 'temporary'
        ]
    );
}

VENTAJAS:
✅ Reutilizable en cualquier repositorio
✅ Tipado fuerte con Model como retorno
✅ Parámetros claros y separados
✅ Compatible con toda la librería
✅ Tres variantes disponibles según necesidad
✅ Integrado con interfaces y contratos
*/
