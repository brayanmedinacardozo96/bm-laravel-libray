# Solución para AreaRepository

## Problema
Al implementar `AreaRepositoryInterface extends GenericRepositoryInterface`, los tipos estrictos de PHP causan conflictos porque esperas `Area` pero la interfaz define `Model`.

## Solución 1: Usar BaseRepositoryInterface (Recomendado)

```php
<?php

namespace App\Contracts;

use BMCLibrary\Contracts\BaseRepositoryInterface;
use App\Models\Area;
use Illuminate\Pagination\LengthAwarePaginator;

interface AreaRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Métodos específicos para Area (con tipos específicos si quieres)
     */
    public function findByName(string $name): ?Area;
    public function getActiveAreas(): LengthAwarePaginator;
}
```

```php
<?php

namespace App\Infrastructure\Persistence\Area;

use App\Contracts\AreaRepositoryInterface;
use App\Models\Area;
use BMCLibrary\Repository\AutoModelRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class AreaRepository extends AutoModelRepository implements AreaRepositoryInterface
{
    protected string $modelClass = Area::class;
    
    public function findByName(string $name): ?Area
    {
        return $this->getFirstWhere(['name' => $name]);
    }
    
    public function getActiveAreas(): LengthAwarePaginator
    {
        return $this->getWhere(['active' => true]);
    }
}
```

## Solución 2: Interfaz específica sin herencia

```php
<?php

namespace App\Contracts;

use App\Models\Area;
use Illuminate\Pagination\LengthAwarePaginator;

interface AreaRepositoryInterface
{
    // Solo los métodos que realmente necesitas
    public function find(mixed $id): ?Area;
    public function create(array $data): Area;
    public function update(mixed $id, array $data): ?Area;
    public function delete(mixed $id): bool;
    public function all(int $perPage = 15): LengthAwarePaginator;
    
    // Métodos específicos
    public function findByName(string $name): ?Area;
    public function getActiveAreas(): LengthAwarePaginator;
}
```

## Solución 3: Sin interfaz específica (Más simple)

```php
<?php

namespace App\Infrastructure\Persistence\Area;

use App\Models\Area;
use BMCLibrary\Repository\AutoModelRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class AreaRepository extends AutoModelRepository
{
    protected string $modelClass = Area::class;
    
    public function findByName(string $name): ?Area
    {
        return $this->getFirstWhere(['name' => $name]);
    }
    
    public function getActiveAreas(): LengthAwarePaginator
    {
        return $this->getWhere(['active' => true]);
    }
}
```

## Recomendación

**Usa la Solución 1** con `BaseRepositoryInterface` porque:

1. ✅ **Mantiene el contrato**: Tienes una interfaz clara
2. ✅ **Evita conflictos de tipos**: Sin problemas de covarianza
3. ✅ **Flexibilidad**: Puedes agregar métodos específicos con tipos exactos
4. ✅ **Testeable**: Fácil mockear en tests

## Uso en Handlers

```php
<?php

namespace App\Handlers;

use App\Contracts\AreaRepositoryInterface;
use BMCLibrary\Mediator\QueryHandler;
use BMCLibrary\Utils\Result;

class GetAreaByNameQueryHandler extends QueryHandler
{
    public function __construct(
        private AreaRepositoryInterface $areaRepository
    ) {}
    
    public function handle(object $request): Result
    {
        $area = $this->areaRepository->findByName($request->name);
        
        if (!$area) {
            return Result::fail("Área no encontrada", 404);
        }
        
        return Result::ok($area, "Área encontrada");
    }
}
```
