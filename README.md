# BM Library - Laravel Utils

Una librería de utilidades para Laravel que implementa el patrón Result y helpers para respuestas API consistentes.

## Características

- ✅ **Patrón Result**: Manejo consistente de operaciones exitosas y fallidas
- ✅ **ApiResponse Helper**: Respuestas JSON estandarizadas para APIs
- ✅ **Soporte para paginación**: Manejo automático de datos paginados
- ✅ **Facade incluido**: Acceso fácil mediante `ApiResponse::method()`
- ✅ **Auto-discovery**: Se registra automáticamente en Laravel

## Instalación

### Desde Packagist (Recomendado)

```bash
composer require bm-library/bm-library
```

### Desde repositorio Git

```bash
composer require bm-library/bm-library:dev-main
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
        "bm-library/bm-library": "dev-main"
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
