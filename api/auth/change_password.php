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

$data = json_decode(file_get_contents('php://input'));
if (empty($data->current_password) || empty($data->new_password)) {
    http_response_code(400);
    echo json_encode(["message"=>"current_password y new_password son obligatorios."]);
    exit();
}

try {
    // Obtener hash actual
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->bindParam(':id', $user['id']);
    $stmt->execute();
    $hash = $stmt->fetchColumn();

    if (!password_verify($data->current_password, $hash)) {
        http_response_code(401);
        echo json_encode(["message"=>"La contraseña actual es incorrecta."]);
        exit();
    }

    // Validar nueva contraseña
    $pwd = $data->new_password;
    if (strlen($pwd) < 8 || !preg_match('/[A-Z]/', $pwd) || !preg_match('/[a-z]/', $pwd) || !preg_match('/[0-9]/', $pwd)) {
        http_response_code(400);
        echo json_encode(["message" => "La contraseña debe tener al menos 8 caracteres, con mayúscula, minúscula y número."]);
        exit();
    }

    $newhash = password_hash($pwd, PASSWORD_BCRYPT);
    $upd = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
    $upd->bindParam(':hash', $newhash);
    $upd->bindParam(':id', $user['id']);
    $upd->execute();

    echo json_encode(["message"=>"Contraseña actualizada."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message"=>"Error: " . $e->getMessage()]);
}

?>
