# AppHttpClient - Configuración SSL

## Métodos para Configurar SSL

La clase `AppHttpClient` ahora incluye métodos para configurar la verificación SSL:

### Desactivar Verificación SSL
```php
$client = new AppHttpClient();
$client->disableSSLVerification();
```

### Activar Verificación SSL
```php
$client = new AppHttpClient();
$client->enableSSLVerification(); // Por defecto está activada
```

### Configurar Opciones SSL Personalizadas
```php
$client = new AppHttpClient();
$client->setSSLOptions([
    'verify' => false,
    'curl' => [
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]
]);
```

## Ejemplos de Uso

### 1. Para APIs de Desarrollo (Sin SSL)
```php
use BMCLibrary\AppHttpClient\AppHttpClient;

$client = new AppHttpClient();
$client->setBaseUrl('https://qa-enlinea.electrohuila.com.co:8013')
       ->disableSSLVerification()
       ->setTimeout(60);

// Ahora las peticiones no verificarán SSL
$response = $client->get('/public/api/v1/files/subdirectories');
```

### 2. Para APIs de Producción (Con SSL)
```php
$client = new AppHttpClient();
$client->setBaseUrl('https://api.production.com')
       ->enableSSLVerification() // Opcional, por defecto está activo
       ->setTimeout(30);

$response = $client->get('/api/v1/data');
```

### 3. Configuración Mixta
```php
$client = new AppHttpClient();
$client->setBaseUrl('https://qa-server.com')
       ->setTimeout(45)
       ->setDefaultHeaders([
           'Authorization' => 'Bearer ' . $token,
           'Accept' => 'application/json'
       ]);

// Para desarrollo, desactivar SSL
if (env('APP_ENV') === 'local') {
    $client->disableSSLVerification();
}

$response = $client->post('/api/v1/solicitations', $data);
```

### 4. Configuración Avanzada SSL
```php
$client = new AppHttpClient();
$client->setSSLOptions([
    'verify' => '/path/to/cacert.pem', // Ruta al certificado CA
    'cert' => '/path/to/client.pem',   // Certificado del cliente
    'ssl_key' => '/path/to/client.key' // Clave privada del cliente
]);
```

## Configuración en Service Provider

```php
// En tu AppServiceProvider
public function register()
{
    $this->app->singleton(AppHttpClient::class, function () {
        $client = new AppHttpClient();
        
        if (config('app.env') === 'local') {
            $client->disableSSLVerification();
        }
        
        return $client->setTimeout(config('http.timeout', 30));
    });
}
```

## Variables de Entorno

Puedes agregar estas variables a tu `.env`:

```env
HTTP_SSL_VERIFY=false
HTTP_TIMEOUT=60
API_BASE_URL=https://qa-enlinea.electrohuila.com.co:8013
```

Y usar en tu configuración:

```php
$client = new AppHttpClient();
$client->setBaseUrl(env('API_BASE_URL'))
       ->setTimeout(env('HTTP_TIMEOUT', 30));

if (!env('HTTP_SSL_VERIFY', true)) {
    $client->disableSSLVerification();
}
```

## Solución Inmediata para tu Error

Para resolver tu error específico:

```php
use BMCLibrary\AppHttpClient\AppHttpClient;

$client = new AppHttpClient();
$client->setBaseUrl('https://qa-enlinea.electrohuila.com.co:8013')
       ->disableSSLVerification() // Esto soluciona el error SSL
       ->setTimeout(60)
       ->setDefaultHeaders([
           'Content-Type' => 'application/json',
           'Accept' => 'application/json'
       ]);

try {
    $response = $client->get('/public/api/v1/files/subdirectories');
    
    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getBody(), true);
        // Procesar datos...
    }
} catch (\RuntimeException $e) {
    \Log::error('HTTP Client Error: ' . $e->getMessage());
    // Manejar error...
}
```

**Nota**: Desactivar la verificación SSL solo debería usarse en entornos de desarrollo. En producción, es recomendable configurar correctamente los certificados SSL.
