<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';

$database = new Database();
$db = $database->getConnection();

$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$token = $authHeader ? str_replace('Bearer ', '', $authHeader) : '';
// Fallbacks: allow token via query string or JSON body
if (!$token) {
    if (!empty($_COOKIE['authToken'])) {
        $token = $_COOKIE['authToken'];
    }
}
if (!$token) {
    if (!empty($_GET['token'])) {
        $token = $_GET['token'];
    } else {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $j = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($j['token'])) {
                $token = $j['token'];
            }
        }
    }
}
$user = verifyToken($db, $token);

if (!$user) {
    http_response_code(401);
    echo json_encode(["message" => "Acceso no autorizado."]);
    exit();
}

// Sólo administradores pueden listar usuarios
if ($user['role'] !== 'Administrador') {
    http_response_code(403);
    echo json_encode(["message" => "Permisos insuficientes. Rol actual: " . ($user['role'] ?? 'desconocido')]);
    exit();
}

try {
    $query = "SELECT id, email, role, active, created_at, last_login FROM users ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $users], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error al obtener usuarios: " . $e->getMessage()]);
}
?>
