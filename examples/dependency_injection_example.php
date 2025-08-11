<?php

namespace App\Services;

use BMCLibrary\AppHttpClient\Abstractions\HttpClientInterface;
use Illuminate\Support\Facades\Log;

class FileApiService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {
        // El HttpClient ya viene configurado desde el Service Provider
        // con SSL desactivado según tu configuración en .env
    }

    /**
     * Obtener subdirectorios
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
     * Si necesitas configuración SSL específica para este servicio
     */
    public function configureForDevelopment(): self
    {
        $this->httpClient->disableSSLVerification();
        return $this;
    }

    public function configureForProduction(): self
    {
        $this->httpClient->enableSSLVerification();
        return $this;
    }

    /**
     * Configurar SSL con opciones personalizadas
     */
    public function configureSSL(array $options): self
    {
        $this->httpClient->setSSLOptions($options);
        return $this;
    }
}

// Ejemplo de uso en un controlador
class FileController extends Controller
{
    public function __construct(
        private FileApiService $fileApiService
    ) {}

    public function getSubdirectories()
    {
        try {
            $subdirectories = $this->fileApiService->getSubdirectories();
            return response()->json($subdirectories);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener subdirectorios'], 500);
        }
    }

    public function uploadFile(Request $request)
    {
        try {
            $filePath = $request->file('file')->getPathname();
            $metadata = $request->only(['name', 'description']);
            
            $result = $this->fileApiService->uploadFile($filePath, $metadata);
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al subir archivo'], 500);
        }
    }
}
