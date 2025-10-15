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
$user_id = $user['id'];
$user_role = isset($user['role']) ? $user['role'] : 'Operador';
$user_active = isset($user['active']) ? $user['active'] : true;
if (!$user_active) {
    http_response_code(403);
    echo json_encode(["message"=>"Usuario desactivado."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data) || empty($data->id)) {
    http_response_code(400);
    echo json_encode(["message" => "ID de la orden es requerido para eliminar."]);
    exit();
}

try {
    // La eliminación en cascada en la DB se encargará de los `order_items`.
    $query = "DELETE FROM orders WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(["message" => "Orden eliminada exitosamente."]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Orden no encontrada o no pertenece al usuario."]);
        }
    } else {
        throw new Exception("Error en la base de datos al eliminar.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error al eliminar la orden: " . $e->getMessage()]);
}
?>
