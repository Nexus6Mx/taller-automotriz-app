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
$user_active = isset($user['active']) ? $user['active'] : true;
if (!$user_active) {
    http_response_code(403);
    echo json_encode(["message"=>"Usuario desactivado."]);
    exit();
}

try {
    $query = "SELECT * FROM vehicles WHERE user_id = :user_id ORDER BY client_name ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['data' => $vehicles]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error al obtener los vehículos: " . $e->getMessage()]);
}
?>
