```php
<?php

namespace BMCLibrary\Examples;

use BMCLibrary\UnitOfWork\UnitOfWork;
use BMCLibrary\Repository\GenericRepository;
use BMCLibrary\Repository\AutoModelRepository;

class UserRepository extends AutoModelRepository
{
    protected string $modelClass = \App\Models\User::class;
}

class ProductRepository extends AutoModelRepository
{
    protected string $modelClass = \App\Models\Product::class;
}

class ExampleService
{
    private UnitOfWork $unitOfWork;
    private UserRepository $userRepository;
    private ProductRepository $productRepository;

    public function __construct(UnitOfWork $unitOfWork, UserRepository $userRepository, ProductRepository $productRepository)
    {
        $this->unitOfWork = $unitOfWork;
        $this->userRepository = $userRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Ejemplo de uso de UnitOfWork para coordinar varias operaciones
     */
    public function createUserAndProduct(array $userData, array $productData)
    {
        return $this->unitOfWork->execute(function () use ($userData, $productData) {
            $user = $this->userRepository->create($userData);
            $product = $this->productRepository->create($productData);
            // Si ocurre una excepción aquí, se hace rollback automático
            return compact('user', 'product');
        });
    }

    /**
     * Ejemplo de actualización masiva y creación en transacción
     */
    public function updateUsersAndCreateProduct(array $userConditions, array $userUpdates, array $productData)
    {
        return $this->unitOfWork->execute(function () use ($userConditions, $userUpdates, $productData) {
            $updatedCount = $this->userRepository->updateMany($userConditions, $userUpdates);
            $product = $this->productRepository->create($productData);
            return compact('updatedCount', 'product');
        });
    }

    /**
     * Ejemplo de rollback manual
     */
    public function manualTransactionExample(array $userData)
    {
        $this->unitOfWork->beginTransaction();
        try {
            $user = $this->userRepository->create($userData);
            // ... otras operaciones
            $this->unitOfWork->commit();
            return $user;
        } catch (\Exception $e) {
            $this->unitOfWork->rollback();
            throw $e;
        }
    }
}

// Ejemplo de uso en un controlador
class ExampleController
{
    private ExampleService $service;

    public function __construct(ExampleService $service)
    {
        $this->service = $service;
    }

    public function store()
    {
        $userData = [
            'name' => 'Brayan',
            'email' => 'brayan@example.com',
            'password' => bcrypt('secret')
        ];
        $productData = [
            'name' => 'Producto X',
            'price' => 99.99
        ];
        $result = $this->service->createUserAndProduct($userData, $productData);
        return response()->json($result);
    }
}
```
