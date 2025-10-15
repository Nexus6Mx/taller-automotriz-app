<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once 'verify.php';

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
http_response_code(200);
// Devolver el objeto de usuario directamente para facilitar el consumo desde el frontend
echo json_encode($user);

exit();

