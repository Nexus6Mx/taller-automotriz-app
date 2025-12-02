<?php
// actions/move_task.php

// Permisos: el usuario debe poder gestionar el tablero origen y destino o ser admin
$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión.']);
    exit;
}

$task_id = $data['taskId'] ?? null;
$new_column_id = $data['newColumnId'] ?? null;
$new_index = $data['newIndex'] ?? 0;
$old_column_id = $data['oldColumnId'] ?? null;
$old_index = $data['oldIndex'] ?? 0;

// (debug traces removed for production)

if (!$task_id || !$new_column_id || !$old_column_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
    exit;
}

list($task_board_id, $task_col_id) = get_board_and_column_by_task($conn, $task_id);
$dest_board_id = get_board_id_by_column($conn, $new_column_id);

if (!$task_board_id || !$dest_board_id) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Tarea o columna destino no encontrada.']);
    exit;
}

if (!can_manage_board($conn, $task_board_id, $current_user_id) || !can_manage_board($conn, $dest_board_id, $current_user_id)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para mover esta tarea.']);
    exit;
}

$conn->begin_transaction();
try {
    $find_col_stmt = $conn->prepare("SELECT c.title FROM `columns` c JOIN tasks t ON c.id = t.column_id WHERE t.id = ?");
    $find_col_stmt->bind_param("i", $task_id);
    $find_col_stmt->execute();
    $old_column_title = $find_col_stmt->get_result()->fetch_assoc()['title'] ?? '';
    $find_col_stmt->close();

    // Reordenar saliente
    $stmt = $conn->prepare("UPDATE tasks SET position = position - 1 WHERE column_id = ? AND position > ?");
    $stmt->bind_param("ii", $old_column_id, $old_index);
    $stmt->execute();

    // Hacer espacio en destino
    $stmt = $conn->prepare("UPDATE tasks SET position = position + 1 WHERE column_id = ? AND position >= ?");
    $stmt->bind_param("ii", $new_column_id, $new_index);
    $stmt->execute();

    // Mover la tarea
    $stmt = $conn->prepare("UPDATE tasks SET column_id = ?, position = ? WHERE id = ?");
    $stmt->bind_param("iii", $new_column_id, $new_index, $task_id);
    $stmt->execute();

    $conn->commit();

    $find_col_stmt = $conn->prepare("SELECT title FROM `columns` WHERE id = ?");
    $find_col_stmt->bind_param("i", $new_column_id);
    $find_col_stmt->execute();
    $new_column_title = $find_col_stmt->get_result()->fetch_assoc()['title'] ?? '';
    $find_col_stmt->close();

    log_activity($conn, $task_id, $current_user_id, "movió la tarea de '".$old_column_title."' a '".$new_column_title."'.");
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>