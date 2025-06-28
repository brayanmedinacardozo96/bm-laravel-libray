# Cómo usar BM-Library en tu proyecto Laravel

## Instalación

### Opción 1: Desde repositorio Git (Desarrollo)

1. En tu proyecto Laravel, edita el archivo `composer.json` y agrega:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/tu-usuario/tu-repositorio.git"
        }
    ],
    "require": {
        "bm-library/bm-library": "master"
    }
}
```

2. Ejecuta:
```bash
composer install
```

### Opción 2: Desde Packagist (Producción)

Si publicas en Packagist:
```bash
composer require bm-library/bm-library
```

## Configuración

### 1. Registrar el Service Provider (Laravel < 11)

Si tu Laravel no tiene auto-discovery habilitado, agrega en `config/app.php`:

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

### 2. Para Laravel 11+ (Auto-discovery)

El paquete se registra automáticamente gracias a la sección `extra.laravel` en composer.json.

## Uso

### 1. Usando la clase Result

```php
<?php

use BMCLibrary\Utils\Result;
use BMCLibrary\Utils\HttpStatus;

// En tu Controller o Service
class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::all();
            
            $result = Result::success($users, 'Usuarios obtenidos exitosamente');
            
            return app('laravel-utils.api-response')->call($result);
            
        } catch (\Exception $e) {
            $result = Result::error('Error al obtener usuarios: ' . $e->getMessage());
            
            return app('laravel-utils.api-response')->call($result, HttpStatus::INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);
        
        if ($validator->fails()) {
            $result = Result::error('Datos de validación incorrectos', $validator->errors());
            return app('laravel-utils.api-response')->call($result, HttpStatus::UNPROCESSABLE_ENTITY);
        }
        
        $user = User::create($request->all());
        $result = Result::success($user, 'Usuario creado exitosamente');
        
        return app('laravel-utils.api-response')->call($result, HttpStatus::CREATED);
    }
}
```

### 2. Usando el Facade ApiResponse

```php
<?php

use BMCLibrary\Facades\ApiResponse;
use BMCLibrary\Utils\HttpStatus;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        
        return ApiResponse::success(true)
            ->message('Usuarios obtenidos exitosamente')
            ->data($users)
            ->status(HttpStatus::OK)
            ->build();
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
        ]);
        
        if ($validator->fails()) {
            return ApiResponse::success(false)
                ->message('Error de validación')
                ->validation($validator->errors())
                ->status(HttpStatus::UNPROCESSABLE_ENTITY)
                ->build();
        }
        
        $user = User::create($request->all());
        
        return ApiResponse::success(true)
            ->message('Usuario creado exitosamente')
            ->data($user)
            ->status(HttpStatus::CREATED)
            ->build();
    }
}
```

### 3. Con paginación automática

```php
public function index()
{
    $users = User::paginate(10);
    
    $result = Result::success($users, 'Usuarios paginados');
    
    return app('laravel-utils.api-response')->call($result);
    // Automáticamente detecta la paginación y la incluye en la respuesta
}
```

## Estructura de respuesta JSON

La librería genera respuestas con esta estructura:

```json
{
    "message": "Mensaje de éxito o error",
    "data": [], // Solo si hay datos
    "validations": {}, // Solo si hay errores de validación
    "pagination": { // Solo si hay paginación
        "current_page": 1,
        "last_page": 5,
        "per_page": 10,
        "total": 50,
        "next_page_url": "http://example.com/users?page=2&per_page=10",
        "prev_page_url": null
    }
}
```

## Códigos de estado HTTP disponibles

```php
use BMCLibrary\Utils\HttpStatus;

HttpStatus::OK                    // 200
HttpStatus::CREATED              // 201
HttpStatus::BAD_REQUEST          // 400
HttpStatus::UNAUTHORIZED         // 401
HttpStatus::FORBIDDEN            // 403
HttpStatus::NOT_FOUND            // 404
HttpStatus::UNPROCESSABLE_ENTITY // 422
HttpStatus::INTERNAL_SERVER_ERROR // 500
```
