<?php
// actions/delete_comment.php

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Comment ID not provided.']);
    exit;
}

$comment_id = $data['id'];

// Optional: Add authorization to ensure only the comment owner or board owner can delete
// For now, we'll allow any authenticated user to delete.

$stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
$stmt->close();
?>