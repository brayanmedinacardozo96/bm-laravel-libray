# BM Library - Laravel Utils

Una librería de utilidades para Laravel que implementa el patrón Result y helpers para respuestas API consistentes.

## Características

- ✅ **Patrón Result**: Manejo consistente de operaciones exitosas y fallidas
- ✅ **ApiResponse Helper**: Respuestas JSON estandarizadas para APIs
- ✅ **Soporte para paginación**: Manejo automático de datos paginados
- ✅ **Facade incluido**: Acceso fácil mediante `ApiResponse::method()`
- ✅ **Auto-discovery**: Se registra automáticamente en Laravel
- ✅ **GenericController**: Controlador base con dependencias preconfiguradas
- ✅ **Implementación Mediator**: Patrón Mediator completo con Commands, Queries y Handlers
- ✅ **CQRS Support**: Separación clara entre Commands (escritura) y Queries (lectura)
- ✅ **GenericRepository**: Repositorio genérico con operaciones CRUD comunes
- ✅ **Contratos bien definidos**: Interfaces claras para extensibilidad
- ✅ **Manejo de excepciones**: Excepciones dedicadas para mejor debugging

## Instalación

### Desde Packagist (Recomendado)

```bash
composer require bm-library/bm-library
```

### Desde repositorio Git

```bash
composer require bm-library/bm-library:dev-master
```

O agregar a tu `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/tu-usuario/bm-library"
        }
    ],
    "require": {
        "bm-library/bm-library": "dev-master"
    }
}
```

## Uso

### 1. Patrón Result

```php
use BMCLibrary\Utils\Result;

// Operación exitosa
$result = Result::ok($data, "Usuario creado exitosamente");

// Operación fallida
$result = Result::fail("Error de validación", 422);

// En un controlador
public function store(Request $request)
{
    $user = $this->userService->create($request->all());
    
    if ($user instanceof Result) {
        return ApiResponse::call($user);
    }
    
    return ApiResponse::call(Result::ok($user, "Usuario creado"));
}
```

### 2. ApiResponse (usando el Facade)

```php
use BMCLibrary\Facades\ApiResponse;

// Respuesta exitosa simple
return ApiResponse::success(true)
    ->data($users)
    ->message("Usuarios obtenidos exitosamente")
    ->build();

// Respuesta de error
return ApiResponse::success(false)
    ->message("Usuario no encontrado")
    ->status(404)
    ->build();

// Con validaciones
return ApiResponse::success(false)
    ->message("Datos inválidos")
    ->validation($validator->errors())
    ->status(422)
    ->build();
```

### 3. ApiResponse con Result

```php
use BMCLibrary\Utils\Result;
use BMCLibrary\Facades\ApiResponse;

// En un controlador
public function index()
{
    $users = $this->userService->getAll();
    $result = Result::ok($users, "Usuarios obtenidos exitosamente");
    
    return ApiResponse::call($result);
}

public function store(Request $request)
{
    try {
        $user = $this->userService->create($request->all());
        $result = Result::ok($user, "Usuario creado exitosamente");
        
        return ApiResponse::call($result, 201);
    } catch (ValidationException $e) {
        $result = Result::fail("Datos inválidos", 422);
        return ApiResponse::call($result);
    }
}
```

### 4. Soporte para paginación

La librería detecta automáticamente datos paginados de Laravel:

```php
public function index()
{
    $users = User::paginate(10);
    $result = Result::ok($users);
    
    return ApiResponse::call($result); // Incluye automáticamente metadata de paginación
}
```

### 5. Usando GenericController (Recomendado)

Para facilitar el uso, puedes extender el controlador base que incluye las dependencias ya inyectadas:

