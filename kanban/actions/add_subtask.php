<?php
// actions/add_subtask.php

// 1. Verificación de autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión.']);
    exit;
}

$user_id_session = $_SESSION['user_id'];
$task_id = $data['task_id'] ?? null;

if (!$task_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de tarea no proporcionado.']);
    exit;
}

// 2. Verificación de autorización (el usuario debe ser dueño del tablero)
$auth_stmt = $conn->prepare("SELECT b.owner_user_id FROM tasks t JOIN `columns` c ON t.column_id = c.id JOIN boards b ON c.board_id = b.id WHERE t.id = ?");
$auth_stmt->bind_param("i", $task_id);
$auth_stmt->execute();
$result = $auth_stmt->get_result();
if ($board = $result->fetch_assoc()) {
    if ($board['owner_user_id'] != $user_id_session) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para modificar esta tarea.']);
        exit;
    }
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'La tarea no existe.']);
    exit;
}
$auth_stmt->close();

// 3. Lógica para añadir la subtarea
$title = $data['title'] ?? 'Nueva Subtarea';
if (empty(trim($title))) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'El título no puede estar vacío.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO subtasks (task_id, title) VALUES (?, ?)");
$stmt->bind_param("is", $task_id, $title);

if ($stmt->execute()) {
    $subtask_id = $stmt->insert_id;
    $new_subtask = [
        'id' => $subtask_id,
        'task_id' => $task_id,
        'title' => $title,
        'is_completed' => 0
    ];
    echo json_encode(['status' => 'success', 'data' => $new_subtask]);
} else { 
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'message' => $stmt->error]); 
}
$stmt->close();
?>