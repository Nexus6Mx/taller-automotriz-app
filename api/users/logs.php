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

// Sólo administradores pueden ver logs
if ($user['role'] !== 'Administrador') {
    http_response_code(403);
    echo json_encode(["message" => "Permisos insuficientes."]);
    exit();
}

try {
    $query = "SELECT a.*, u.email as actor_email FROM audit_logs a LEFT JOIN users u ON a.actor_user_id = u.id ORDER BY a.created_at DESC LIMIT 100";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data'=>$logs], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message"=>"Error: " . $e->getMessage()]);
}

?>