```php
use BMCLibrary\Controllers\GenericController;
use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;

class UserController extends GenericController
{
    public function index()
    {
        try {
            $users = User::all();
            $result = Result::ok($users, "Usuarios obtenidos exitosamente");
            
            return $this->apiResponse->call($result);
        } catch (\Exception $e) {
            $result = Result::fail("Error al obtener usuarios", HttpStatus::SERVER_ERROR);
            return $this->apiResponse->call($result);
        }
    }
    
    public function store(Request $request)
    {
        // Con mediator (si está configurado)
        if ($this->mediator) {
            $command = new CreateUserCommand($request->all());
            $result = $this->mediator->send($command);
            
            return $this->apiResponse->call($result, HttpStatus::CREATED);
        }
        
        // Sin mediator
        $user = User::create($request->all());
        $result = Result::ok($user, "Usuario creado exitosamente");
        
        return $this->apiResponse->call($result, HttpStatus::CREATED);
    }
}
```

### 6. Patrón Mediator con CQRS

La librería incluye una implementación completa del patrón Mediator con soporte para CQRS (Command Query Responsibility Segregation):

#### Commands (Operaciones de escritura)

```php
use BMCLibrary\Mediator\Command;
use BMCLibrary\Mediator\CommandHandler;
use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;

// Command para crear usuario
class CreateUserCommand extends Command
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password
    ) {}
}

// Handler para el command
class CreateUserCommandHandler extends CommandHandler
{
    public function handle(object $request): Result
    {
        /** @var CreateUserCommand $request */
        
        $validator = validator([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return Result::fail("Errores de validación", HttpStatus::UNPROCESSABLE_ENTITY);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            return Result::ok($user, "Usuario creado exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al crear usuario: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}
```

#### Queries (Operaciones de lectura)

```php
use BMCLibrary\Mediator\Query;
use BMCLibrary\Mediator\QueryHandler;
use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;

// Query para obtener usuarios
class GetUsersQuery extends Query
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $perPage = 15,
        public readonly ?string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc'
    ) {}
}

// Handler para la query
class GetUsersQueryHandler extends QueryHandler
{
    public function handle(object $request): Result
    {
        /** @var GetUsersQuery $request */
        
        try {
            $query = User::query();
            
            // Filtro de búsqueda
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }
            
            // Ordenamiento
            if ($request->sortBy) {
                $query->orderBy($request->sortBy, $request->sortDirection);
            }
            
            $users = $query->paginate($request->perPage);
            
            return Result::ok($users, "Usuarios obtenidos exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al obtener usuarios: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}

// Query para obtener un usuario específico
class GetUserByIdQuery extends Query
{
    public function __construct(
        public readonly int $userId
    ) {}
}

class GetUserByIdQueryHandler extends QueryHandler
{
    public function handle(object $request): Result
    {
        /** @var GetUserByIdQuery $request */
        
        $user = User::find($request->userId);
        
        if (!$user) {
            return Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
        }
        
        return Result::ok($user, "Usuario encontrado");
    }
}
```

#### Uso en el controlador

```php
class UserController extends GenericController
{
    // Query - Operación de lectura
    public function index(Request $request)
    {
        $query = new GetUsersQuery(
            $request->input('search'),
            $request->input('per_page', 15),
            $request->input('sort_by'),
            $request->input('sort_direction', 'desc')
        );

        $result = $this->mediator->send($query);
        
        return $this->apiResponse->call($result);
    }
    
    // Query - Operación de lectura
    public function show($id)
    {
        $query = new GetUserByIdQuery($id);
        $result = $this->mediator->send($query);
        
        return $this->apiResponse->call($result);
    }

    // Command - Operación de escritura
    public function store(Request $request)
    {
        $command = new CreateUserCommand(
            $request->input('name'),
            $request->input('email'),
            $request->input('password')
        );

        $result = $this->mediator->send($command);
        
        return $this->apiResponse->call($result, HttpStatus::CREATED);
    }
    
    // Command - Operación de escritura
    public function update(Request $request, $id)
    {
        $command = new UpdateUserCommand(
            $id,
            $request->only(['name', 'email'])
        );

        $result = $this->mediator->send($command);
        
        return $this->apiResponse->call($result);
    }
}
```

