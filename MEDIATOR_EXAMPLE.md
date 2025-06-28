# Ejemplo Completo: Sistema de Usuarios con Mediator y CQRS

Este ejemplo muestra cómo usar toda la funcionalidad de BM Library en un sistema de gestión de usuarios implementando CQRS (Command Query Responsibility Segregation).

## 1. Commands (Operaciones de escritura)

```php
<?php

namespace App\Commands;

use BMCLibrary\Mediator\Command;

class CreateUserCommand extends Command
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password
    ) {}
}

class UpdateUserCommand extends Command
{
    public function __construct(
        public readonly int $userId,
        public readonly array $data
    ) {}
}

class DeleteUserCommand extends Command
{
    public function __construct(
        public readonly int $userId
    ) {}
}
```

## 2. Queries (Operaciones de lectura)

```php
<?php

namespace App\Queries;

use BMCLibrary\Mediator\Query;

class GetUsersQuery extends Query
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $perPage = 15,
        public readonly ?string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc'
    ) {}
}

class GetUserByIdQuery extends Query
{
    public function __construct(
        public readonly int $userId
    ) {}
}

class GetActiveUsersQuery extends Query
{
    public function __construct(
        public readonly int $perPage = 15
    ) {}
}
```

## 3. Command Handlers

```php
<?php

namespace App\Handlers;

use App\Commands\CreateUserCommand;
use App\Commands\UpdateUserCommand;
use App\Commands\DeleteUserCommand;
use App\Models\User;
use BMCLibrary\Mediator\CommandHandler;
use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;

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

class UpdateUserCommandHandler extends CommandHandler
{
    public function handle(object $request): Result
    {
        /** @var UpdateUserCommand $request */
        
        $user = User::find($request->userId);
        
        if (!$user) {
            return Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
        }
        
        $validator = validator($request->data, [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $request->userId,
        ]);

        if ($validator->fails()) {
            return Result::fail("Errores de validación", HttpStatus::UNPROCESSABLE_ENTITY);
        }

        try {
            $user->update($request->data);
            
            return Result::ok($user->fresh(), "Usuario actualizado exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al actualizar usuario: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}

class DeleteUserCommandHandler extends CommandHandler
{
    public function handle(object $request): Result
    {
        /** @var DeleteUserCommand $request */
        
        $user = User::find($request->userId);
        
        if (!$user) {
            return Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
        }

        try {
            $user->delete();
            
            return Result::ok(null, "Usuario eliminado exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al eliminar usuario: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}
```

## 4. Query Handlers

```php
<?php

namespace App\Handlers;

use App\Queries\GetUsersQuery;
use App\Queries\GetUserByIdQuery;
use App\Queries\GetActiveUsersQuery;
use App\Models\User;
use BMCLibrary\Mediator\QueryHandler;
use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;

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

class GetUserByIdQueryHandler extends QueryHandler
{
    public function handle(object $request): Result
    {
        /** @var GetUserByIdQuery $request */
        
        try {
            $user = User::find($request->userId);
            
            if (!$user) {
                return Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
            }
            
            return Result::ok($user, "Usuario encontrado");
        } catch (\Exception $e) {
            return Result::fail("Error al obtener usuario: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}

class GetActiveUsersQueryHandler extends QueryHandler
{
    public function handle(object $request): Result
    {
        /** @var GetActiveUsersQuery $request */
        
        try {
            $users = User::where('active', true)
                        ->orderBy('created_at', 'desc')
                        ->paginate($request->perPage);
            
            return Result::ok($users, "Usuarios activos obtenidos exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al obtener usuarios activos: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}
```

## 5. Controller

```php
<?php

namespace App\Http\Controllers;

use App\Commands\CreateUserCommand;
use App\Commands\UpdateUserCommand;
use App\Commands\DeleteUserCommand;
use App\Queries\GetUsersQuery;
use App\Queries\GetUserByIdQuery;
use App\Queries\GetActiveUsersQuery;
use BMCLibrary\Controllers\GenericController;
use BMCLibrary\Utils\HttpStatus;
use Illuminate\Http\Request;

class UserController extends GenericController
{
    // Query - Operación de lectura
    public function index(Request $request)
    {
        $query = new GetUsersQuery(
            $request->input('search'),
            $request->input('per_page', 15),
            $request->input('sort_by', 'created_at'),
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

    // Query - Operación de lectura
    public function active(Request $request)
    {
        $query = new GetActiveUsersQuery(
            $request->input('per_page', 15)
        );

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

    // Command - Operación de escritura
    public function destroy($id)
    {
        $command = new DeleteUserCommand($id);

        $result = $this->mediator->send($command);
        
        return $this->apiResponse->call($result);
    }
}
```

## 6. Routes

```php
<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class);

// Ruta adicional para usuarios activos
Route::get('users/active', [UserController::class, 'active']);
```

## 7. Ventajas de esta implementación CQRS

### Commands (Escritura)
- ✅ **Enfoque en modificación**: Solo se encargan de cambiar el estado
- ✅ **Validación centralizada**: Toda la validación en un lugar
- ✅ **Transacciones**: Fácil envolver en transacciones de BD
- ✅ **Auditoría**: Fácil registrar qué operaciones se realizan

### Queries (Lectura)
- ✅ **Optimización independiente**: Queries optimizadas sin afectar escritura
- ✅ **Cacheable**: Fácil agregar cache a las consultas
- ✅ **Proyecciones**: Pueden retornar solo los datos necesarios
- ✅ **Sin efectos secundarios**: Solo leen datos, no los modifican

### Generales
- ✅ **Separación clara de responsabilidades**
- ✅ **Código más testeable y mantenible**
- ✅ **Escalabilidad**: Lectura y escritura pueden escalar independientemente**
- ✅ **Reutilización**: Commands y Queries reutilizables en diferentes contextos**

### Listar usuarios con paginación
```http
GET /users?search=john&per_page=10
```

```json
{
    "message": "Usuarios obtenidos exitosamente",
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 10,
        "total": 25,
        "next_page_url": "http://localhost/users?page=2&per_page=10&search=john",
        "prev_page_url": null
    }
}
```

### Crear usuario exitoso
```http
POST /users
Content-Type: application/json

{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "password": "password123"
}
```

```json
{
    "message": "Usuario creado exitosamente",
    "data": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### Error de validación
```http
POST /users
Content-Type: application/json

{
    "name": "",
    "email": "invalid-email",
    "password": "123"
}
```

```json
{
    "message": "Errores de validación",
    "validations": {
        "name": ["El campo name es obligatorio."],
        "email": ["El campo email debe ser una dirección de correo válida."],
        "password": ["El campo password debe tener al menos 8 caracteres."]
    }
}
```

## 8. Respuestas de ejemplo
