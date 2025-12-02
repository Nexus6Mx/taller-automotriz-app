<?php
// La inclusión del CORS debe ser la primera línea para que los encabezados se envíen correctamente.
include_once '../utils/cors.php';

include_once '../config/database.php';
include_once '../users/log_audit.php';

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
    $query = "SELECT id, email, role, password_hash, failed_attempts, locked_until, active FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        http_response_code(401); // No autorizado
        echo json_encode(["message" => "Credenciales incorrectas."]);
        exit();
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario está activo
    if (isset($user['active']) && !$user['active']) {
        http_response_code(403);
        echo json_encode(["message" => "Usuario desactivado."]);
        exit();
    }

    // Verificar bloqueo por intentos fallidos
    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        http_response_code(423); // Locked
        echo json_encode(["message" => "Cuenta temporalmente bloqueada. Intenta más tarde."]);
        log_audit($db, $user['id'], 'login_blocked', 'users', $user['id'], null);
        exit();
    }

    // 2. Verificar que la contraseña coincida con el hash almacenado
    if (!password_verify($data->password, $user['password_hash'])) {
        // Incrementar failed_attempts
        $inc = $db->prepare("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = :id");
        $inc->bindParam(':id', $user['id']);
        $inc->execute();

        // Si supera umbral (ej. 5), bloquear por 15 minutos
        $check = $db->prepare("SELECT failed_attempts FROM users WHERE id = :id");
        $check->bindParam(':id', $user['id']);
        $check->execute();
        $count = (int)$check->fetchColumn();
        if ($count >= 5) {
            $lock = $db->prepare("UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE), failed_attempts = 0 WHERE id = :id");
            $lock->bindParam(':id', $user['id']);
            $lock->execute();
            log_audit($db, $user['id'], 'account_locked', 'users', $user['id'], null);
        }

        http_response_code(401); // No autorizado
        echo json_encode(["message" => "Credenciales incorrectas."]);
        log_audit($db, $user['id'], 'login_failed', 'users', $user['id'], null);
        exit();
    }

    // 3. Resetear failed_attempts y actualizar la fecha del último inicio de sesión
    $update_query = "UPDATE users SET last_login = NOW(), failed_attempts = 0, locked_until = NULL WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":id", $user['id']);
    $update_stmt->execute();

    // 4. Crear un nuevo token de sesión
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

    // Limpiar SOLO sesiones expiradas para este usuario (permite múltiples sesiones actuales)
    $clean_query = "DELETE FROM sessions WHERE user_id = :user_id AND expires_at < NOW()";
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

    // Registrar login exitoso en logs
    log_audit($db, $user['id'], 'login_success', 'users', $user['id'], null);

    // 5. Establecer cookie segura con el token para que el navegador la envíe automáticamente
    // Nota: requiere HTTPS para secure=true en producción
    $cookieOptions = [
        'expires' => strtotime($expires),
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    // PHP 7.3+ soporta opciones como array
    @setcookie('authToken', $token, $cookieOptions);

    // 6. Enviar respuesta exitosa
    http_response_code(200);
    echo json_encode([
        "message" => "Inicio de sesión exitoso.",
        "token" => $token,
        "user_id" => $user['id'],
        "email" => $user['email'],
        "role" => isset($user['role']) ? $user['role'] : 'Operador',
        "active" => isset($user['active']) ? (int)$user['active'] : 1
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error en el inicio de sesión: " . $e->getMessage()]);
}
?>

