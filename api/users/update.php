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

// Sólo administradores pueden actualizar usuarios
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
    $fields = [];
    if (!empty($data->email)) {
        $fields[] = "email = :email";
    }
    if (!empty($data->password)) {
        // validar password
        $pwd = $data->password;
        if (strlen($pwd) < 8 || !preg_match('/[A-Z]/', $pwd) || !preg_match('/[a-z]/', $pwd) || !preg_match('/[0-9]/', $pwd)) {
            http_response_code(400);
            echo json_encode(["message" => "La contraseña debe tener al menos 8 caracteres, con mayúscula, minúscula y número."]);
            exit();
        }
        $fields[] = "password_hash = :password";
    }
    if (isset($data->role)) {
        $fields[] = "role = :role";
    }
    if (isset($data->active)) {
        $fields[] = "active = :active";
    }

    if (count($fields) === 0) {
        http_response_code(400);
        echo json_encode(["message" => "Nada para actualizar."]);
        exit();
    }

    $sql = "UPDATE users SET " . implode(',', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    if (!empty($data->email)) $stmt->bindParam(':email', $data->email);
    if (!empty($data->password)) { $hash = password_hash($data->password, PASSWORD_BCRYPT); $stmt->bindParam(':password', $hash); }
    if (isset($data->role)) $stmt->bindParam(':role', $data->role);
    if (isset($data->active)) {
        $a = $data->active ? 1 : 0;
        $stmt->bindParam(':active', $a, PDO::PARAM_INT);
    }
    $stmt->bindParam(':id', $data->id);

    if ($stmt->execute()) {
        // Si se desactiva un usuario, forzar logout: eliminar sesiones
        if (isset($data->active) && !$data->active) {
            $del = $db->prepare("DELETE FROM sessions WHERE user_id = :uid");
            $del->bindParam(':uid', $data->id);
            $del->execute();
        }

        log_audit($db, $actor['id'], 'actualizar_usuario', 'users', $data->id, json_encode($data));
        echo json_encode(["message"=>"Usuario actualizado."]);
    } else {
        throw new Exception('No se pudo actualizar.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message"=>"Error: " . $e->getMessage()]);
}

?>
