<?php
include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';

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
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$token = $authHeader ? str_replace('Bearer ', '', $authHeader) : '';
$user = verifyToken($db, $token);

if (!$user) {
    http_response_code(401);
    echo json_encode(["message" => "Acceso no autorizado."]);
    exit();
}

$user_id = $user['id'];
$user_role = isset($user['role']) ? $user['role'] : 'Operador';
$user_active = isset($user['active']) ? $user['active'] : true;

if (!$user_active) {
    http_response_code(403);
    echo json_encode(["message" => "Usuario desactivado."]);
    exit();
}

try {
    $order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    // Si se proporciona un ID, obtener los detalles de una sola orden.
    if ($order_id) {
        // Admin y Operador pueden ver cualquier orden; Consulta sólo las propias
        if (in_array($user_role, ['Administrador', 'Operador'])) {
            $query_order = "SELECT * FROM orders WHERE id = :order_id";
            $stmt_order = $db->prepare($query_order);
            $stmt_order->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        } else {
            $query_order = "SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id";
            $stmt_order = $db->prepare($query_order);
            $stmt_order->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt_order->bindParam(':user_id', $user_id);
        }
        $stmt_order->execute();
        $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $query_items = "SELECT qty, description, price FROM order_items WHERE order_id = :order_id";
            $stmt_items = $db->prepare($query_items);
            $stmt_items->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt_items->execute();
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            // Mapear los datos a la estructura JSON esperada por el frontend.
            // Normalizar estatus: renombrar 'En reparación' -> 'Autorizado en reparación'
            $normalizedStatus = $order['status'] === 'En reparación' ? 'Autorizado en reparación' : $order['status'];

            $output = [
                'id' => $order['id'],
                'numericId' => (int)$order['numeric_id'],
                'client' => [
                    'name' => $order['client_name'],
                    'cel' => $order['client_cel'],
                    'address' => $order['client_address'],
                    'rfc' => $order['client_rfc'],
                    'email' => $order['client_email']
                ],
                'vehicle' => [
                    'brand' => $order['vehicle_brand'],
                    'plates' => $order['vehicle_plates'],
                    'year' => $order['vehicle_year'],
                    'km' => $order['vehicle_km'],
                    'gasLevel' => $order['vehicle_gas_level']
                ],
                'items' => array_map(function($item) {
                    return [
                        'qty' => $item['qty'],
                        'description' => $item['description'],
                        'price' => floatval($item['price'])
                    ];
                }, $items),
                'observations' => $order['observations'],
                'status' => $normalizedStatus,
                'subtotal' => floatval($order['subtotal']),
                'iva' => floatval($order['iva']),
                'total' => floatval($order['total']),
                'ivaApplied' => (bool)$order['iva_applied'],
                // mantener snake_case por compatibilidad y añadir camelCase para el frontend
                'advance_amount' => $order['advance_amount'] !== null ? floatval($order['advance_amount']) : null,
                'advance_date' => $order['advance_date'],
                'advanceAmount' => $order['advance_amount'] !== null ? floatval($order['advance_amount']) : null,
                'advanceDate' => $order['advance_date'],
                'createdAt' => $order['created_at']
            ];
            
            echo json_encode(['data' => $output], JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Orden no encontrada."]);
        }
        // Detener la ejecución para no correr el código de abajo.
        exit();
    }

    // Si no se proporciona ID, se ejecuta la lógica original para obtener todas las órdenes.
    // Permisos: Administrador y Operador ven todas las órdenes; Consulta sólo las propias.
    if (in_array($user_role, ['Administrador', 'Operador'])) {
        $query = "SELECT o.*, GROUP_CONCAT(JSON_OBJECT('qty', oi.qty, 'description', oi.description, 'price', oi.price)) as items_json FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id GROUP BY o.id ORDER BY o.created_at DESC";
        $stmt = $db->prepare($query);
    } else {
        // role Consulta u otros: sólo ver órdenes creadas por el usuario
        $query = "SELECT o.*, GROUP_CONCAT(JSON_OBJECT('qty', oi.qty, 'description', oi.description, 'price', oi.price)) as items_json FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = :user_id GROUP BY o.id ORDER BY o.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
    }

    
    $stmt->execute();
    
    $orders = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items = [];
        if ($row['items_json']) {
            // Fix para decodificar el string que devuelve GROUP_CONCAT
            $items = json_decode('[' . $row['items_json'] . ']');
        }
        
        // Normalizar estatus: renombrar 'En reparación' -> 'Autorizado en reparación'
        $normalizedStatus = $row['status'] === 'En reparación' ? 'Autorizado en reparación' : $row['status'];

        $order = [
            'id' => $row['id'],
            'numericId' => (int)$row['numeric_id'],
            'client' => [
                'name' => $row['client_name'],
                'cel' => $row['client_cel'],
                'address' => $row['client_address'],
                'rfc' => $row['client_rfc'],
                'email' => $row['client_email']
            ],
            'vehicle' => [
                'brand' => $row['vehicle_brand'],
                'plates' => $row['vehicle_plates'],
                'year' => $row['vehicle_year'],
                'km' => $row['vehicle_km'],
                'gasLevel' => $row['vehicle_gas_level']
            ],
            'items' => $items,
            'observations' => $row['observations'],
            'status' => $normalizedStatus,
            'subtotal' => floatval($row['subtotal']),
            'iva' => floatval($row['iva']),
            'total' => floatval($row['total']),
            'ivaApplied' => (bool)$row['iva_applied'],
            // mantener snake_case por compatibilidad y añadir camelCase para el frontend
            'advance_amount' => isset($row['advance_amount']) ? floatval($row['advance_amount']) : null,
            'advance_date' => $row['advance_date'],
            'advanceAmount' => isset($row['advance_amount']) ? floatval($row['advance_amount']) : null,
            'advanceDate' => $row['advance_date'],
            'createdAt' => $row['created_at']
        ];
        
        $orders[] = $order;
    }
    
    echo json_encode(['data' => $orders]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error al obtener las órdenes: " . $e->getMessage()]);
}
?>
