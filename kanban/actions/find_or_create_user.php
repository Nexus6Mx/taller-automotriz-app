<?php
// actions/find_or_create_user.php

$name = $data['name'] ?? 'Invitado';
$stmt = $conn->prepare("SELECT * FROM users WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
    echo json_encode(['status' => 'success', 'user' => $result->fetch_assoc()]);
} else {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO users (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if($stmt->execute()){
        echo json_encode(['status' => 'success', 'user' => ['id' => $stmt->insert_id, 'name' => $name]]);
    } else {
        http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Could not create user.']);
    }
}
?>