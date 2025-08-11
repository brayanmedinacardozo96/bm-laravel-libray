<?php

/**
 * Ejemplo completo usando inyección de dependencias con SSL configurado
 */

namespace App\Services;

use BMCLibrary\AppHttpClient\Abstractions\HttpClientInterface;

class ElectroHuilaApiService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {
        // El cliente ya viene configurado desde el Service Provider
        // con la configuración SSL que pusiste en services.php
    }

    public function obtenerSubdirectorios(): array
    {
        try {
            $response = $this->httpClient->get('/public/api/v1/files/subdirectories');
            
            return $response->getStatusCode() === 200 
                ? json_decode($response->getBody(), true)
                : [];
                
        } catch (\Exception $e) {
            \Log::error('Error API ElectroHuila: ' . $e->getMessage());
            return [];
        }
    }

    public function crearSolicitud(array $data): ?array
    {
        try {
            $response = $this->httpClient->post('/public/api/v1/solicitations', $data);
            
            return $response->getStatusCode() === 201 
                ? json_decode($response->getBody(), true)
                : null;
                
        } catch (\Exception $e) {
            \Log::error('Error crear solicitud: ' . $e->getMessage());
            return null;
        }
    }
}

// Si necesitas usar múltiples clientes HTTP
class MultiApiService
{
    public function __construct(
        private HttpClientInterface $defaultClient,
        // Inyectar cliente específico usando el container
    ) {}

    public function useElectroHuilaClient()
    {
        $electroHuilaClient = app('httpclient.electrohuila');
        // Usar cliente específico para ElectroHuila
        return $electroHuilaClient->get('/public/api/v1/files/subdirectories');
    }

    public function useProductionClient()
    {
        $productionClient = app('httpclient.production');
        // Usar cliente de producción
        return $productionClient->get('/api/data');
    }
}

// Uso en controlador
class ApiController 
{
    public function __construct(
        private ElectroHuilaApiService $apiService
    ) {}

    public function getSubdirectories()
    {
        $subdirectories = $this->apiService->obtenerSubdirectorios();
        return response()->json($subdirectories);
    }

    public function createSolicitation(Request $request)
    {
        $result = $this->apiService->crearSolicitud($request->all());
        
        return $result 
            ? response()->json($result, 201)
            : response()->json(['error' => 'Error al crear solicitud'], 500);
    }
}
