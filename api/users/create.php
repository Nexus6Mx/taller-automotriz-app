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

// Sólo administradores pueden crear usuarios
if ($actor['role'] !== 'Administrador') {
    http_response_code(403);
    echo json_encode(["message" => "Permisos insuficientes."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->password) || empty($data->role)) {
    http_response_code(400);
    echo json_encode(["message" => "email, password y role son obligatorios."]);
    exit();
}

// Validar contraseña segura (mínimos simples: longitud >= 8, mayúscula, minúscula, número)
$pwd = $data->password;
if (strlen($pwd) < 8 || !preg_match('/[A-Z]/', $pwd) || !preg_match('/[a-z]/', $pwd) || !preg_match('/[0-9]/', $pwd)) {
    http_response_code(400);
    echo json_encode(["message" => "La contraseña debe tener al menos 8 caracteres, con mayúscula, minúscula y número."]);
    exit();
}

try {
    // Verificar duplicado
    $check = $db->prepare("SELECT id FROM users WHERE email = :email");
    $check->bindParam(':email', $data->email);
    $check->execute();
    if ($check->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "El correo ya está registrado."]);
        exit();
    }

    $password_hash = password_hash($pwd, PASSWORD_BCRYPT);
    $role = $data->role;
    $active = isset($data->active) ? ($data->active ? 1 : 0) : 1;

    $insert = $db->prepare("INSERT INTO users (email, password_hash, role, active) VALUES (:email, :password, :role, :active)");
    $insert->bindParam(':email', $data->email);
    $insert->bindParam(':password', $password_hash);
    $insert->bindParam(':role', $role);
    $insert->bindParam(':active', $active);

    if ($insert->execute()) {
        $newId = $db->lastInsertId();
        // Registrar en audit
        log_audit($db, $actor['id'], 'crear_usuario', 'users', $newId, json_encode(['email'=>$data->email,'role'=>$role]));

        http_response_code(201);
        echo json_encode(["message"=>"Usuario creado.", "id"=>$newId]);
    } else {
        throw new Exception('No se pudo crear el usuario.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message"=>"Error: " . $e->getMessage()]);
}

?>
