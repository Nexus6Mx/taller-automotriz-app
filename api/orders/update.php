<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';
require_once '../users/log_audit.php';

$database = new Database();
$db = $database->getConnection();

// --- Verificación de Token (Seguridad) ---
$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
}
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
$token = $authHeader ? str_replace('Bearer ', '', $authHeader) : '';
if (!$token && !empty($_COOKIE['authToken'])) {
    $token = $_COOKIE['authToken'];
}
$user = verifyToken($db, $token);
if (!$user) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Acceso no autorizado."]);
    exit();
}
$user_id = $user['id'];
$user_role = isset($user['role']) ? $user['role'] : 'Operador';
$user_active = isset($user['active']) ? $user['active'] : true;
if (!$user_active) {
    http_response_code(403);
    echo json_encode(["success"=>false, "message"=>"Usuario desactivado."]);
    exit();
}

// Permisos: solo Administrador y Operador pueden actualizar órdenes
if (!in_array($user_role, ['Administrador', 'Operador'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tiene permisos para actualizar órdenes."]);
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
    // Validar que el usuario exista realmente (protege de tokens huérfanos)
    $uStmt = $db->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
    $uStmt->execute(['id' => $user_id]);
    if ($uStmt->fetchColumn() === false) {
        throw new Exception('Sesión inválida. Vuelve a iniciar sesión.');
    }
    // Verificar que la orden exista antes de intentar actualizarla
    // Admin/Operador pueden actualizar cualquier orden; si se desea restringir por usuario, descomentar AND user_id = :user_id
    $check_query = "SELECT id, user_id FROM orders WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindValue(':id', $data->id, PDO::PARAM_INT);
    $check_stmt->execute();

    $order_row = $check_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order_row) {
        throw new Exception("No se encontró la orden con ID: " . $data->id);
    }

    // --- 1. Actualizar la tabla principal 'orders' ---
    // Si solo se está actualizando el estado (solo se envía id y status)
    if (count((array)$data) <= 2 && isset($data->status)) {
        $query = "UPDATE orders SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $data->status);
        $stmt->bindParam(':id', $data->id);
        $stmt->execute();
        $db->commit();
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Estado de la orden actualizado con éxito.", "status" => $data->status]);
        // Audit status change
        log_audit($db, $user_id, 'order_status_updated', 'order', $data->id, json_encode(['status' => $data->status]));
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
                            WHERE id = :id";

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
    // Audit full update
    log_audit($db, $user_id, 'order_updated', 'order', $data->id, null);

} catch (Exception $e) {
    // --- Si algo falló, revertir todos los cambios ---
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    // Log técnico para diagnóstico
    try {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
        $logFile = $logDir . '/order_update_error.log';
        $context = [
            'time' => date('c'),
            'user_id' => isset($user_id) ? $user_id : null,
            'order_id' => isset($data->id) ? $data->id : null,
            'error' => $e->getMessage(),
        ];
        @file_put_contents($logFile, json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    } catch (\Throwable $t) { /* ignore logging errors */ }

    http_response_code(500);
    echo json_encode(["success" => false, "message" => "No se pudo actualizar la orden. Inténtalo de nuevo o contacta a soporte."]);
}
?>