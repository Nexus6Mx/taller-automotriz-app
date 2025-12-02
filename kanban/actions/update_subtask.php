<?php
// actions/update_subtask.php

$subtask_id = $data['id'] ?? null;
if (!$subtask_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de subtarea no proporcionado.']);
    exit;
}

$fields_to_update = [];
$bind_params_types = '';
$bind_params_values = [];

if (isset($data['title'])) {
    $fields_to_update[] = 'title = ?';
    $bind_params_types .= 's';
    $bind_params_values[] = &$data['title'];
}

if (isset($data['is_completed'])) {
    $fields_to_update[] = 'is_completed = ?';
    $bind_params_types .= 'i';
    $bind_params_values[] = &$data['is_completed'];
}

if (empty($fields_to_update)) {
    echo json_encode(['status' => 'success', 'message' => 'No fields to update.']);
    exit;
}

$sql = "UPDATE subtasks SET " . implode(', ', $fields_to_update) . " WHERE id = ?";
$bind_params_types .= 'i';
$bind_params_values[] = &$subtask_id;

$stmt = $conn->prepare($sql);
// Use call_user_func_array as a workaround for bind_param with dynamic params
call_user_func_array([$stmt, 'bind_param'], array_merge([$bind_params_types], $bind_params_values));

if ($stmt->execute()) { 
    echo json_encode(['status' => 'success']); 
} else { 
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'message' => $stmt->error]); 
}
?>