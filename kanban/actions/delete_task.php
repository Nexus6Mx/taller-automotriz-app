<?php
// actions/delete_task.php

// Verificar autenticación
$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión.']);
    exit;
}

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
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para eliminar esta tarea.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);
if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
?>