<?php

/**
 * Ejemplo práctico para resolver el error SSL en tu API
 *
 * Este ejemplo muestra cómo usar AppHttpClient para comunicarte con
 *  sin errores SSL
 */

require_once __DIR__ . '/vendor/autoload.php';

use BMCLibrary\AppHttpClient\AppHttpClient;
use Illuminate\Support\Facades\Log;

class ApiElectroHuilaService
{
    private AppHttpClient $httpClient;

    public function __construct()
    {
        $this->httpClient = new AppHttpClient();
        $this->httpClient
            ->setBaseUrl('')
            ->disableSSLVerification() // Esto resuelve el error SSL
            ->setTimeout(60)
            ->setDefaultHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);
    }

    /**
     * Obtiene subdirectorios
     */
    public function getSubdirectories(): array
    {
        try {
            $response = $this->httpClient->get('/public/api/v1/files/subdirectories');

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody(), true);
            }

            throw new \Exception('Error en la respuesta: ' . $response->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Error al obtener subdirectorios: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Subir archivo
     */
    public function uploadFile(string $filePath, array $metadata = []): array
    {
        try {
            $data = array_merge($metadata, [
                'file' => new \CURLFile($filePath)
            ]);

            $response = $this->httpClient->post('/public/api/v1/files/upload', $data);

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody(), true);
            }

            throw new \Exception('Error al subir archivo: ' . $response->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Error al subir archivo: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear solicitud
     */
    public function createSolicitation(array $solicitation): array
    {
        try {
            $response = $this->httpClient->post('/public/api/v1/solicitations', $solicitation);

            if ($response->getStatusCode() === 201) {
                return json_decode($response->getBody(), true);
            }

            throw new \Exception('Error al crear solicitud: ' . $response->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Error al crear solicitud: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener solicitud por ID
     */
    public function getSolicitation(int $id): array
    {
        try {
            $response = $this->httpClient->get("/public/api/v1/solicitations/{$id}");

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody(), true);
            }

            throw new \Exception('Solicitud no encontrada: ' . $response->getStatusCode());
        } catch (\Exception $e) {
            Log::error("Error al obtener solicitud {$id}: " . $e->getMessage());
            throw $e;
        }
    }
}

// Ejemplo de uso
try {
    $apiService = new ApiElectroHuilaService();

    // Obtener subdirectorios
    $subdirectories = $apiService->getSubdirectories();
    echo "Subdirectorios obtenidos:\n";
    print_r($subdirectories);

    // Crear una solicitud
    $solicitation = [
        'type' => 'nueva',
        'description' => 'Solicitud de prueba',
        'user_id' => 123
    ];

    $newSolicitation = $apiService->createSolicitation($solicitation);
    echo "Solicitud creada:\n";
    print_r($newSolicitation);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
