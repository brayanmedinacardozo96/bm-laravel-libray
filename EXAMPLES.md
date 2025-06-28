# Ejemplos de uso completos

## 1. Controlador básico con GenericController

```php
<?php

namespace App\Http\Controllers;

use BMCLibrary\Controllers\GenericController;
use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends GenericController
{
    public function index()
    {
        try {
            $users = User::paginate(10);
            $result = Result::ok($users, "Usuarios obtenidos exitosamente");
            
            return $this->apiResponse->call($result);
        } catch (\Exception $e) {
            $result = Result::fail("Error al obtener usuarios: " . $e->getMessage());
            return $this->apiResponse->call($result, HttpStatus::SERVER_ERROR);
        }
    }

    public function show($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            $result = Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
            return $this->apiResponse->call($result, HttpStatus::NOT_FOUND);
        }
        
        $result = Result::ok($user, "Usuario encontrado");
        return $this->apiResponse->call($result);
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse
                ->success(false)
                ->message("Errores de validación")
                ->validation($validator->errors())
                ->status(HttpStatus::UNPROCESSABLE_ENTITY)
                ->build();
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            $result = Result::ok($user, "Usuario creado exitosamente");
            return $this->apiResponse->call($result, HttpStatus::CREATED);
            
        } catch (\Exception $e) {
            $result = Result::fail("Error al crear usuario: " . $e->getMessage());
            return $this->apiResponse->call($result, HttpStatus::SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            $result = Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
            return $this->apiResponse->call($result, HttpStatus::NOT_FOUND);
        }

        $validator = \Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
        ]);

        if ($validator->fails()) {
            return $this->apiResponse
                ->success(false)
                ->message("Errores de validación")
                ->validation($validator->errors())
                ->status(HttpStatus::UNPROCESSABLE_ENTITY)
                ->build();
        }

        try {
            $user->update($request->only(['name', 'email']));
            
            $result = Result::ok($user->fresh(), "Usuario actualizado exitosamente");
            return $this->apiResponse->call($result);
            
        } catch (\Exception $e) {
            $result = Result::fail("Error al actualizar usuario: " . $e->getMessage());
            return $this->apiResponse->call($result, HttpStatus::SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            $result = Result::fail("Usuario no encontrado", HttpStatus::NOT_FOUND);
            return $this->apiResponse->call($result, HttpStatus::NOT_FOUND);
        }

        try {
            $user->delete();
            
            $result = Result::ok(null, "Usuario eliminado exitosamente");
            return $this->apiResponse->call($result);
            
        } catch (\Exception $e) {
            $result = Result::fail("Error al eliminar usuario: " . $e->getMessage());
            return $this->apiResponse->call($result, HttpStatus::SERVER_ERROR);
        }
    }
}
```

## 2. Usando con Service Layer

```php
<?php

namespace App\Http\Controllers;

use BMCLibrary\Controllers\GenericController;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends GenericController
{
    public function __construct(
        protected UserService $userService
    ) {
        parent::__construct(
            app(\BMCLibrary\Contracts\ApiResponseInterface::class)
        );
    }

    public function index()
    {
        $result = $this->userService->getAllUsers();
        return $this->apiResponse->call($result);
    }

    public function store(Request $request)
    {
        $result = $this->userService->createUser($request->all());
        return $this->apiResponse->call($result);
    }
}
```

## 3. Service que retorna Result

```php
<?php

namespace App\Services;

use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;
use App\Models\User;

class UserService
{
    public function getAllUsers(): Result
    {
        try {
            $users = User::paginate(10);
            return Result::ok($users, "Usuarios obtenidos exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al obtener usuarios: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }

    public function createUser(array $data): Result
    {
        $validator = \Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return Result::fail("Errores de validación", HttpStatus::UNPROCESSABLE_ENTITY);
        }

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]);

            return Result::ok($user, "Usuario creado exitosamente");
        } catch (\Exception $e) {
            return Result::fail("Error al crear usuario: " . $e->getMessage(), HttpStatus::SERVER_ERROR);
        }
    }
}
```

## 4. Respuestas típicas generadas

### Éxito con datos
```json
{
    "message": "Usuarios obtenidos exitosamente",
    "data": [
        {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@example.com"
        }
    ]
}
```

### Error de validación
```json
{
    "message": "Errores de validación",
    "validations": {
        "email": ["El campo email es obligatorio."],
        "password": ["El campo password debe tener al menos 8 caracteres."]
    }
}
```

### Con paginación
```json
{
    "message": "Usuarios obtenidos exitosamente",
    "data": [...],
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 10,
        "total": 25,
        "next_page_url": "http://localhost/users?page=2&per_page=10",
        "prev_page_url": null
    }
}
```
