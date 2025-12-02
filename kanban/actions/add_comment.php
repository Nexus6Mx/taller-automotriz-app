<?php
// actions/add_comment.php

$stmt = $conn->prepare("INSERT INTO comments (task_id, user_id, comment) VALUES (?, ?, ?)");
$user_id = empty($data['user_id']) ? null : $data['user_id'];
$stmt->bind_param("iis", $data['task_id'], $user_id, $data['comment']);
if ($stmt->execute()) { 
    echo json_encode(['status' => 'success', 'id' => $stmt->insert_id]); 
} else { 
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'message' => $stmt->error]); 
}
?>