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

try {
    // 1. Buscar al usuario por email
    $query = "SELECT id, email, password_hash FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        http_response_code(401); // No autorizado
        echo json_encode(["message" => "Credenciales incorrectas."]);
        exit();
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verificar que la contraseña coincida con el hash almacenado
    if (!password_verify($data->password, $user['password_hash'])) {
        http_response_code(401); // No autorizado
        echo json_encode(["message" => "Credenciales incorrectas."]);
        exit();
    }

    // 3. Actualizar la fecha del último inicio de sesión
    $update_query = "UPDATE users SET last_login = NOW() WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":id", $user['id']);
    $update_stmt->execute();

    // 4. Crear un nuevo token de sesión
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

    // (Opcional pero recomendado) Limpiar sesiones antiguas para este usuario
    $clean_query = "DELETE FROM sessions WHERE user_id = :user_id";
    $clean_stmt = $db->prepare($clean_query);
    $clean_stmt->bindParam(":user_id", $user['id']);
    $clean_stmt->execute();

    // Crear la nueva sesión
    $session_query = "INSERT INTO sessions (user_id, token, expires_at) VALUES (:user_id, :token, :expires)";
    $session_stmt = $db->prepare($session_query);
    $session_stmt->bindParam(":user_id", $user['id']);
    $session_stmt->bindParam(":token", $token);
    $session_stmt->bindParam(":expires", $expires);
    $session_stmt->execute();

    // 5. Enviar respuesta exitosa
    http_response_code(200);
    echo json_encode([
        "message" => "Inicio de sesión exitoso.",
        "token" => $token,
        "user_id" => $user['id'],
        "email" => $user['email']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error en el inicio de sesión: " . $e->getMessage()]);
}
?>

