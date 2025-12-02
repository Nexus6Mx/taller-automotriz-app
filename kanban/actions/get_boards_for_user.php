<?php
// actions/get_boards_for_user.php

$user_id = $_SESSION['user_id'];
$boards = [];

// Seleccionamos los tableros que el usuario posee o a los que ha sido invitado
// Por ahora, para simplificar, solo cargaremos los que posee.
$stmt = $conn->prepare("SELECT id, name FROM boards WHERE owner_user_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $boards[] = $row;
}
$stmt->close();

echo json_encode(['status' => 'success', 'boards' => $boards]);
?>