<?php
// actions/add_task.php

// 1. Verificación de autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión.']);
    exit;
}

$user_id_session = $_SESSION['user_id'];
$column_id = $data['column_id'] ?? null;

if (!$column_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de columna no proporcionado.']);
    exit;
}

// 2. Verificación de autorización (el usuario debe ser dueño del tablero)
// Obtener board_id de la columna y validar permisos (owner, miembro o admin)
$board_id = get_board_id_by_column($conn, $column_id);
if (!$board_id) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'La columna no existe.']);
    exit;
}
if (!can_manage_board($conn, $board_id, $user_id_session)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para añadir tareas aquí.']);
    exit;
}

// 3. Lógica para añadir la tarea

// Calcular la siguiente posición para la nueva tarea en la columna
$pos_stmt = $conn->prepare("SELECT COALESCE(MAX(position), -1) + 1 AS next_pos FROM tasks WHERE column_id = ?");
$pos_stmt->bind_param("i", $column_id);
$pos_stmt->execute();
$next_pos = $pos_stmt->get_result()->fetch_assoc()['next_pos'];
$pos_stmt->close();

// Asignar valores, usando defaults para los opcionales
$title = $data['title'] ?? 'Nueva Tarea';
$description = $data['description'] ?? '';
$assigned_user_id = empty($data['user_id']) ? null : $data['user_id'];
$due_date = empty($data['due_date']) ? null : $data['due_date'];
$priority = $data['priority'] ?? 'Media';
$color = $data['color'] ?? '#FFFFFF'; // Default color blanco

$stmt = $conn->prepare("INSERT INTO tasks (column_id, title, description, user_id, due_date, priority, color, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ississsi", $column_id, $title, $description, $assigned_user_id, $due_date, $priority, $color, $next_pos);

if ($stmt->execute()) {
    $task_id = $stmt->insert_id;
    
    // Devolver el objeto de la tarea recién creada para que el frontend pueda renderizarlo
    $new_task_stmt = $conn->prepare("SELECT t.*, u.name as user_name FROM tasks t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $new_task_stmt->bind_param("i", $task_id);
    $new_task_stmt->execute();
    $new_task = $new_task_stmt->get_result()->fetch_assoc();
    
    // Inicializar arrays para que la estructura sea consistente
    $new_task['subtasks'] = [];
    $new_task['attachments'] = [];
    $new_task['comments'] = [];
    $new_task['tags'] = [];

    http_response_code(201);
    echo json_encode(['status' => 'success', 'data' => $new_task]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error del servidor: ' . $stmt->error]);
}
$stmt->close();

?>