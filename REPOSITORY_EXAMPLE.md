# Ejemplo Completo: Sistema con Mediator + Repository

Este ejemplo muestra cómo integrar el patrón Mediator con el GenericRepository para crear un sistema completo y bien estructurado.

## 1. Configuración del Repositorio

```php
<?php

namespace App\Repositories;

use App\Models\User;
use BMCLibrary\Repository\GenericRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends GenericRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->getFirstWhere(['email' => $email]);
    }
    
    /**
     * Get active users
     */
    public function getActiveUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getWhere(['active' => true], ['*'], $perPage);
    }
    
    /**
     * Search users by name or email
     */
    public function searchUsers(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->search([
            'filters' => [
                'name' => $search,
                'email' => $search
            ],
            'per_page' => $perPage
        ]);
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return $this->search([
            'filters' => [
                'roles' => ['name' => $role]  // Relación con roles
            ],
            'per_page' => $perPage
        ]);
    }
}
```

## 2. Query Handlers con Repository

```php
<?php

namespace App\Handlers;

use App\Queries\GetUsersQuery;
use App\Queries\GetUserByIdQuery;
use App\Repositories\UserRepository;
use BMCLibrary\Mediator\QueryHandler;
use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;

class GetUsersQueryHandler extends QueryHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    
    public function handle(object $request): Result
    {
        /** @var GetUsersQuery $request */
        
        try {
            if ($request->search) {
                $users = $this->userRepository->searchUsers($request->search, $request->perPage);
            } elseif ($request->role) {
                $users = $this->userRepository->getUsersByRole($request->role, $request->perPage);
            } elseif ($request->activeOnly) {
                $users = $this->userRepository->getActiveUsers($request->perPage);
            } else {
                $users = $this->userRepository->all($request->perPage);
            }
            
            return Result::ok($users, "Usuarios obtenidos exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al obtener usuarios: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}

class GetUserByIdQueryHandler extends QueryHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    
    public function handle(object $request): Result
    {
        /** @var GetUserByIdQuery $request */
        
        try {
            $user = $this->userRepository->find($request->userId);
            
            if (!$user) {
                return Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
            }
            
            return Result::ok($user, "Usuario encontrado");
        } catch (\Exception $e) {
            return Result::fail("Error al obtener usuario: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}
```

## 3. Command Handlers con Repository

```php
<?php

namespace App\Handlers;

use App\Commands\CreateUserCommand;
use App\Commands\UpdateUserCommand;
use App\Commands\DeleteUserCommand;
use App\Repositories\UserRepository;
use BMCLibrary\Mediator\CommandHandler;
use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommandHandler extends CommandHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    
    public function handle(object $request): Result
    {
        /** @var CreateUserCommand $request */
        
        // Validar que el email no exista
        if ($this->userRepository->findByEmail($request->email)) {
            return Result::fail("El email ya está en uso", HttpStatus::UNPROCESSABLE_ENTITY);
        }
        
        $validator = Validator::make([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return Result::fail("Errores de validación", HttpStatus::UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $this->userRepository->create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'active' => true,
            ]);

            return Result::ok($user, "Usuario creado exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al crear usuario: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}

class UpdateUserCommandHandler extends CommandHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    
    public function handle(object $request): Result
    {
        /** @var UpdateUserCommand $request */
        
        if (!$this->userRepository->exists($request->userId)) {
            return Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
        }
        
        // Validar email único (excluyendo el usuario actual)
        if (isset($request->data['email'])) {
            $existingUser = $this->userRepository->findByEmail($request->data['email']);
            if ($existingUser && $existingUser->id !== $request->userId) {
                return Result::fail("El email ya está en uso", HttpStatus::UNPROCESSABLE_ENTITY);
            }
        }
        
        $validator = Validator::make($request->data, [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email',
        ]);

        if ($validator->fails()) {
            return Result::fail("Errores de validación", HttpStatus::UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $this->userRepository->update($request->userId, $request->data);
            
            return Result::ok($user, "Usuario actualizado exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al actualizar usuario: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}

class DeleteUserCommandHandler extends CommandHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    
    public function handle(object $request): Result
    {
        /** @var DeleteUserCommand $request */
        
        if (!$this->userRepository->exists($request->userId)) {
            return Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
        }

        try {
            $deleted = $this->userRepository->delete($request->userId);
            
            if ($deleted) {
                return Result::ok(null, "Usuario eliminado exitosamente");
            }
            
            return Result::fail("No se pudo eliminar el usuario", HttpStatus::SERVER_ERROR);
        } catch (\Exception $e) {
            return Result::fail("Error al eliminar usuario: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}
```

## 4. Queries y Commands actualizados

```php
<?php

namespace App\Queries;

use BMCLibrary\Mediator\Query;

class GetUsersQuery extends Query
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $role = null,
        public readonly bool $activeOnly = false,
        public readonly int $perPage = 15,
        public readonly ?string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc'
    ) {}
}
```

## 5. Service Provider para registrar repositorios

```php
<?php

namespace App\Providers;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, function ($app) {
            return new UserRepository($app->make(User::class));
        });
    }
}
```

## 6. Controller optimizado

```php
<?php

namespace App\Http\Controllers;

use App\Commands\CreateUserCommand;
use App\Commands\UpdateUserCommand;
use App\Commands\DeleteUserCommand;
use App\Queries\GetUsersQuery;
use App\Queries\GetUserByIdQuery;
use BMCLibrary\Controllers\GenericController;
use BMCLibrary\Utils\HttpStatus;
use Illuminate\Http\Request;

class UserController extends GenericController
{
    public function index(Request $request)
    {
        $query = new GetUsersQuery(
            search: $request->input('search'),
            role: $request->input('role'),
            activeOnly: $request->boolean('active_only'),
            perPage: $request->input('per_page', 15),
            sortBy: $request->input('sort_by', 'created_at'),
            sortDirection: $request->input('sort_direction', 'desc')
        );

        $result = $this->mediator->send($query);
        
        return $this->apiResponse->call($result);
    }

    public function show($id)
    {
        $query = new GetUserByIdQuery($id);
        $result = $this->mediator->send($query);
        
        return $this->apiResponse->call($result);
    }

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

    public function update(Request $request, $id)
    {
        $command = new UpdateUserCommand(
            $id,
            $request->only(['name', 'email'])
        );

        $result = $this->mediator->send($command);
        
        return $this->apiResponse->call($result);
    }

    public function destroy($id)
    {
        $command = new DeleteUserCommand($id);
        $result = $this->mediator->send($command);
        
        return $this->apiResponse->call($result);
    }
}
```

## Ventajas de esta arquitectura

### 🎯 **Separación de responsabilidades**
- **Controllers**: Solo manejan HTTP y delegan al Mediator
- **Handlers**: Lógica de negocio pura
- **Repositories**: Acceso a datos optimizado
- **Models**: Solo definición de estructura

### 🚀 **Testabilidad**
- Cada componente se puede testear independientemente
- Fácil mockear repositorios en tests
- Handlers tienen dependencias explícitas

### 📈 **Escalabilidad**
- Fácil agregar nuevas operaciones
- Repositorios optimizables independientemente
- Cache fácil de implementar en repositorios

### 🔧 **Mantenibilidad**
- Código organizado y predecible
- Fácil encontrar y modificar funcionalidad
- Reutilización de componentes

### 💡 **Flexibilidad**
- Repositorios intercambiables
- Handlers reutilizables
- Fácil cambiar implementaciones
