<?php
// La inclusión del CORS debe ser la primera línea para que los encabezados se envíen correctamente.
include_once '../utils/cors.php';

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

// Validación de datos de entrada
if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "El correo electrónico y la contraseña son obligatorios."]);
    exit();
}

if (strlen($data->password) < 6) {
    http_response_code(400);
    echo json_encode(["message" => "La contraseña debe tener al menos 6 caracteres."]);
    exit();
}

try {
    // 1. Verificar si el email ya existe en la base de datos
    $check_query = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":email", $data->email);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        http_response_code(409); // 409 Conflict es más apropiado para duplicados
        echo json_encode(["message" => "El correo electrónico ya está registrado."]);
        exit();
    }

    // 2. Si no existe, registrar el nuevo usuario
    $query = "INSERT INTO users (email, password_hash) VALUES (:email, :password)";
    $stmt = $db->prepare($query);

    // Encriptar la contraseña antes de guardarla
    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $password_hash);

    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();

        // 3. Crear un token de sesión para el nuevo usuario
        $token = bin2hex(random_bytes(32)); // Token seguro
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        $session_query = "INSERT INTO sessions (user_id, token, expires_at) VALUES (:user_id, :token, :expires)";
        $session_stmt = $db->prepare($session_query);
        $session_stmt->bindParam(":user_id", $user_id);
        $session_stmt->bindParam(":token", $token);
        $session_stmt->bindParam(":expires", $expires);
        $session_stmt->execute();

        // 4. Enviar respuesta exitosa
        http_response_code(201); // 201 Created
        echo json_encode([
            "message" => "Usuario creado exitosamente.",
            "token" => $token,
            "user_id" => $user_id,
            "email" => $data->email
        ]);
    } else {
         throw new Exception("No se pudo ejecutar la inserción del usuario.");
    }

} catch (Exception $e) {
    http_response_code(500); // Error interno del servidor
    echo json_encode(["message" => "Error al crear el usuario: " . $e->getMessage()]);
}
?>

