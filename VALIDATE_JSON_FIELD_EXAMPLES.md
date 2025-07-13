# Uso de validateJsonField en Clases Hijas

## Método Disponible en GeneralFormRequest

```php
protected function validateJsonField(string $attribute, $value, $fail): void
{
    // Valida que el campo sea un JSON válido o un array
}
```

## Ejemplos de Uso en Clases Hijas

### 1. **CreateApplicationRequest - Validación de Configuración**

```php
<?php

namespace App\Http\Requests;

use BMCLibrary\Http\Requests\Base\GeneralFormRequest;

class CreateApplicationRequest extends GeneralFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'config' => 'required', // Validación personalizada en withValidator
            'metadata' => 'nullable',
            'settings' => 'nullable'
        ];
    }

    /**
     * Configure the validator instance usando validateJsonField
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar config como JSON
            $this->validateJsonField(
                'config', 
                $this->input('config'), 
                function ($message) use ($validator) {
                    $validator->errors()->add('config', $message);
                }
            );

            // Validar metadata como JSON si está presente
            if ($this->has('metadata')) {
                $this->validateJsonField(
                    'metadata', 
                    $this->input('metadata'), 
                    function ($message) use ($validator) {
                        $validator->errors()->add('metadata', $message);
                    }
                );
            }

            // Validar settings como JSON si está presente
            if ($this->has('settings')) {
                $this->validateJsonField(
                    'settings', 
                    $this->input('settings'), 
                    function ($message) use ($validator) {
                        $validator->errors()->add('settings', $message);
                    }
                );
            }
        });
    }
}
```

### 2. **UpdateUserPreferencesRequest - Múltiples Campos JSON**

```php
<?php

namespace App\Http\Requests;

use BMCLibrary\Http\Requests\Base\GeneralFormRequest;

class UpdateUserPreferencesRequest extends GeneralFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'preferences' => 'required',
            'theme_config' => 'nullable',
            'notification_settings' => 'nullable',
            'dashboard_layout' => 'nullable'
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar todos los campos JSON usando el método heredado
            $jsonFields = ['preferences', 'theme_config', 'notification_settings', 'dashboard_layout'];
            
            foreach ($jsonFields as $field) {
                if ($this->has($field)) {
                    $this->validateJsonField(
                        $field,
                        $this->input($field),
                        function ($message) use ($validator, $field) {
                            $validator->errors()->add($field, $message);
                        }
                    );
                }
            }
        });
    }

    /**
     * Método adicional para validar estructura específica
     */
    protected function validatePreferencesStructure($validator): void
    {
        $preferences = $this->input('preferences');
        
        // Si es string, convertir a array para validar estructura
        if (is_string($preferences)) {
            $preferences = json_decode($preferences, true);
        }
        
        if (is_array($preferences)) {
            // Validar que tenga campos requeridos
            $requiredFields = ['language', 'timezone'];
            foreach ($requiredFields as $field) {
                if (!isset($preferences[$field])) {
                    $validator->errors()->add(
                        'preferences', 
                        "El campo preferences debe contener: {$field}"
                    );
                }
            }
        }
    }
}
```

### 3. **CreateProductRequest - Con Validación de Estructura JSON Compleja**

