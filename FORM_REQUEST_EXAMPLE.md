# FormRequest Base - Documentación y Ejemplos

## GeneralFormRequest

La clase `GeneralFormRequest` proporciona una base para todos los FormRequests de la aplicación, integrando automáticamente la respuesta de validación con el sistema de ApiResponse de la librería.

### Características

- **Integración automática** con `ApiResponseInterface`
- **Respuestas de error consistentes** en formato JSON
- **Manejo automático de errores 422** (Unprocessable Entity)
- **Formato estandarizado** para errores de validación

### Uso Básico

```php
<?php

namespace App\Http\Requests;

use BMCLibrary\Http\Requests\Base\GeneralFormRequest;

class CreateUserRequest extends GeneralFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // o tu lógica de autorización
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,user,moderator'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'role.required' => 'El rol es obligatorio.',
            'role.in' => 'El rol debe ser admin, user o moderator.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'role' => 'rol'
        ];
    }
}
```

### Ejemplo con Validación Condicional

```php
<?php

namespace App\Http\Requests;

use BMCLibrary\Http\Requests\Base\GeneralFormRequest;

class UpdateProductRequest extends GeneralFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $productId,
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'required|in:active,inactive,draft',
            'publish_at' => 'nullable|date|after:now'
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'El SKU ya existe para otro producto.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'tags.*.string' => 'Cada tag debe ser una cadena de texto.',
            'publish_at.after' => 'La fecha de publicación debe ser futura.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => str()->slug($this->name ?? ''),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->price < 1 && $this->status === 'active') {
                $validator->errors()->add(
                    'price', 
                    'Los productos activos deben tener un precio mayor a 0.'
                );
            }
        });
    }
}
```

### Uso en Controladores

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateProductRequest;
use BMCLibrary\Contracts\ApiResponseInterface;

class UserController extends Controller
{
    public function __construct(
        private ApiResponseInterface $response
    ) {}

    public function store(CreateUserRequest $request)
    {
        // Los datos ya están validados automáticamente
        $validatedData = $request->validated();
        
        $user = User::create($validatedData);
        
        return $this->response
            ->status(201)
            ->message('Usuario creado exitosamente')
            ->data($user)
            ->build();
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validatedData = $request->validated();
        
        $product->update($validatedData);
        
        return $this->response
            ->message('Producto actualizado exitosamente')
            ->data($product->fresh())
            ->build();
    }
}
```

### Respuesta de Error Automática

Cuando la validación falla, `GeneralFormRequest` automáticamente devuelve una respuesta JSON con este formato:

```json
{
    "success": false,
    "status": 422,
    "message": "Validation errors",
    "errors": {
        "name": ["El nombre es obligatorio."],
        "email": [
            "El email es obligatorio.",
            "El email debe tener un formato válido."
        ],
        "password": ["La contraseña debe tener al menos 8 caracteres."]
    }
}
```

### Configuración en Service Provider

```php
<?php

namespace App\Providers;

use BMCLibrary\Contracts\ApiResponseInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Asegurar que ApiResponse esté disponible para FormRequests
        $this->app->bind(ApiResponseInterface::class, function ($app) {
            return $app->make(\BMCLibrary\Utils\ApiResponse::class);
        });
    }
}
```

### Ventajas

1. **Consistencia**: Todas las respuestas de validación tienen el mismo formato
2. **Centralización**: La lógica de respuesta de error está en un solo lugar
3. **Flexibilidad**: Puedes sobrescribir `failedValidation` en clases específicas si necesitas comportamiento custom
4. **Integración**: Se integra perfectamente con el sistema ApiResponse de la librería

### Personalización Avanzada

Si necesitas personalizar el comportamiento para un FormRequest específico:

```php
class SpecialFormRequest extends GeneralFormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        // Lógica personalizada antes de la respuesta
        \Log::warning('Validation failed for special form', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all()
        ]);

        // Usar respuesta personalizada
        throw new HttpResponseException(
            $this->response
                ->status(422)
                ->message('Error de validación personalizado')
                ->validation($validator->errors())
                ->extra(['timestamp' => now()->toISOString()])
                ->build()
        );
    }
}
```
