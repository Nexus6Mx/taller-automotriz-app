<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';

$database = new Database();
$db = $database->getConnection();

$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
$user = verifyToken($db, $token);

if (!$user) {
    http_response_code(401);
    echo json_encode(["message" => "Acceso no autorizado."]);
    exit();
}

// Sólo administradores pueden listar usuarios
if ($user['role'] !== 'Administrador') {
    http_response_code(403);
    echo json_encode(["message" => "Permisos insuficientes."]);
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