### 7. GenericRepository para operaciones CRUD

La librería incluye un repositorio genérico que simplifica las operaciones CRUD comunes:

#### Opción A: AutoModelRepository (Recomendado)

```php
use BMCLibrary\Repository\AutoModelRepository;

// 1. Crear tu repositorio sin constructor
class UserRepository extends AutoModelRepository
{
    protected string $modelClass = User::class;
    
    // Métodos específicos para usuarios
    public function findByEmail(string $email): ?User
    {
        return $this->getFirstWhere(['email' => $email]);
    }
    
    public function getActiveUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getWhere(['active' => true], ['*'], $perPage);
    }
}

// 2. Usar en los handlers
class GetUsersQueryHandler extends QueryHandler
{
    public function __construct(
        private UserRepository $userRepository  // Se inyecta automáticamente
    ) {}
    
    public function handle(object $request): Result
    {
        $users = $this->userRepository->all($request->perPage);
        return Result::ok($users, "Usuarios obtenidos exitosamente");
    }
}
```

#### Opción B: Factory Methods

```php
use BMCLibrary\Repository\GenericRepository;

class UserRepository extends GenericRepository
{
    public static function make(): self
    {
        return self::for(User::class);
    }
}

// Uso directo
$repository = UserRepository::make();
$users = $repository->all();
```

#### Opción C: Constructor tradicional

```php
use BMCLibrary\Repository\GenericRepository;

class UserRepository extends GenericRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
```

#### Métodos disponibles en GenericRepository:

```php
// Operaciones básicas
$repository->all(15);                    // Paginado
$repository->getAllRecords();            // Sin paginación - NUEVO
$repository->find($id);                  // Buscar por ID
$repository->create($data);              // Crear nuevo
$repository->update($id, $data);         // Actualizar
$repository->delete($id);                // Eliminar
$repository->exists($id);                // Verificar existencia

// Búsquedas avanzadas
$repository->getWhere(['active' => true], ['*'], 15);      // Paginado
$repository->getAllWhere(['active' => true]);             // Sin paginación - NUEVO
$repository->getFirstWhere(['email' => 'user@email.com']);

// Para dropdowns y selects
$repository->allForSelect(['id', 'name'], 1000);          // Paginado
$repository->getAllForSelect(['id', 'name']);             // Sin paginación - NUEVO

// Búsqueda con filtros
$repository->search([
    'filters' => [
        'name' => 'John',
        'active' => true,
        'profile' => ['type' => 'admin']  // Relación
    ],
    'per_page' => 20
]);

// Queries personalizadas
$repository->whereQuery(['name', 'email'], [
    'active' => true,
    'created_at' => ['>=', '2024-01-01']
])->get();                               // Sin paginación
// O
])->paginate(15);                        // Con paginación

// Utilidades
$repository->count(['active' => true]);   // Contar registros
```

### 8. Ejemplo completo de uso

