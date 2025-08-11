<?php

/**
 * Solución Directa para el Error SSL
 *
 * Usar este código para resolver inmediatamente el error:
 * "cURL error 60: SSL certificate problem: unable to get local issuer certificate"
 */

use BMCLibrary\AppHttpClient\AppHttpClient;

// Configurar cliente HTTP sin verificación SSL
$client = new AppHttpClient();
$client->setBaseUrl('https://qa-enlinea.electrohuila.com.co:8013')
       ->disableSSLVerification() // Esto resuelve el error SSL
       ->setTimeout(60);

// Probar la conexión
try {
    $response = $client->get('/public/api/v1/files/subdirectories');
    
    echo "Conexión exitosa!\n";
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Respuesta: " . $response->getBody() . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Si necesitas hacer una petición POST
try {
    $data = [
        'name' => 'test',
        'description' => 'Prueba de conexión'
    ];
    
    $response = $client->post('/public/api/v1/solicitations', $data);
    
    echo "POST exitoso!\n";
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Respuesta: " . $response->getBody() . "\n";
    
} catch (\Exception $e) {
    echo "Error en POST: " . $e->getMessage() . "\n";
}
