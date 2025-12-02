<?php
// actions/create_board.php

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$board_name = $data['name'] ?? null;

if (empty($board_name)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'El nombre del tablero no puede estar vacío.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO boards (name, owner_user_id) VALUES (?, ?)");
$stmt->bind_param("si", $board_name, $user_id);

if ($stmt->execute()) {
    $new_board_id = $stmt->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Tablero creado con éxito.',
        'data' => [
            'id' => $new_board_id,
            'name' => $board_name
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al crear el tablero.']);
}

$stmt->close();
?>