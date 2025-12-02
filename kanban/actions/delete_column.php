<?php
// actions/delete_column.php

$stmt = $conn->prepare("DELETE FROM `columns` WHERE id = ?");
$stmt->bind_param("i", $data['id']);
if ($stmt->execute()) { 
    echo json_encode(['status' => 'success']); 
} else { 
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'message' => $stmt->error]); 
}
?>