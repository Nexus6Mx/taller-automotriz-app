<?php
// actions/update_task_tags.php

$taskId = $data['task_id'];
$tagIds = $data['tag_ids'];
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("DELETE FROM task_tags WHERE task_id = ?");
    $stmt->bind_param("i", $taskId); $stmt->execute(); $stmt->close();
    if (!empty($tagIds)) {
        $stmt = $conn->prepare("INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)");
        foreach ($tagIds as $tagId) {
            $stmt->bind_param("ii", $taskId, $tagId);
            $stmt->execute();
        }
        $stmt->close();
    }
    $conn->commit();
    log_activity($conn, $taskId, $data['current_user_id'] ?? null, "actualizó las etiquetas.");
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500); echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>