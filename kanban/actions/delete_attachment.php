<?php
// actions/delete_attachment.php

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Falta el ID del adjunto.']);
    exit;
}

$attachmentId = $data['id'];
$userId = $_SESSION['user_id'];

// Primero, obtener la ruta del archivo para poder eliminarlo del servidor
$stmt = $conn->prepare("SELECT file_path, task_id FROM attachments WHERE id = ?");
$stmt->bind_param("i", $attachmentId);
$stmt->execute();
$result = $stmt->get_result();
$attachment = $result->fetch_assoc();

if ($attachment) {
    // Eliminar el archivo físico del servidor
    if (file_exists($attachment['file_path'])) {
        unlink($attachment['file_path']);
    }

    // Eliminar el registro de la base de datos
    $deleteStmt = $conn->prepare("DELETE FROM attachments WHERE id = ?");
    $deleteStmt->bind_param("i", $attachmentId);
    
    if ($deleteStmt->execute()) {
        log_activity($conn, $attachment['task_id'], $userId, "eliminó un archivo adjunto");
        echo json_encode(['status' => 'success', 'message' => 'Adjunto eliminado correctamente.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el adjunto de la base de datos.']);
    }
    $deleteStmt->close();
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Adjunto no encontrado.']);
}

$stmt->close();
?>