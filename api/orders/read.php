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
    echo json_encode(["message" => "Acceso no autorizado."]);
    exit();
}

try {
    $query = "SELECT o.*, GROUP_CONCAT(JSON_OBJECT('qty', oi.qty, 'description', oi.description, 'price', oi.price)) as items_json FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = :user_id GROUP BY o.id ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $orders = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items = [];
        if ($row['items_json']) {
            // Fix para decodificar el string que devuelve GROUP_CONCAT
            $items = json_decode('[' . $row['items_json'] . ']');
        }
        
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
            'status' => $row['status'],
            'subtotal' => floatval($row['subtotal']),
            'iva' => floatval($row['iva']),
            'total' => floatval($row['total']),
            'ivaApplied' => (bool)$row['iva_applied'],
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
