<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';

$database = new Database();
$db = $database->getConnection();

// --- Verificación de Token (Seguridad) ---
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
$user_id = verifyToken($db, $token);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(["message" => "Acceso no autorizado."]);
    exit();
}

// --- Lectura de Datos ---
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !isset($data->numeric_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Faltan datos esenciales (ID o Numeric ID)."]);
    exit();
}

// --- Inicio de la Transacción (para asegurar que todo se guarde o nada se guarde) ---
$db->beginTransaction();

try {
    // --- 1. Actualizar la tabla principal 'orders' ---
    $query = "UPDATE orders SET 
                numeric_id = :numeric_id,
                client_name = :client_name,
                client_cel = :client_cel,
                client_address = :client_address,
                client_rfc = :client_rfc,
                client_email = :client_email,
                vehicle_brand = :vehicle_brand,
                vehicle_plates = :vehicle_plates,
                vehicle_year = :vehicle_year,
                vehicle_km = :vehicle_km,
                vehicle_gas_level = :vehicle_gas_level,
                observations = :observations,
                status = :status,
                subtotal = :subtotal,
                iva = :iva,
                total = :total,
                iva_applied = :iva_applied
              WHERE id = :id AND user_id = :user_id";

    $stmt = $db->prepare($query);

    // Vincular todos los parámetros
    $stmt->bindParam(':numeric_id', $data->numeric_id);
    $stmt->bindParam(':client_name', $data->client->name);
    $stmt->bindParam(':client_cel', $data->client->cel);
    $stmt->bindParam(':client_address', $data->client->address);
    $stmt->bindParam(':client_rfc', $data->client->rfc);
    $stmt->bindParam(':client_email', $data->client->email);
    $stmt->bindParam(':vehicle_brand', $data->vehicle->brand);
    $stmt->bindParam(':vehicle_plates', $data->vehicle->plates);
    $stmt->bindParam(':vehicle_year', $data->vehicle->year);
    $stmt->bindParam(':vehicle_km', $data->vehicle->km);
    $stmt->bindParam(':vehicle_gas_level', $data->vehicle->gasLevel);
    $stmt->bindParam(':observations', $data->observations); // <-- ¡Aquí está la corrección!
    $stmt->bindParam(':status', $data->status);
    $stmt->bindParam(':subtotal', $data->subtotal);
    $stmt->bindParam(':iva', $data->iva);
    $stmt->bindParam(':total', $data->total);
    $stmt->bindParam(':iva_applied', $data->ivaApplied, PDO::PARAM_BOOL);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':user_id', $user_id);

    $stmt->execute();

    // --- 2. Actualizar la tabla 'order_items' (borrar los viejos e insertar los nuevos) ---
    
    // Primero, borrar los items existentes para esta orden
    $delete_items_query = "DELETE FROM order_items WHERE order_id = :order_id";
    $delete_stmt = $db->prepare($delete_items_query);
    $delete_stmt->bindParam(':order_id', $data->id);
    $delete_stmt->execute();

    // Segundo, insertar los nuevos items
    $insert_item_query = "INSERT INTO order_items (order_id, qty, description, price) VALUES (:order_id, :qty, :description, :price)";
    $insert_stmt = $db->prepare($insert_item_query);

    foreach ($data->items as $item) {
        $insert_stmt->bindParam(':order_id', $data->id);
        $insert_stmt->bindParam(':qty', $item->qty);
        $insert_stmt->bindParam(':description', $item->description);
        $insert_stmt->bindParam(':price', $item->price);
        $insert_stmt->execute();
    }

    // --- Si todo salió bien, confirmar los cambios ---
    $db->commit();
    
    http_response_code(200);
    echo json_encode(["message" => "Orden actualizada con éxito."]);

} catch (Exception $e) {
    // --- Si algo falló, revertir todos los cambios ---
    $db->rollBack();
    http_response_code(500);
    echo json_encode(["message" => "Error al actualizar la orden: " . $e->getMessage()]);
}
?>