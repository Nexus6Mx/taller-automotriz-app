<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';
include_once 'log_audit.php';

$database = new Database();
$db = $database->getConnection();

$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
$actor = verifyToken($db, $token);

if (!$actor) {
    http_response_code(401);
    echo json_encode(["message" => "Acceso no autorizado."]);
    exit();
}

// Sólo administradores pueden eliminar usuarios
if ($actor['role'] !== 'Administrador') {
    http_response_code(403);
    echo json_encode(["message" => "Permisos insuficientes."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(["message" => "id es obligatorio."]);
    exit();
}

try {
    // Opcional: mover a tabla de deleted users en vez de borrar definitivamente. Aquí borramos.
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(':id', $data->id);
    if ($stmt->execute()) {
        // Borrar sesiones del usuario
        $del = $db->prepare("DELETE FROM sessions WHERE user_id = :uid");
        $del->bindParam(':uid', $data->id);
        $del->execute();

        log_audit($db, $actor['id'], 'eliminar_usuario', 'users', $data->id, null);
        echo json_encode(["message"=>"Usuario eliminado."]);
    } else {
        throw new Exception('No se pudo eliminar.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message"=>"Error: " . $e->getMessage()]);
}

?>
