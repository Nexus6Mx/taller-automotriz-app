<?php
// actions/add_column.php

$board_id = $data['board_id'] ?? null;
if (empty($board_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'El ID del tablero es requerido.']);
    exit;
}

// La posición ahora se calcula dentro del tablero específico
$stmt = $conn->prepare("SELECT COALESCE(MAX(position), -1) + 1 AS next_pos FROM `columns` WHERE board_id = ?");
$stmt->bind_param("i", $board_id);
$stmt->execute(); 
$next_pos = $stmt->get_result()->fetch_assoc()['next_pos'];
$stmt->close();

$stmt = $conn->prepare("INSERT INTO `columns` (title, color, position, wip_limit, board_id) VALUES (?, ?, ?, ?, ?)");
$wip_limit = isset($data['wip_limit']) && $data['wip_limit'] > 0 ? $data['wip_limit'] : 0;
$title = $data['title'] ?? 'Nueva Columna';
$color = $data['color'] ?? '#E0E0E0';

$stmt->bind_param("ssiii", $title, $color, $next_pos, $wip_limit, $board_id);

if ($stmt->execute()) { 
    echo json_encode(['status' => 'success', 'id' => $stmt->insert_id]); 
} else { 
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'message' => $stmt->error]); 
}
$stmt->close();
?>