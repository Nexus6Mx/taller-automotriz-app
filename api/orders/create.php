<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';
require_once '../users/log_audit.php';

$database = new Database();
$db = $database->getConnection();

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
    echo json_encode(["message" => "No autorizado"]);
    exit();
}
$user_id = $user['id'];
$user_role = isset($user['role']) ? $user['role'] : 'Operador';
$user_active = isset($user['active']) ? $user['active'] : true;
if (!$user_active) {
    http_response_code(403);
    echo json_encode(["message"=>"Usuario desactivado."]);
    exit();
}

// Permisos: solo Administrador y Operador pueden crear órdenes
if (!in_array($user_role, ['Administrador', 'Operador'])) {
    http_response_code(403);
    echo json_encode(["message" => "No tiene permisos para crear órdenes."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data)) {
    http_response_code(400);
    echo json_encode(["message" => "Datos incompletos."]);
    exit();
}

try {
    // Validar que el usuario exista realmente (protege de tokens huérfanos y evita errores FK)
    $uStmt = $db->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
    $uStmt->execute(['id' => $user_id]);
    if ($uStmt->fetchColumn() === false) {
        http_response_code(401);
        echo json_encode(["message" => "Sesión inválida. Vuelve a iniciar sesión."]);
        exit();
    }

    $db->beginTransaction();

    // --- 1. GESTIONAR CLIENTE ---
    if (!isset($data->client) || !isset($data->client->name)) {
        throw new Exception('Información de cliente faltante.');
    }
    $client_name = trim($data->client->name);
    $stmt = $db->prepare("SELECT id FROM clients WHERE user_id = :user_id AND name = :name");
    $stmt->execute(['user_id' => $user_id, 'name' => $client_name]);
    $client_id = $stmt->fetchColumn();

    if (!$client_id) {
        // El cliente no existe, lo creamos
        $stmt = $db->prepare("SELECT IFNULL(MAX(numeric_id), 0) + 1 FROM clients WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $new_client_numeric_id = $stmt->fetchColumn();

        $stmt = $db->prepare(
            "INSERT INTO clients (user_id, numeric_id, name, cel, address, rfc, email, created_at) 
             VALUES (:user_id, :numeric_id, :name, :cel, :address, :rfc, :email, NOW())"
        );
        $stmt->execute([
            'user_id' => $user_id,
            'numeric_id' => $new_client_numeric_id,
            'name' => $client_name,
            'cel' => $data->client->cel,
            'address' => $data->client->address,
            'rfc' => $data->client->rfc,
            'email' => $data->client->email
        ]);
        $client_id = $db->lastInsertId();
    }

    // --- 2. GESTIONAR VEHÍCULO ---
    $vehicle = isset($data->vehicle) ? $data->vehicle : (object)[];
    $vehicle_plates = isset($vehicle->plates) ? trim($vehicle->plates) : '';
    if (!empty($vehicle_plates)) {
        $stmt = $db->prepare("SELECT id FROM vehicles WHERE user_id = :user_id AND plates = :plates");
        $stmt->execute(['user_id' => $user_id, 'plates' => $vehicle_plates]);
        $vehicle_id = $stmt->fetchColumn();

        if (!$vehicle_id) {
            // El vehículo no existe, lo creamos
            $stmt = $db->prepare("SELECT IFNULL(MAX(numeric_id), 0) + 1 FROM vehicles WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $new_vehicle_numeric_id = $stmt->fetchColumn();
            
            $stmt = $db->prepare(
                "INSERT INTO vehicles (user_id, numeric_id, client_id, client_name, brand, plates, year, km, gas_level, created_at)
                 VALUES (:user_id, :numeric_id, :client_id, :client_name, :brand, :plates, :year, :km, :gas_level, NOW())"
            );
            $stmt->execute([
                'user_id' => $user_id,
                'numeric_id' => $new_vehicle_numeric_id,
                'client_id' => $client_id,
                'client_name' => $client_name,
                'brand' => $vehicle->brand ?? null,
                'plates' => $vehicle_plates,
                'year' => $vehicle->year ?? null,
                'km' => $vehicle->km ?? null,
                'gas_level' => $vehicle->gasLevel ?? null
            ]);
        }
    }

    // --- 3. GESTIONAR INSUMOS ---
    if (!empty($data->items) && (is_array($data->items) || $data->items instanceof Traversable)) {
        foreach ($data->items as $item) {
        $item_description = trim($item->description);
        $stmt = $db->prepare("SELECT id FROM supplies WHERE user_id = :user_id AND description = :description");
        $stmt->execute(['user_id' => $user_id, 'description' => $item_description]);
        
        if ($stmt->fetchColumn() === false) {
            // El insumo no existe, lo creamos
            $stmt = $db->prepare("SELECT IFNULL(MAX(numeric_id), 0) + 1 FROM supplies WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $new_supply_numeric_id = $stmt->fetchColumn();

            $qty = isset($item->qty) ? (float)$item->qty : 0;
            $price = isset($item->price) ? (float)$item->price : 0;
            $unit_price = ($qty > 0) ? ($price / $qty) : 0;

            $stmt = $db->prepare(
                "INSERT INTO supplies (user_id, numeric_id, description, price, created_at)
                 VALUES (:user_id, :numeric_id, :description, :price, NOW())"
            );
            $stmt->execute([
                'user_id' => $user_id,
                'numeric_id' => $new_supply_numeric_id,
                'description' => $item_description,
                'price' => $unit_price
            ]);
        }
        }
    }

    // --- 4. GENERAR NUEVO NUMERIC_ID PARA LA ORDEN ---
    $stmt_id = $db->prepare("SELECT IFNULL(MAX(numeric_id), 9999) + 1 FROM orders WHERE user_id = :user_id");
    $stmt_id->execute(['user_id' => $user_id]);
    $new_order_numeric_id = $stmt_id->fetchColumn();

    // --- 5. INSERTAR ORDEN ---
    $query = "INSERT INTO orders (user_id, numeric_id, client_name, client_cel, client_address, client_rfc, client_email, vehicle_brand, vehicle_plates, vehicle_year, vehicle_km, vehicle_gas_level, observations, status, subtotal, iva, total, iva_applied, advance_amount, advance_date, created_at, updated_at) 
              VALUES (:user_id, :numeric_id, :client_name, :client_cel, :client_address, :client_rfc, :client_email, :vehicle_brand, :vehicle_plates, :vehicle_year, :vehicle_km, :vehicle_gas_level, :observations, :status, :subtotal, :iva, :total, :iva_applied, :advance_amount, :advance_date, NOW(), NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'user_id' => $user_id,
        'numeric_id' => $new_order_numeric_id,
        'client_name' => $client_name,
        'client_cel' => $data->client->cel ?? null,
        'client_address' => $data->client->address ?? null,
        'client_rfc' => $data->client->rfc ?? null,
        'client_email' => $data->client->email ?? null,
        'vehicle_brand' => $vehicle->brand ?? null,
        'vehicle_plates' => $vehicle_plates,
        'vehicle_year' => $vehicle->year ?? null,
        'vehicle_km' => $vehicle->km ?? null,
        'vehicle_gas_level' => $vehicle->gasLevel ?? null,
        'observations' => $data->observations ?? null,
        'status' => $data->status ?? 'Recibido',
        'subtotal' => isset($data->subtotal) ? (float)$data->subtotal : 0,
        'iva' => isset($data->iva) ? (float)$data->iva : 0,
        'total' => isset($data->total) ? (float)$data->total : 0,
        'iva_applied' => isset($data->ivaApplied) ? 
            (is_bool($data->ivaApplied) ? ($data->ivaApplied ? 1 : 0) : 
            (is_numeric($data->ivaApplied) ? ($data->ivaApplied ? 1 : 0) : 0)) : 0,
        'advance_amount' => !empty($data->advance_amount) ? floatval($data->advance_amount) : null,
        'advance_date' => !empty($data->advance_date) ? $data->advance_date : null
    ]);
    $order_id = $db->lastInsertId();

    // --- 5. INSERTAR ITEMS DE LA ORDEN ---
    if (!empty($data->items) && (is_array($data->items) || $data->items instanceof Traversable)) {
        foreach ($data->items as $item) {
            $item_query = "INSERT INTO order_items (order_id, qty, description, price) VALUES (:order_id, :qty, :description, :price)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->execute([
                'order_id' => $order_id,
                'qty' => isset($item->qty) ? $item->qty : 0,
                'description' => isset($item->description) ? $item->description : '',
                'price' => isset($item->price) ? $item->price : 0
            ]);
        }
    }

    $db->commit();
    echo json_encode([
        "success" => true,
        "message" => "Orden creada exitosamente.",
        "order_id" => $order_id,
        "numeric_id" => (int)$new_order_numeric_id,
        "status" => $data->status
    ]);

    // Audit
    log_audit($db, $user_id, 'order_created', 'order', $order_id, json_encode([
        'numeric_id' => (int)$new_order_numeric_id,
        'status' => $data->status
    ]));

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    // Log técnico para diagnóstico
    try {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/create_order_error.log';
        $context = [
            'time' => date('c'),
            'user_id' => isset($user_id) ? $user_id : null,
            'error' => $e->getMessage(),
        ];
        @file_put_contents($logFile, json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    } catch (\Throwable $t) { /* ignore logging errors */ }

    // No exponer detalles SQL al usuario final
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "No se pudo crear la orden. Inténtalo de nuevo o contacta a soporte."]);
}
?>

