# Ejemplos Avanzados de whereQuery

El método `whereQuery` del `GenericRepository` soporta múltiples tipos de condiciones avanzadas. Aquí tienes ejemplos completos de uso:

## Operaciones Básicas

### Condiciones Simples
```php
// Buscar por un valor exacto
$result = $repository->whereQuery(['*'], [
    'status' => 'active',
    'type' => 'premium'
]);

// Obtener solo columnas específicas
$result = $repository->whereQuery(['id', 'name', 'email'], [
    'role' => 'admin'
]);
```

## Operaciones con Arrays

### whereIn - Múltiples valores
```php
// whereIn automático con array simple
$result = $repository->whereQuery(['*'], [
    'id' => [1, 2, 3, 4, 5]
]);

// whereIn explícito
$result = $repository->whereQuery(['*'], [
    'status' => ['in', ['active', 'pending', 'verified']]
]);
```

### whereNotIn - Excluir valores
```php
$result = $repository->whereQuery(['*'], [
    'status' => ['not_in', ['deleted', 'banned']],
    'role' => ['notin', ['guest', 'anonymous']]  // Alias alternativo
]);
```

## Operaciones de Rango

### whereBetween - Rangos
```php
// Rango de fechas
$result = $repository->whereQuery(['*'], [
    'created_at' => ['between', ['2024-01-01', '2024-12-31']],
    'age' => ['between', [18, 65]]
]);

// whereNotBetween
$result = $repository->whereQuery(['*'], [
    'score' => ['not_between', [0, 50]]  // Excluir puntuaciones bajas
]);
```

## Operaciones de Nulos

### whereNull y whereNotNull
```php
$result = $repository->whereQuery(['*'], [
    'deleted_at' => ['null'],      // Solo registros no eliminados (soft delete)
    'email_verified_at' => ['not_null']  // Solo usuarios verificados
]);
```

## Operaciones de Texto

### LIKE e ILIKE (case-insensitive)
```php
// Búsqueda con LIKE
$result = $repository->whereQuery(['*'], [
    'name' => ['like', '%john%'],
    'email' => ['like', '%.com']
]);

// Búsqueda case-insensitive (PostgreSQL)
$result = $repository->whereQuery(['*'], [
    'title' => ['ilike', '%IMPORTANT%']
]);
```

## Operadores de Comparación

### Operadores Estándar
```php
$result = $repository->whereQuery(['*'], [
    'price' => ['>', 100],
    'discount' => ['<=', 50],
    'rating' => ['>=', 4.5],
    'stock' => ['!=', 0]
]);
```

## Ejemplos Complejos Combinados

### E-commerce: Productos Filtrados
```php
class ProductRepository extends GenericRepository
{
    public function getFilteredProducts(array $filters)
    {
        $whereConditions = [];
        
        // Categorías específicas
        if (!empty($filters['categories'])) {
            $whereConditions['category_id'] = $filters['categories'];  // whereIn automático
        }
        
        // Rango de precios
        if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
            $whereConditions['price'] = ['between', [$filters['min_price'], $filters['max_price']]];
        }
        
        // Solo productos en stock
        $whereConditions['stock'] = ['>', 0];
        
        // Solo productos activos
        $whereConditions['status'] = 'active';
        
        // Excluir productos descontinuados
        $whereConditions['type'] = ['not_in', ['discontinued', 'draft']];
        
        return $this->whereQuery(['*'], $whereConditions);
    }
}
```

### Sistema de Usuarios: Búsqueda Avanzada
```php
class UserRepository extends GenericRepository
{
    public function getActiveUsers(array $criteria = [])
    {
        $whereConditions = [
            'deleted_at' => ['null'],           // No eliminados
            'email_verified_at' => ['not_null'], // Verificados
            'status' => ['in', ['active', 'premium']]
        ];
        
        // Filtro por roles específicos
        if (!empty($criteria['roles'])) {
            $whereConditions['role'] = $criteria['roles'];
        }
        
        // Filtro por fecha de registro
        if (!empty($criteria['registered_after'])) {
            $whereConditions['created_at'] = ['>=', $criteria['registered_after']];
        }
        
        // Búsqueda por nombre
        if (!empty($criteria['search_name'])) {
            $whereConditions['name'] = ['like', '%' . $criteria['search_name'] . '%'];
        }
        
        return $this->whereQuery(['id', 'name', 'email', 'role', 'created_at'], $whereConditions);
    }
}
```

### Blog: Artículos Publicados
```php
class ArticleRepository extends GenericRepository
{
    public function getPublishedArticles($authorIds = null, $tags = null)
    {
        $whereConditions = [
            'status' => 'published',
            'published_at' => ['not_null'],
            'deleted_at' => ['null']
        ];
        
        // Filtrar por autores específicos
        if ($authorIds) {
            $whereConditions['author_id'] = $authorIds;  // whereIn automático
        }
        
        // Excluir ciertos tags
        if ($tags) {
            $whereConditions['tag'] = ['not_in', $tags];
        }
        
        return $this->whereQuery(['*'], $whereConditions);
    }
}
```

## Uso con Paginación

### Combinar whereQuery con Paginación
```php
// En tu repositorio personalizado
public function getFilteredPaginated(array $filters, int $perPage = 15)
{
    $query = $this->whereQuery(['*'], $filters);
    
    return $query->orderBy('created_at', 'desc')
                 ->paginate($perPage);
}

// Uso en controlador
$filters = [
    'status' => ['in', ['active', 'pending']],
    'created_at' => ['>=', '2024-01-01'],
    'category' => ['not_in', ['spam', 'test']]
];

$results = $repository->getFilteredPaginated($filters, 20);
```

## Consejos y Mejores Prácticas

### 1. Validación de Entrada
```php
public function searchProducts(array $criteria)
{
    $whereConditions = [];
    
    // Validar y sanitizar entradas
    if (isset($criteria['price_range']) && is_array($criteria['price_range'])) {
        $whereConditions['price'] = ['between', $criteria['price_range']];
    }
    
    if (isset($criteria['categories']) && is_array($criteria['categories'])) {
        $whereConditions['category_id'] = $criteria['categories'];
    }
    
    return $this->whereQuery(['*'], $whereConditions);
}
```

### 2. Combinación con Relaciones
```php
// Usar whereQuery y luego agregar relaciones
$query = $this->whereQuery(['*'], [
    'status' => 'active',
    'type' => ['in', ['premium', 'standard']]
]);

return $query->with(['category', 'tags', 'author'])
             ->orderBy('created_at', 'desc')
             ->get();
```

### 3. Performance Tips
```php
// Solo seleccionar columnas necesarias
$lightweightResults = $this->whereQuery(
    ['id', 'name', 'status'], // Solo campos necesarios
    [
        'status' => 'active',
        'created_at' => ['>=', now()->subDays(30)]
    ]
);

// Usar índices en las columnas de filtrado
// CREATE INDEX idx_status_created ON table_name (status, created_at);
```

Esta funcionalidad hace que `whereQuery` sea extremadamente flexible para construir consultas complejas de manera simple y legible.
