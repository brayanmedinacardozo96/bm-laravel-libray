# Mejoras del GenericRepository: Refactorización con Traits

## Visión General

El `GenericRepository` ha sido refactorizado utilizando traits para mejor organización del código y cumplir con las mejores prácticas de desarrollo. Los métodos se han dividido en tres grupos:

### 1. GenericRepository (Clase Principal)
Contiene los métodos fundamentales CRUD y de consulta.

### 2. BasicRepositoryOperations (Trait)
Contiene operaciones básicas de búsqueda y consulta.

### 3. AdvancedRepositoryOperations (Trait)
Contiene operaciones avanzadas como agregaciones y manipulación masiva.

## Métodos Disponibles por Categoría

### Métodos CRUD Básicos (GenericRepository)

```php
// Crear registro
$user = $repository->create(['name' => 'Juan', 'email' => 'juan@example.com']);

// Buscar por ID
$user = $repository->find(1);

// Buscar por ID o fallar
$user = $repository->findOrFail(1);

// Obtener todos los registros paginados
$users = $repository->getAll();

// Actualizar registro
$updatedUser = $repository->update(1, ['name' => 'Juan Carlos']);

// Eliminar registro
$deleted = $repository->delete(1);

// Verificar existencia por ID
$exists = $repository->exists(1);
```

### Consultas Avanzadas con whereQuery

```php
// Uso básico
$activeUsers = $repository->whereQuery(['*'], ['status' => 'active']);

// Múltiples condiciones
$results = $repository->whereQuery(['id', 'name'], [
    'status' => 'active',
    'role' => ['in', ['admin', 'moderator']],
    'created_at' => ['>=', '2024-01-01']
]);

// Operaciones complejas
$results = $repository->whereQuery(['*'], [
    'age' => ['between', [18, 65]],
    'email' => ['like', '%@gmail.com'],
    'deleted_at' => ['null']
]);
```

### Métodos de BasicRepositoryOperations (Trait)

```php
// Obtener primer registro
$user = $repository->first(['status' => 'active']);

// Obtener primer registro o fallar
$user = $repository->firstOrFail(['email' => 'admin@example.com']);

// Verificar existencia con condiciones
$exists = $repository->existsWhere(['email' => 'test@example.com']);

// Contar registros
$count = $repository->count(['status' => 'active']);

// Búsqueda con paginación
$results = $repository->search([
    'per_page' => 20,
    'filters' => [
        'name' => 'Juan',
        'status' => 'active'
    ]
]);

// Obtener todos sin paginación
$allUsers = $repository->getAllRecords(['status' => 'active']);

// Obtener para select/dropdown
$options = $repository->getAllForSelect(
    ['status' => 'active'], 
    'id', 
    'name'
);

// Obtener con condiciones y paginación
$results = $repository->getWhere(
    ['status' => 'active'], 
    ['id', 'name', 'email'], 
    15
);

// Primer registro con condiciones
$user = $repository->getFirstWhere(['email' => 'admin@example.com']);

// Constructor de consulta select
$query = $repository->buildSelect(['id', 'name']);

// Getter/Setter del modelo
$model = $repository->getModel();
$repository->setModel(new User());
```

### Métodos de AdvancedRepositoryOperations (Trait)

```php
// Operaciones de agregación
$maxAge = $repository->max('age', ['status' => 'active']);
$minPrice = $repository->min('price');
$avgRating = $repository->avg('rating', ['status' => 'published']);
$totalSales = $repository->sum('amount', ['status' => 'completed']);

// Valores distintos
$roles = $repository->distinct('role', ['status' => 'active']);

// Actualización masiva
$updated = $repository->updateWhere(
    ['status' => 'pending'], 
    ['status' => 'processed']
);

// Eliminación masiva
$deleted = $repository->deleteWhere(['status' => 'spam']);

// Crear o encontrar
$user = $repository->firstOrCreate(
    ['email' => 'user@example.com'],
    ['name' => 'New User', 'status' => 'active']
);

// Actualizar o crear
$user = $repository->updateOrCreate(
    ['email' => 'user@example.com'],
    ['name' => 'Updated Name', 'last_login' => now()]
);

// Inserción masiva
$inserted = $repository->insertBatch([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
]);

// Registros aleatorios
$randomUsers = $repository->random(5, ['status' => 'active']);

// Últimos registros
$latestPosts = $repository->latest(10, 'created_at', ['status' => 'published']);

// Primeros registros
$oldestUsers = $repository->oldest(5, 'created_at');

// Procesamiento en chunks
$repository->chunk(100, function ($users) {
    foreach ($users as $user) {
        // Procesar usuario
        $user->processData();
    }
}, ['status' => 'active']);
```

