# Métodos sin paginación en GenericRepository

## Nuevos métodos agregados

### 1. getAllRecords() - Todos los registros

```php
// En lugar de paginar todos los registros
$users = $userRepository->all(1000)->items(); // ❌ Ineficiente

// Usa el método directo
$users = $userRepository->getAllRecords(); // ✅ Eficiente
```

### 2. getAllWhere() - Registros con condiciones

```php
// Obtener todos los usuarios activos sin paginación
$activeUsers = $userRepository->getAllWhere(['active' => true]);

// Usuarios creados este año
$thisYearUsers = $userRepository->getAllWhere([
    'created_at' => ['>=', '2024-01-01']
]);
```

### 3. getAllForSelect() - Para dropdowns

```php
// Para llenar un select/dropdown
$userOptions = $userRepository->getAllForSelect(['id', 'name']);

// Con columnas personalizadas
$userOptions = $userRepository->getAllForSelect(['id', 'email', 'name']);
```

## Casos de uso prácticos

### En un Handler para obtener datos para un select

```php
class GetUsersForSelectHandler extends QueryHandler
{
    public function __construct(private UserRepository $userRepository) {}
    
    public function handle(object $request): Result
    {
        // Solo usuarios activos para el select
        $users = $this->userRepository->getAllWhere(['active' => true]);
        
        // Transformar para el frontend
        $options = $users->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name
        ]);
        
        return Result::ok($options, "Opciones de usuarios obtenidas");
    }
}
```

### En un Export/Report Handler

```php
class ExportUsersHandler extends CommandHandler
{
    public function __construct(private UserRepository $userRepository) {}
    
    public function handle(object $request): Result
    {
        // Obtener todos los registros para exportar
        $users = $this->userRepository->getAllRecords();
        
        // Generar Excel/CSV
        $export = new UsersExport($users);
        
        return Result::ok($export, "Exportación generada");
    }
}
```

### En un Seeder o Migration

```php
class UserSeeder extends Seeder
{
    public function run()
    {
        $userRepository = app(UserRepository::class);
        
        // Verificar si ya hay usuarios
        $existingUsers = $userRepository->getAllRecords();
        
        if ($existingUsers->isEmpty()) {
            // Crear usuarios predeterminados
            $defaultUsers = [
                ['name' => 'Admin', 'email' => 'admin@example.com'],
                ['name' => 'User', 'email' => 'user@example.com'],
            ];
            
            foreach ($defaultUsers as $userData) {
                $userRepository->create($userData);
            }
        }
    }
}
```

### En un API para obtener datos relacionados

```php
class GetUserWithRelationsHandler extends QueryHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository
    ) {}
    
    public function handle(object $request): Result
    {
        $user = $this->userRepository->find($request->userId);
        
        if (!$user) {
            return Result::fail("Usuario no encontrado", 404);
        }
        
        // Obtener todos los roles disponibles para mostrar opciones
        $availableRoles = $this->roleRepository->getAllForSelect(['id', 'name']);
        
        return Result::ok([
            'user' => $user,
            'available_roles' => $availableRoles
        ], "Usuario y roles obtenidos");
    }
}
```

## Comparación de rendimiento

```php
// ❌ Ineficiente para obtener todos los registros
$users = $userRepository->all(999999)->items();

// ✅ Eficiente - directamente sin paginación
$users = $userRepository->getAllRecords();

// ❌ Ineficiente para dropdowns
$roles = $roleRepository->allForSelect(['id', 'name'], 1000)->items();

// ✅ Eficiente para dropdowns
$roles = $roleRepository->getAllForSelect(['id', 'name']);
```

## Cuándo usar cada método

### Usa paginación cuando:
- ✅ Mostrar datos en una tabla con navegación
- ✅ API endpoints que pueden devolver muchos registros
- ✅ Búsquedas de usuarios con filtros

### Usa sin paginación cuando:
- ✅ Llenar dropdowns/selects
- ✅ Exportar datos
- ✅ Generar reportes
- ✅ Seeders y migraciones
- ✅ Cálculos que requieren todos los registros
- ✅ Pocos registros (< 1000 generalmente)

## Advertencias

⚠️ **Cuidado con tablas grandes**: Los métodos sin paginación cargan todos los registros en memoria. Para tablas con millones de registros, considera usar:

```php
// En lugar de
$allUsers = $userRepository->getAllRecords(); // Puede consumir mucha memoria

// Usa chunks para procesar en lotes
$userRepository->getModel()->chunk(1000, function ($users) {
    foreach ($users as $user) {
        // Procesar usuario
    }
});
```
