<?php
/**
 * Configuración para PRODUCCIÓN
 * Sistema de Cotizaciones Proveedores V2
 */

// Configuración de base de datos PRODUCCIÓN
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u185421649_scpv');
define('DB_USER', 'u185421649_user_scpv');
define('DB_PASS', 'Chckcl740620');
define('DB_CHARSET', 'utf8mb4');

// Configuración de sesiones
define('SESSION_TIMEOUT', 3600); // 1 hora

// Configuración de aplicación
define('APP_NAME', 'Sistema de Cotizaciones Proveedores V2');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'production'); // IMPORTANTE: Producción

// Definir rutas principales
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('SRC_PATH')) define('SRC_PATH', ROOT_PATH . '/src');
if (!defined('CONFIG_PATH')) define('CONFIG_PATH', ROOT_PATH . '/config');
if (!defined('ASSETS_PATH')) define('ASSETS_PATH', ROOT_PATH . '/assets');
if (!defined('LOGS_PATH')) define('LOGS_PATH', ROOT_PATH . '/logs');

// Configuración de logs
define('LOG_ERRORS', true);
define('LOG_FILE', LOGS_PATH . '/app.log');

// Configuración de errores para PRODUCCIÓN
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0); // NO mostrar errores en producción
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);

// Timezone
date_default_timezone_set('America/Mexico_City');
