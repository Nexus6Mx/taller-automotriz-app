<?php
// api/utils/cors.php

// --- CONFIGURACIÓN DE ORÍGENES PERMITIDOS (CORS) ---
// Lista de dominios autorizados para hacer peticiones a esta API.
$allowed_origins = [
    'https://errautomotriz.online',           // Producción
    'https://www.errautomotriz.online',       // Variante con www
    'https://app.errautomotriz.online',       // Subdominio app (si aplica)
    'http://localhost',                       // Desarrollo local sin puerto
    'http://localhost:8888'                   // Desarrollo local con puerto
];
// ----------------------------------------------------

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Verifica si el origen de la petición está en nuestra lista blanca.
    if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
        // Si está, responde afirmativamente reflejando ese origen.
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // Cache por 1 día
    }
}

// Headers para peticiones OPTIONS (pre-vuelo)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

// Establece el tipo de contenido para todas las respuestas
header("Content-Type: application/json; charset=UTF-8");
?>
