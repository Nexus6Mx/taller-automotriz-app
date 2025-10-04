<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';

$database = new Database();
$db = $database->getConnection();

$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
$user_id = verifyToken($db, $token);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(["message" => "Acceso no autorizado."]);
    exit();
}

try {
    $query = "SELECT * FROM clients WHERE user_id = :user_id ORDER BY name ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['data' => $clients]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error al obtener los clientes: " . $e->getMessage()]);
}
?>
