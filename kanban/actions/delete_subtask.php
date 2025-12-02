<?php
// actions/delete_subtask.php

$stmt = $conn->prepare("DELETE FROM subtasks WHERE id = ?");
$stmt->bind_param("i", $data['id']);
if ($stmt->execute()) { 
    echo json_encode(['status' => 'success']); 
} else { 
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'message' => $stmt->error]); 
}
?>