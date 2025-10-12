<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';

$database = new Database();
$db = $database->getConnection();

$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';
$user_id = verifyToken($db, $token);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(["message" => "No autorizado"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data)) {
    http_response_code(400);
    echo json_encode(["message" => "Datos incompletos."]);
    exit();
}

try {
    $db->beginTransaction();

    // --- 1. GESTIONAR CLIENTE ---
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
    $vehicle_plates = trim($data->vehicle->plates);
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
                'brand' => $data->vehicle->brand,
                'plates' => $vehicle_plates,
                'year' => $data->vehicle->year,
                'km' => $data->vehicle->km,
                'gas_level' => $data->vehicle->gasLevel
            ]);
        }
    }

    // --- 3. GESTIONAR INSUMOS ---
    foreach ($data->items as $item) {
        $item_description = trim($item->description);
        $stmt = $db->prepare("SELECT id FROM supplies WHERE user_id = :user_id AND description = :description");
        $stmt->execute(['user_id' => $user_id, 'description' => $item_description]);
        
        if ($stmt->fetchColumn() === false) {
            // El insumo no existe, lo creamos
            $stmt = $db->prepare("SELECT IFNULL(MAX(numeric_id), 0) + 1 FROM supplies WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $new_supply_numeric_id = $stmt->fetchColumn();

            $unit_price = ($item->qty > 0) ? $item->price / $item->qty : 0;

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
        'client_cel' => $data->client->cel,
        'client_address' => $data->client->address,
        'client_rfc' => $data->client->rfc,
        'client_email' => $data->client->email,
        'vehicle_brand' => $data->vehicle->brand,
        'vehicle_plates' => $vehicle_plates,
        'vehicle_year' => $data->vehicle->year,
        'vehicle_km' => $data->vehicle->km,
        'vehicle_gas_level' => $data->vehicle->gasLevel,
        'observations' => $data->observations,
        'status' => $data->status,
        'subtotal' => $data->subtotal,
        'iva' => $data->iva,
        'total' => $data->total,
        'iva_applied' => isset($data->ivaApplied) ? 
            (is_bool($data->ivaApplied) ? ($data->ivaApplied ? 1 : 0) : 
            (is_numeric($data->ivaApplied) ? ($data->ivaApplied ? 1 : 0) : 0)) : 0,
        'advance_amount' => !empty($data->advance_amount) ? floatval($data->advance_amount) : null,
        'advance_date' => !empty($data->advance_date) ? $data->advance_date : null
    ]);
    $order_id = $db->lastInsertId();

    // --- 5. INSERTAR ITEMS DE LA ORDEN ---
    foreach ($data->items as $item) {
        $item_query = "INSERT INTO order_items (order_id, qty, description, price) VALUES (:order_id, :qty, :description, :price)";
        $item_stmt = $db->prepare($item_query);
        $item_stmt->execute([
            'order_id' => $order_id,
            'qty' => $item->qty,
            'description' => $item->description,
            'price' => $item->price
        ]);
    }

    $db->commit();
    echo json_encode(["success" => true, "message" => "Orden creada exitosamente y catálogos actualizados.", "order_id" => $order_id]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al crear orden: " . $e->getMessage()]);
}
?>

