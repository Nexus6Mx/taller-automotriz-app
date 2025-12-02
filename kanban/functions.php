<?php
// Función de conexión a la BD
function db_connect($servername, $username, $password, $dbname) {
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database connection error: ' . $e->getMessage()]);
        exit;
    }
}

// Función para registrar actividad
function log_activity($conn, $task_id, $user_id, $activity_text) {
    $stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_id, activity) VALUES (?, ?, ?)");
    $uid = $user_id ? $user_id : null;
    $stmt->bind_param("iis", $task_id, $uid, $activity_text);
    $stmt->execute();
    $stmt->close();
}

// --- Helpers de autenticación y autorización ---
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user_name() {
    return $_SESSION['user_name'] ?? null;
}

// Determina si el usuario actual es administrador según la lista en config.php
function is_admin() {
    // $admin_users viene de config.php
    global $admin_users;
    $name = current_user_name();
    if (!$name) return false;
    if (!is_array($admin_users)) return false;
    return in_array($name, $admin_users, true);
}

// Verifica si el usuario puede gestionar un tablero: dueño, miembro invitado o admin
function can_manage_board($conn, $board_id, $user_id) {
    if (!$user_id) return false;
    if (is_admin()) return true; // override para administradores

    // Dueño del tablero
    $stmt = $conn->prepare("SELECT 1 FROM boards WHERE id = ? AND owner_user_id = ?");
    $stmt->bind_param("ii", $board_id, $user_id);
    $stmt->execute();
    $is_owner = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    if ($is_owner) return true;

    // Miembro invitado del tablero
    $stmt = $conn->prepare("SELECT 1 FROM board_users WHERE board_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $board_id, $user_id);
    $stmt->execute();
    $is_member = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();

    return $is_member;
}

// Dada una columna, devuelve board_id o null
function get_board_id_by_column($conn, $column_id) {
    $stmt = $conn->prepare("SELECT board_id FROM `columns` WHERE id = ?");
    $stmt->bind_param("i", $column_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res['board_id'] ?? null;
}

// Dada una tarea, devuelve [board_id, column_id] o [null, null]
function get_board_and_column_by_task($conn, $task_id) {
    $stmt = $conn->prepare("SELECT c.board_id, t.column_id FROM tasks t JOIN `columns` c ON t.column_id = c.id WHERE t.id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($res) {
        return [$res['board_id'], $res['column_id']];
    }
    return [null, null];
}
?>