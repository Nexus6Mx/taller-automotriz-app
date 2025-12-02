<?php
// actions/get_boards.php

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Se obtienen todos los tableros, ya no se filtra por propietario.
$stmt = $conn->prepare("SELECT id, name FROM boards ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();

$boards = [];
while ($row = $result->fetch_assoc()) {
    $boards[] = $row;
}

$stmt->close();

echo json_encode(['status' => 'success', 'data' => $boards]);
?>