```php
<?php

namespace App\Http\Requests;

use BMCLibrary\Http\Requests\Base\GeneralFormRequest;

class CreateProductRequest extends GeneralFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'specifications' => 'required', // JSON con especificaciones del producto
            'variants' => 'nullable',       // JSON con variantes del producto
            'seo_config' => 'nullable'      // JSON con configuración SEO
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar specifications (requerido)
            $this->validateJsonField(
                'specifications',
                $this->input('specifications'),
                function ($message) use ($validator) {
                    $validator->errors()->add('specifications', $message);
                }
            );

            // Validar structure específica de specifications
            $this->validateSpecificationsStructure($validator);

            // Validar variants si está presente
            if ($this->has('variants')) {
                $this->validateJsonField(
                    'variants',
                    $this->input('variants'),
                    function ($message) use ($validator) {
                        $validator->errors()->add('variants', $message);
                    }
                );
            }

            // Validar seo_config si está presente
            if ($this->has('seo_config')) {
                $this->validateJsonField(
                    'seo_config',
                    $this->input('seo_config'),
                    function ($message) use ($validator) {
                        $validator->errors()->add('seo_config', $message);
                    }
                );
            }
        });
    }

    /**
     * Validar estructura específica usando el método heredado como base
     */
    protected function validateSpecificationsStructure($validator): void
    {
        $specs = $this->input('specifications');
        
        // Convertir a array si es string JSON válido
        if (is_string($specs)) {
            $specs = json_decode($specs, true);
        }
        
        if (is_array($specs)) {
            // Validar campos requeridos en specifications
            $requiredSpecs = ['weight', 'dimensions', 'material'];
            foreach ($requiredSpecs as $spec) {
                if (!isset($specs[$spec])) {
                    $validator->errors()->add(
                        'specifications',
                        "Las especificaciones deben incluir: {$spec}"
                    );
                }
            }
            
            // Validar que dimensions sea un objeto válido
            if (isset($specs['dimensions']) && is_array($specs['dimensions'])) {
                $requiredDimensions = ['length', 'width', 'height'];
                foreach ($requiredDimensions as $dim) {
                    if (!isset($specs['dimensions'][$dim])) {
                        $validator->errors()->add(
                            'specifications',
                            "Las dimensiones deben incluir: {$dim}"
                        );
                    }
                }
            }
        }
    }
}
```

### 4. **Ejemplo con Múltiples Validaciones JSON Reutilizables**

```php
<?php

namespace App\Http\Requests;

use BMCLibrary\Http\Requests\Base\GeneralFormRequest;

class ConfigurationRequest extends GeneralFormRequest
{
    protected array $jsonFields = [
        'app_config' => true,      // requerido
        'user_settings' => false,  // opcional
        'feature_flags' => false,  // opcional
        'api_endpoints' => true    // requerido
    ];

    public function rules(): array
    {
        $rules = [];
        
        foreach ($this->jsonFields as $field => $required) {
            $rules[$field] = $required ? 'required' : 'nullable';
        }
        
        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->jsonFields as $field => $required) {
                // Solo validar si el campo está presente o es requerido
                if ($this->has($field) || $required) {
                    $this->validateJsonField(
                        $field,
                        $this->input($field),
                        function ($message) use ($validator, $field) {
                            $validator->errors()->add($field, $message);
                        }
                    );
                }
            }
        });
    }

    /**
     * Método helper para validar múltiples campos JSON de una vez
     */
    protected function validateAllJsonFields($validator, array $fields): void
    {
        foreach ($fields as $field) {
            if ($this->has($field)) {
                $this->validateJsonField(
                    $field,
                    $this->input($field),
                    function ($message) use ($validator, $field) {
                        $validator->errors()->add($field, $message);
                    }
                );
            }
        }
    }
}
```

## Ventajas de usar `protected validateJsonField`

### ✅ **Reutilización**
```php
// En cualquier FormRequest hijo puedes usar:
$this->validateJsonField('config', $this->input('config'), $failCallback);
```

### ✅ **Consistencia**
```php
// Todas las validaciones JSON siguen el mismo patrón
// - Acepta null (para campos nullable)
// - Acepta arrays directamente
// - Valida strings como JSON
// - Mensaje de error consistente
```

### ✅ **Flexibilidad**
```php
// Puedes agregar validaciones adicionales después
public function withValidator($validator): void
{
    $validator->after(function ($validator) {
        // Validar que sea JSON válido
        $this->validateJsonField('config', $this->input('config'), $failCallback);
        
        // Validar estructura específica
        $this->validateConfigStructure($validator);
    });
}
```

### ✅ **Extensibilidad**
```php
// Puedes crear métodos helper que usen validateJsonField internamente
protected function validateMultipleJsonFields($validator, array $fields): void
{
    foreach ($fields as $field) {
        $this->validateJsonField($field, $this->input($field), $failCallback);
    }
}
```

**¡Perfecto diseño!** El método `protected` permite máxima reutilización en clases hijas manteniendo la encapsulación. 🚀
