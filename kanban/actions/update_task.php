<?php
// actions/update_task.php

// Verificar autenticación
$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión.']);
    exit;
}

// Permisos: el usuario debe poder gestionar el tablero de la tarea o ser admin
$task_id = $data['id'] ?? null;
if (!$task_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de tarea no proporcionado.']);
    exit;
}

list($board_id, $col_id) = get_board_and_column_by_task($conn, $task_id);
if (!$board_id) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'La tarea no existe.']);
    exit;
}
if (!can_manage_board($conn, $board_id, $current_user_id)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para editar esta tarea.']);
    exit;
}

$title = isset($data['title']) ? trim((string)$data['title']) : '';
if ($title === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'El título de la tarea es obligatorio.']);
    exit;
}

$description = isset($data['description']) ? (string)$data['description'] : '';
$user_id = ($data['user_id'] ?? '') === '' ? null : (int)$data['user_id'];
$due_date = isset($data['due_date']) && $data['due_date'] !== '' ? (string)$data['due_date'] : null;
$priority = isset($data['priority']) ? (string)$data['priority'] : 'Media';
$color = isset($data['color']) ? (string)$data['color'] : '#FFFFFF';

$stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, user_id = ?, due_date = ?, priority = ?, color = ? WHERE id = ?");
$stmt->bind_param("ssisssi", $title, $description, $user_id, $due_date, $priority, $color, $task_id);
if ($stmt->execute()) {
    log_activity($conn, $task_id, $current_user_id, "actualizó los detalles de la tarea.");
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
?>