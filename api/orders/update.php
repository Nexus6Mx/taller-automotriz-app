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
    echo json_encode(["success" => false, "message" => "Acceso no autorizado."]);
    exit();
}

// --- Lectura de Datos ---
$input = file_get_contents("php://input");
$data = json_decode($input);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Error al decodificar JSON"
    ]);
    exit();
}

if (!isset($data->id) || !is_numeric($data->id)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Falta el ID de la orden o no es válido."
    ]);
    exit();
}

// Solo validar numeric_id si se está actualizando más que solo el estado
if (count((array)$data) > 2 && !isset($data->numeric_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta el Numeric ID para la actualización completa."]);
    exit();
}

// --- Inicio de la Transacción (para asegurar que todo se guarde o nada se guarde) ---
$db->beginTransaction();

try {
    // Verificar que la orden exista antes de intentar actualizarla
    $check_query = "SELECT id FROM orders WHERE id = :id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindValue(':id', $data->id, PDO::PARAM_INT);
    $check_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        throw new Exception("No se encontró la orden con ID: " . $data->id);
    }

    // --- 1. Actualizar la tabla principal 'orders' ---
    // Si solo se está actualizando el estado (solo se envía id y status)
    if (count((array)$data) <= 2 && isset($data->status)) {
        $query = "UPDATE orders SET status = :status WHERE id = :id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $data->status);
        $stmt->bindParam(':id', $data->id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    $db->commit();
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Estado de la orden actualizado con éxito.", "status" => $data->status]);
        exit();
    }

    // Si se está actualizando toda la orden
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
                                iva_applied = :iva_applied,
                                advance_amount = :advance_amount,
                                advance_date = :advance_date
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
    $stmt->bindParam(':observations', $data->observations);
    $stmt->bindParam(':status', $data->status);
    $stmt->bindParam(':subtotal', $data->subtotal);
    $stmt->bindParam(':iva', $data->iva);
    $stmt->bindParam(':total', $data->total);
    
    // Asegurar que iva_applied sea siempre 0 o 1
    $iva_applied = 0;
    if (isset($data->ivaApplied)) {
        $iva_applied = is_bool($data->ivaApplied) ? ($data->ivaApplied ? 1 : 0) : 
                     (is_numeric($data->ivaApplied) ? ($data->ivaApplied ? 1 : 0) : 0);
    }
    $stmt->bindValue(':iva_applied', $iva_applied, PDO::PARAM_INT);
    
    // Manejar advance_amount como decimal(10,2) nullable
    $advance_amount = !empty($data->advance_amount) ? floatval($data->advance_amount) : null;
    $stmt->bindValue(':advance_amount', $advance_amount, $advance_amount === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    
    // Manejar advance_date como date nullable
    $advance_date = !empty($data->advance_date) ? $data->advance_date : null;
    $stmt->bindValue(':advance_date', $advance_date, $advance_date === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    
    $stmt->bindValue(':id', $data->id);
    $stmt->bindValue(':user_id', $user_id);

    if (!$stmt->execute()) {
        $err = $stmt->errorInfo();
        throw new Exception("Error al actualizar la orden: " . implode(" ", $err));
    }
    
    // Nota: rowCount() puede ser 0 si los valores enviados son idénticos a los existentes.
    // No tratamos esto como error si la orden existe.

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
    echo json_encode(["success" => true, "message" => "Orden actualizada con éxito."]);

} catch (Exception $e) {
    // --- Si algo falló, revertir todos los cambios ---
    $db->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al actualizar la orden: " . $e->getMessage()]);
}
?>