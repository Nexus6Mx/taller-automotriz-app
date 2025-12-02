<?php
// actions/update_board_name.php

$user_id = $_SESSION['user_id'];
$board_id = $data['board_id'] ?? 0;
$new_name = $data['name'] ?? '';

if (empty($new_name) || empty($board_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos para actualizar el tablero.']);
    exit;
}

// Verificamos que el usuario sea el dueño del tablero para poder editarlo
$stmt = $conn->prepare("UPDATE boards SET name = ? WHERE id = ? AND owner_user_id = ?");
$stmt->bind_param("sii", $new_name, $board_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Nombre del tablero actualizado.']);
    } else {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para editar este tablero o no existe.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el tablero.']);
}
$stmt->close();
?>