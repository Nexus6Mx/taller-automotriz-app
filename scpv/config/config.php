<?php
/**
 * Configuración general de la aplicación
 * Sistema de Cotizaciones Proveedores V2
 * 
 * IMPORTANTE: Para producción, copiar config.production.php como config.php
 * o definir las variables de entorno apropiadas
 */

// Detectar entorno (Docker vs Producción)
$isDocker = file_exists('/.dockerenv') || getenv('DOCKER_CONTAINER') !== false;

// Configuración de base de datos
if ($isDocker) {
    // Entorno de desarrollo con Docker
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'db');
    define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
    define('DB_NAME', $_ENV['DB_DATABASE'] ?? 'scpv_db');
    define('DB_USER', $_ENV['DB_USERNAME'] ?? 'scpv_user');
    define('DB_PASS', $_ENV['DB_PASSWORD'] ?? 'scpv_password');
    define('APP_ENV', 'development');
    
    // Mostrar errores en desarrollo
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Entorno de producción (servidor tradicional)
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'u185421649_scpv');
    define('DB_USER', 'u185421649_user_scpv');
    define('DB_PASS', 'Chckcl740620');
    define('APP_ENV', 'production');
    
    // Ocultar errores en producción
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

define('DB_CHARSET', 'utf8mb4');

// Configuración de sesiones
define('SESSION_TIMEOUT', 3600); // 1 hora

// Configuración de aplicación
define('APP_NAME', 'Sistema de Cotizaciones Proveedores V2');
define('APP_VERSION', '1.0.0');

// Definir rutas principales (solo si no están definidas)
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('SRC_PATH')) define('SRC_PATH', ROOT_PATH . '/src');
if (!defined('CONFIG_PATH')) define('CONFIG_PATH', ROOT_PATH . '/config');
if (!defined('ASSETS_PATH')) define('ASSETS_PATH', ROOT_PATH . '/assets');
if (!defined('LOGS_PATH')) define('LOGS_PATH', ROOT_PATH . '/logs');

// Configuración de logs
define('LOG_ERRORS', true);
define('LOG_FILE', LOGS_PATH . '/app.log');

// Configurar log de errores
if (APP_ENV === 'production') {
    ini_set('error_log', LOG_FILE);
}

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de seguridad
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'scpv_default_key_2024');
define('CSRF_TOKEN_EXPIRE', 3600);

// Configuración de uploads
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);