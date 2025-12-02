<?php
// actions/update_board.php

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$board_id = $data['id'] ?? null;
$new_name = $data['name'] ?? null;

if (empty($board_id) || empty($new_name)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos para actualizar el tablero.']);
    exit;
}

// Verificación de seguridad: El usuario debe ser el propietario del tablero.
$stmt = $conn->prepare("SELECT owner_user_id FROM boards WHERE id = ?");
$stmt->bind_param("i", $board_id);
$stmt->execute();
$result = $stmt->get_result();
if ($board = $result->fetch_assoc()) {
    if ($board['owner_user_id'] != $user_id) {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para modificar este tablero.']);
        exit;
    }
} else {
    http_response_code(404); // Not Found
    echo json_encode(['status' => 'error', 'message' => 'El tablero no existe.']);
    exit;
}
$stmt->close();

// Actualizar el nombre del tablero
$stmt = $conn->prepare("UPDATE boards SET name = ? WHERE id = ?");
$stmt->bind_param("si", $new_name, $board_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Tablero actualizado con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el tablero.']);
}

$stmt->close();
?>