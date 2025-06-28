# Método updateOrCreate Genérico - Documentación

## Métodos Disponibles

El `GenericRepository` ahora incluye varios métodos para operaciones `updateOrCreate` con diferentes firmas según tus necesidades:

### 1. `updateOrCreate(array $attributes, array $values = [])`

**Método estándar de Laravel**

```php
// Busca por $attributes, si no existe lo crea con $attributes + $values
$user = $repository->updateOrCreate(
    ['email' => 'john@example.com'],     // Condiciones de búsqueda
    ['name' => 'John Doe', 'role' => 'admin']  // Datos adicionales para crear/actualizar
);
```

### 2. `updateOrCreateWithFilters(array $attributes, array $filters)`

**Método con parámetros separados y claros**

```php
// Parámetros claramente separados
$application = $repository->updateOrCreateWithFilters(
    [                                    // $attributes: Datos a actualizar/crear
        'name' => 'Nueva Aplicación',
        'description' => 'Descripción actualizada',
        'status' => 'active',
        'updated_at' => now()
    ],
    [                                    // $filters: Condiciones para encontrar registro
        'user_id' => 123,
        'type' => 'temporary'
    ]
);
```

### 3. `updateOrCreateRecord(array $attributes, array $filters)`

**Alias alternativo con nombre más descriptivo**

```php
$parameter = $repository->updateOrCreateRecord(
    [                                    // Datos del registro
        'value' => 'nuevo_valor',
        'description' => 'Valor actualizado',
        'status' => 'active'
    ],
    [                                    // Filtros de búsqueda
        'name' => 'configuracion_importante',
        'type' => 'config'
    ]
);
```

## Ejemplos Prácticos

### Ejemplo 1: Configuración de Aplicación

```php
class ApplicationTempRepository extends AutoModelRepository
{
    protected string $modelClass = ApplicationTemp::class;

    public function createOrUpdateUserApplication(int $userId, array $applicationData): ApplicationTemp
    {
        return $this->updateOrCreateWithFilters(
            [
                'name' => $applicationData['name'],
                'description' => $applicationData['description'],
                'config' => $applicationData['config'],
                'status' => 'active',
                'updated_at' => now()
            ],
            [
                'user_id' => $userId,
                'type' => 'temporary'
            ]
        );
    }
}
```

### Ejemplo 2: Configuración de Usuario

```php
class UserConfigRepository extends AutoModelRepository
{
    protected string $modelClass = UserConfig::class;

    public function setUserPreference(int $userId, string $key, mixed $value): UserConfig
    {
        return $this->updateOrCreateRecord(
            [
                'value' => json_encode($value),
                'updated_at' => now()
            ],
            [
                'user_id' => $userId,
                'config_key' => $key
            ]
        );
    }

    public function setBulkPreferences(int $userId, array $preferences): Collection
    {
        $results = collect();

        foreach ($preferences as $key => $value) {
            $config = $this->updateOrCreateRecord(
                [
                    'value' => json_encode($value),
                    'updated_at' => now()
                ],
                [
                    'user_id' => $userId,
                    'config_key' => $key
                ]
            );
            $results->push($config);
        }

        return $results;
    }
}
```

### Ejemplo 3: Cache de Datos

```php
class CacheRepository extends AutoModelRepository
{
    protected string $modelClass = CacheEntry::class;

    public function cacheData(string $cacheKey, mixed $data, int $ttlMinutes = 60): CacheEntry
    {
        return $this->updateOrCreateWithFilters(
            [
                'data' => json_encode($data),
                'expires_at' => now()->addMinutes($ttlMinutes),
                'updated_at' => now()
            ],
            [
                'cache_key' => $cacheKey
            ]
        );
    }

    public function cacheUserData(int $userId, string $type, mixed $data): CacheEntry
    {
        return $this->updateOrCreateRecord(
            [
                'data' => json_encode($data),
                'expires_at' => now()->addHour(),
                'updated_at' => now()
            ],
            [
                'user_id' => $userId,
                'cache_type' => $type
            ]
        );
    }
}
```

## Uso en Controladores

### Controlador con updateOrCreate

```php
class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationTempRepository $applicationRepository,
        private ApiResponseInterface $response
    ) {}

    public function storeOrUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'config' => 'required|array',
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $application = $this->applicationRepository->updateOrCreateWithFilters(
                [
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'config' => $validated['config'],
                    'status' => 'active',
                    'updated_at' => now()
                ],
                [
                    'user_id' => $validated['user_id'],
                    'type' => 'temporary'
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
                ->message('Error al procesar la aplicación')
                ->error($e->getMessage())
                ->build();
        }
    }
}
```

## Diferencias entre los Métodos

| Método                        | Parámetro 1             | Parámetro 2                  | Comportamiento       |
| ----------------------------- | ----------------------- | ---------------------------- | -------------------- |
| `updateOrCreate()`            | Condiciones de búsqueda | Datos adicionales (opcional) | Estándar Laravel     |
| `updateOrCreateWithFilters()` | Datos completos         | Condiciones de búsqueda      | Parámetros separados |
| `updateOrCreateRecord()`      | Datos completos         | Condiciones de búsqueda      | Alias descriptivo    |

## Ventajas del Nuevo Enfoque

### 1. **Claridad de Parámetros**

```php
// ❌ Confuso: ¿qué es qué?
$result = $repository->updateOrCreate($data1, $data2);

// ✅ Claro: parámetros bien definidos
$result = $repository->updateOrCreateWithFilters($attributes, $filters);
```

### 2. **Separación de Responsabilidades**

```php
// Datos que se van a guardar/actualizar
$dataToSave = [
    'name' => 'Nuevo nombre',
    'status' => 'active',
    'updated_at' => now()
];

// Condiciones para encontrar el registro
$searchConditions = [
    'user_id' => 123,
    'type' => 'config'
];

$result = $repository->updateOrCreateWithFilters($dataToSave, $searchConditions);
```

### 3. **Flexibilidad**

```php
// Puedes usar el método que más te convenga según el contexto
$user = $repository->updateOrCreate(['email' => $email], $userData);           // Laravel estándar
$config = $repository->updateOrCreateWithFilters($configData, $filters);      // Parámetros claros
$cache = $repository->updateOrCreateRecord($cacheData, $searchCriteria);      // Nombre descriptivo
```

Ahora tienes **múltiples opciones** para usar `updateOrCreate` según tus preferencias y necesidades específicas! 🚀
