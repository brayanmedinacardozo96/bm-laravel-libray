# ✅ Método updateOrCreate Genérico - IMPLEMENTADO

## 🎯 **Métodos Agregados**

He implementado **3 variantes** del método `updateOrCreate` genérico en el trait `AdvancedRepositoryOperations`:

### 1. **`updateOrCreate(array $attributes, array $values = [])`**

```php
// Método estándar de Laravel
$user = $repository->updateOrCreate(
    ['email' => 'john@example.com'],    // Condiciones
    ['name' => 'John Doe']              // Datos adicionales
);
```

### 2. **`updateOrCreateWithFilters(array $attributes, array $filters)`** ⭐

```php
// Tu método solicitado con parámetros separados y claros
$application = $repository->updateOrCreateWithFilters(
    [                                   // $attributes: Datos a guardar/actualizar
        'name' => 'Nueva App',
        'config' => ['setting' => 'value'],
        'status' => 'active'
    ],
    [                                   // $filters: Condiciones de búsqueda
        'user_id' => 123,
        'type' => 'temporary'
    ]
);
```

### 3. **`updateOrCreateRecord(array $attributes, array $filters)`**

```php
// Alias con nombre más descriptivo
$config = $repository->updateOrCreateRecord($configData, $searchCriteria);
```

## 🔧 **Equivalencia con tu Código Original**

### ❌ **ANTES (Específico):**

```php
public function updateOrCreate(array $attributes, array $filters)
{
    return ApplicationTemp::updateOrCreate(
        $filters,
        $attributes
    );
}
```

### ✅ **DESPUÉS (Genérico):**

```php
// Ahora disponible en CUALQUIER repositorio que extienda GenericRepository
public function createOrUpdateUserApplication(int $userId, array $applicationData): ApplicationTemp
{
    return $this->updateOrCreateWithFilters(
        [                                   // $attributes (datos a guardar)
            'name' => $applicationData['name'],
            'description' => $applicationData['description'],
            'config' => $applicationData['config'],
            'status' => 'active'
        ],
        [                                   // $filters (condiciones de búsqueda)
            'user_id' => $userId,
            'type' => 'temporary'
        ]
    );
}
```

## 📋 **Interfaz Actualizada**

La interfaz `BaseRepositoryInterface` ahora incluye:

```php
public function updateOrCreateWithFilters(array $attributes, array $filters);
public function updateOrCreateRecord(array $attributes, array $filters);
```

## 🎯 **Uso Inmediato**

### En cualquier repositorio que extienda `AutoModelRepository` o `GenericRepository`:

```php
class ApplicationTempRepository extends AutoModelRepository
{
    protected string $modelClass = ApplicationTemp::class;

    // ¡Ya tienes disponible updateOrCreateWithFilters!
    public function saveUserApplication(int $userId, array $data): ApplicationTemp
    {
        return $this->updateOrCreateWithFilters(
            $data,                          // Datos a guardar
            ['user_id' => $userId]          // Filtros de búsqueda
        );
    }
}
```

### En controladores:

```php
$application = $this->applicationRepository->updateOrCreateWithFilters(
    [
        'name' => $request->name,
        'config' => $request->config,
        'status' => 'active'
    ],
    [
        'user_id' => $request->user_id,
        'type' => 'temporary'
    ]
);
```

## 🚀 **Ventajas de la Implementación**

1. **✅ Genérico**: Funciona en cualquier repositorio
2. **✅ Tipado**: Retorna el modelo específico
3. **✅ Claro**: Parámetros separados y bien definidos
4. **✅ Flexible**: 3 variantes según necesidades
5. **✅ Compatible**: Integrado con toda la librería
6. **✅ Documentado**: Ejemplos completos incluidos

## 📁 **Archivos Creados/Actualizados**

- ✅ `src/Traits/AdvancedRepositoryOperations.php` - Métodos agregados
- ✅ `src/Contracts/BaseRepositoryInterface.php` - Interfaz actualizada
- ✅ `examples/UpdateOrCreateExample.php` - Ejemplo completo
- ✅ `UPDATE_OR_CREATE_EXAMPLES.md` - Documentación detallada

**¡El método genérico `updateOrCreate` está listo para usar!** 🎉

Puedes usar inmediatamente `updateOrCreateWithFilters(array $attributes, array $filters)` en cualquier repositorio que extienda de `GenericRepository` o `AutoModelRepository`.
