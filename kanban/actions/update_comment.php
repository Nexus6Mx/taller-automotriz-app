<?php
// actions/update_comment.php

if (!isset($data['id']) || !isset($data['comment'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Comment ID or text not provided.']);
    exit;
}

$comment_id = $data['id'];
$comment_text = $data['comment'];

// Optional: Add authorization

$stmt = $conn->prepare("UPDATE comments SET comment = ? WHERE id = ?");
$stmt->bind_param("si", $comment_text, $comment_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
$stmt->close();
?>