## Ejemplo de Repositorio Personalizado

```php
<?php

namespace App\Repositories;

use BMCLibrary\Repository\GenericRepository;
use App\Models\Product;

class ProductRepository extends GenericRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener productos por categoría con descuento
     */
    public function getDiscountedProducts(int $categoryId, float $minDiscount = 10.0)
    {
        return $this->whereQuery(['*'], [
            'category_id' => $categoryId,
            'discount' => ['>=', $minDiscount],
            'status' => 'active',
            'stock' => ['>', 0]
        ])->with(['category', 'images'])->get();
    }

    /**
     * Búsqueda avanzada de productos
     */
    public function advancedSearch(array $criteria)
    {
        $conditions = [];
        
        // Filtros básicos
        if (!empty($criteria['categories'])) {
            $conditions['category_id'] = $criteria['categories'];
        }
        
        if (!empty($criteria['price_range'])) {
            $conditions['price'] = ['between', $criteria['price_range']];
        }
        
        if (!empty($criteria['in_stock'])) {
            $conditions['stock'] = ['>', 0];
        }
        
        // Usar métodos del trait
        $query = $this->whereQuery(['*'], $conditions);
        
        // Ordenamiento
        if (!empty($criteria['sort_by'])) {
            $direction = $criteria['sort_direction'] ?? 'asc';
            $query->orderBy($criteria['sort_by'], $direction);
        }
        
        return $query->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Estadísticas de productos
     */
    public function getStatistics()
    {
        return [
            'total_products' => $this->count(),
            'active_products' => $this->count(['status' => 'active']),
            'total_value' => $this->sum('price', ['status' => 'active']),
            'average_price' => $this->avg('price', ['status' => 'active']),
            'max_price' => $this->max('price'),
            'categories' => $this->distinct('category_id', ['status' => 'active'])
        ];
    }

    /**
     * Productos más vendidos
     */
    public function getBestSellers(int $limit = 10)
    {
        return $this->latest($limit, 'sales_count', [
            'status' => 'active',
            'sales_count' => ['>', 0]
        ]);
    }

    /**
     * Actualizar stock masivamente
     */
    public function updateStockForCategory(int $categoryId, int $adjustment)
    {
        // Usar operación masiva del trait
        return $this->updateWhere(
            ['category_id' => $categoryId],
            ['stock' => \DB::raw("stock + {$adjustment}")]
        );
    }
}
```

## Uso en Controladores

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    public function index(Request $request)
    {
        $criteria = [
            'categories' => $request->input('categories'),
            'price_range' => $request->input('price_range'),
            'in_stock' => $request->boolean('in_stock'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_direction' => $request->input('sort_direction', 'desc'),
            'per_page' => $request->input('per_page', 15)
        ];

        $products = $this->productRepository->advancedSearch($criteria);
        
        return response()->json($products);
    }

    public function statistics()
    {
        $stats = $this->productRepository->getStatistics();
        
        return response()->json($stats);
    }

    public function bestSellers()
    {
        $products = $this->productRepository->getBestSellers(20);
        
        return response()->json($products);
    }
}
```

## Ventajas de la Refactorización

### 1. **Organización del Código**
- Separación clara de responsabilidades
- Métodos agrupados por funcionalidad
- Fácil mantenimiento

### 2. **Reutilización**
- Los traits pueden ser utilizados en otros repositorios
- Funcionalidades específicas pueden ser incluidas selectivamente

### 3. **Extensibilidad**
- Fácil agregar nuevos métodos a cada trait
- Posibilidad de crear traits específicos para dominios

### 4. **Cumplimiento de Estándares**
- Respeta el límite de métodos por clase
- Mejora la legibilidad del código

### 5. **Flexibilidad de whereQuery**
- Soporte para múltiples operadores: `in`, `not_in`, `between`, `like`, `null`, etc.
- Consultas complejas con sintaxis simple
- Fácil construcción de filtros dinámicos

Esta refactorización mantiene toda la funcionalidad existente mientras mejora la organización y mantenibilidad del código.
