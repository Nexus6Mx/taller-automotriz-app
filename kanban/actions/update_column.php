<?php
// actions/update_column.php

// 1. Verificación de autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para realizar esta acción.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$column_id = $data['id'] ?? null;

if (!$column_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se proporcionó el ID de la columna.']);
    exit;
}

// 2. Verificación de autorización (permisos)
// Comprobar si el usuario es el propietario del tablero al que pertenece la columna.
$auth_stmt = $conn->prepare("
    SELECT b.owner_user_id 
    FROM `columns` c
    JOIN `boards` b ON c.board_id = b.id
    WHERE c.id = ?
");
$auth_stmt->bind_param("i", $column_id);
$auth_stmt->execute();
$result = $auth_stmt->get_result();
if ($board = $result->fetch_assoc()) {
    if ($board['owner_user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para modificar esta columna.']);
        exit;
    }
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'La columna no fue encontrada.']);
    exit;
}
$auth_stmt->close();

// 3. Si la autorización es exitosa, proceder con la actualización
$stmt = $conn->prepare("UPDATE `columns` SET title = ?, color = ?, wip_limit = ? WHERE id = ?");
$wip_limit = isset($data['wip_limit']) && $data['wip_limit'] >= 0 ? $data['wip_limit'] : 0;
$stmt->bind_param("ssii", $data['title'], $data['color'], $wip_limit, $data['id']);

if ($stmt->execute()) { 
    echo json_encode(['status' => 'success', 'message' => 'Columna actualizada correctamente.']); 
} else { 
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'message' => 'Error del servidor al actualizar la columna: ' . $stmt->error]); 
}
$stmt->close();
?>