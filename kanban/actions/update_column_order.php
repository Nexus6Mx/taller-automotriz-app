<?php
// actions/update_column_order.php

$order = $data['order'] ?? [];

if (empty($order) || !is_array($order)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se proporcionó un orden válido.']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE `columns` SET `position` = ? WHERE `id` = ?");
    
    foreach ($order as $position => $column_id) {
        // Sanitizar para asegurarse de que son enteros
        $pos = intval($position);
        $id = intval($column_id);
        
        $stmt->bind_param("ii", $pos, $id);
        $stmt->execute();
    }
    
    $stmt->close();
    $conn->commit();
    
    echo json_encode(['status' => 'success', 'message' => 'Orden de las columnas actualizado.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el orden de las columnas: ' . $e->getMessage()]);
}
?>