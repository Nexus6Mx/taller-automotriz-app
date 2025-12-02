<?php
/**
 * Punto de entrada principal de la aplicación - PRODUCCIÓN
 * Sistema de Cotizaciones Proveedores V2
 */

// Iniciar sesión antes que cualquier salida
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir constantes del proyecto
define('ROOT_PATH', __DIR__);
define('SRC_PATH', ROOT_PATH . '/src');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Autoloader simple
spl_autoload_register(function($class) {
    $file = SRC_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Cargar configuración
require_once CONFIG_PATH . '/config.php';

// Crear directorio de logs si no existe
if (!is_dir(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0777, true);
}

// Inicializar y ejecutar la aplicación
try {
    $app = new Core\Application();
    $app->run();
} catch (Exception $e) {
    // En producción, registrar el error y mostrar mensaje genérico
    error_log('Error crítico: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    
    if (APP_ENV === 'production') {
        http_response_code(500);
        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - ' . APP_NAME . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-exclamation-triangle text-warning fa-4x mb-4"></i>
                        <h2>Error del Sistema</h2>
                        <p class="text-muted">
                            Lo sentimos, ha ocurrido un error. Por favor, intente nuevamente más tarde.
                        </p>
                        <a href="/" class="btn btn-primary mt-3">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    } else {
        // En desarrollo, mostrar el error
        echo '<pre>';
        echo 'Error: ' . $e->getMessage() . "\n";
        echo 'Archivo: ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo 'Traza: ' . $e->getTraceAsString();
        echo '</pre>';
    }
}
