<?php

/**
 * Ejemplo usando HttpClientFactory
 * 
 * Esta es la forma más sencilla de usar el cliente HTTP en tu aplicación Laravel
 */

use BMCLibrary\Utils\HttpClientFactory;

class SolicitudService
{
    private $httpClient;

    public function __construct()
    {
        // Crear cliente específico para ElectroHuila con configuración automática
        $this->httpClient = HttpClientFactory::createElectroHuilaClient();
    }

    public function obtenerSubdirectorios()
    {
        try {
            $response = $this->httpClient->get('/public/api/v1/files/subdirectories');

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody(), true);
            }

            return null;
        } catch (\Exception $e) {
            report($e); // Laravel error reporting
            return null;
        }
    }

    public function crearSolicitud(array $data)
    {
        try {
            $response = $this->httpClient->post('/public/api/v1/solicitations', $data);

            if ($response->getStatusCode() === 201) {
                return json_decode($response->getBody(), true);
            }

            return null;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }
}

// Uso en un controlador o servicio
class SolicitudController extends Controller
{
    public function index()
    {
        $service = new SolicitudService();
        $subdirectories = $service->obtenerSubdirectorios();

        return response()->json($subdirectories);
    }

    public function store(Request $request)
    {
        $service = new SolicitudService();
        $result = $service->crearSolicitud($request->all());

        if ($result) {
            return response()->json($result, 201);
        }

        return response()->json(['error' => 'Error al crear solicitud'], 500);
    }
}