```php
use BMCLibrary\Controllers\GenericController;
use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;
use BMCLibrary\Facades\ApiResponse;

class ProductController extends GenericController
{
    public function index(Request $request)
    {
        try {
            $products = Product::when($request->search, function($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })->paginate(15);
            
            $result = Result::ok($products, "Productos obtenidos exitosamente");
            
            return $this->apiResponse->call($result);
            
        } catch (\Exception $e) {
            $result = Result::fail("Error al obtener productos: " . $e->getMessage());
            return $this->apiResponse->call($result, HttpStatus::SERVER_ERROR);
        }
    }
    
    public function store(Request $request)
    {
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id'
        ]);
        
        if ($validator->fails()) {
            return $this->apiResponse
                ->success(false)
                ->message("Datos de validación incorrectos")
                ->validation($validator->errors())
                ->status(HttpStatus::UNPROCESSABLE_ENTITY)
                ->build();
        }
        
        $product = Product::create($request->all());
        $result = Result::ok($product, "Producto creado exitosamente");
        
        return $this->apiResponse->call($result, HttpStatus::CREATED);
    }
    
    public function show(Product $product)
    {
        $result = Result::ok($product, "Producto encontrado");
        return $this->apiResponse->call($result);
    }
    
    public function update(Request $request, Product $product)
    {
        $validator = validator($request->all(), [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id'
        ]);
        
        if ($validator->fails()) {
            return $this->apiResponse
                ->success(false)
                ->message("Datos de validación incorrectos")
                ->validation($validator->errors())
                ->status(HttpStatus::UNPROCESSABLE_ENTITY)
                ->build();
        }
        
        $product->update($request->all());
        $result = Result::ok($product, "Producto actualizado exitosamente");
        
        return $this->apiResponse->call($result);
    }
    
    public function destroy(Product $product)
    {
        $product->delete();
        $result = Result::ok(null, "Producto eliminado exitosamente");
        
        return $this->apiResponse->call($result);
    }
}
```

## Estructura de respuesta

### Respuesta exitosa

```json
{
    "message": "Operación exitosa",
    "data": [...],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 10,
        "total": 50,
        "next_page_url": "...",
        "prev_page_url": null
    }
}
```

### Respuesta de error

```json
{
    "message": "Error en la operación",
    "validations": {
        "email": ["El email es requerido"]
    }
}
```

## HttpStatus Helper

```php
use BMCLibrary\Utils\HttpStatus;

// Constantes de códigos HTTP
HttpStatus::OK                    // 200
HttpStatus::CREATED              // 201
HttpStatus::BAD_REQUEST          // 400
HttpStatus::UNAUTHORIZED         // 401
HttpStatus::FORBIDDEN           // 403
HttpStatus::NOT_FOUND           // 404
HttpStatus::UNPROCESSABLE_ENTITY // 422
HttpStatus::SERVER_ERROR        // 500

// Obtener mensaje de estado
$message = HttpStatus::message(404); // "Not Found"
```

## Configuración manual (opcional)

Si el auto-discovery no funciona, registra manualmente en `config/app.php`:

```php
'providers' => [
    // ...
    BMCLibrary\Providers\ConfigManagerServiceProvider::class,
],

'aliases' => [
    // ...
    'ApiResponse' => BMCLibrary\Facades\ApiResponse::class,
],
```

## Requisitos

- PHP 8.2 o superior
- Laravel 11.0 o superior

## Licencia

MIT License

## Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-caracteristica`)
3. Commit tus cambios (`git commit -am 'Agregar nueva característica'`)
4. Push a la rama (`git push origin feature/nueva-caracteristica`)
5. Abre un Pull Request

```bash
composer require tu-empresa/laravel-utils
```

## Uso

### Result Pattern

```php
use TuEmpresa\LaravelUtils\Result;

// Operación exitosa
$result = Result::ok($data, "Operación exitosa");

// Operación fallida
$result = Result::fail("Error message", 400);
```

### ApiResponse

```php
use TuEmpresa\LaravelUtils\Facades\ApiResponse;

public function index()
{
    $result = $this->service->getData();
    return ApiResponse::call($result);
}
```

### HttpStatus

```php
use TuEmpresa\LaravelUtils\Enums\HttpStatus;

return response()->json($data, HttpStatus::CREATED);
```

**Ventajas del patrón Mediator con CQRS:**
- ✅ **Separación clara**: Commands para escritura, Queries para lectura
- ✅ **Código más testeable**: Cada handler tiene una responsabilidad específica
- ✅ **Lógica de negocio encapsulada**: Toda la lógica está en los handlers
- ✅ **Fácil reutilización**: Commands y Queries reutilizables
- ✅ **Manejo consistente de errores**: Usando el patrón Result
- ✅ **Escalabilidad**: Fácil optimizar queries independientemente de commands
- ✅ **Mantenibilidad**: Código organizado y fácil de mantener
