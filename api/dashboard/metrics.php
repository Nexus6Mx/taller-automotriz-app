<?php
/**
 * API Endpoint: Dashboard Ejecutivo - Métricas en Tiempo Real
 * 
 * Proporciona datos agregados para el dashboard ejecutivo independiente
 * Protegido por autenticación JWT
 */

// Configurar zona horaria de México
date_default_timezone_set('America/Mexico_City');

header('Content-Type: application/json; charset=utf-8');

include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';

$database = new Database();
$db = $database->getConnection();

// Verificar autenticación
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (empty($headers)) {
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
}

$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = $authHeader ? str_replace('Bearer ', '', $authHeader) : ($_COOKIE['authToken'] ?? '');

$user = verifyToken($db, $token);
if (!$user) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autorizado"]);
    exit();
}

$user_id = $user['id'];
$user_role = $user['role'] ?? 'Operador';

// Solo administradores pueden ver el dashboard ejecutivo
if ($user_role !== 'Administrador') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Acceso denegado. Solo administradores."]);
    exit();
}

try {
    $metrics = [];
    
    // ============================================
    // 1. PROYECCIÓN FINANCIERA (Mes Actual)
    // ============================================
    $currentMonth = date('Y-m');
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_ordenes,
            COALESCE(SUM(total), 0) as total_facturado,
            COALESCE(AVG(total), 0) as ticket_promedio
        FROM orders 
        WHERE user_id = :user_id 
        AND DATE_FORMAT(created_at, '%Y-%m') = :current_month
    ");
    $stmt->execute(['user_id' => $user_id, 'current_month' => $currentMonth]);
    $financial = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Meta mensual (puedes ajustar o hacer configurable)
    $meta_mensual = 150000; // $150,000 MXN
    $porcentaje_meta = $financial['total_facturado'] > 0 ? 
        round(($financial['total_facturado'] / $meta_mensual) * 100, 1) : 0;
    
    $metrics['financial_projection'] = [
        'mes_actual' => date('F Y'),
        'total_facturado' => floatval($financial['total_facturado']),
        'meta_mensual' => $meta_mensual,
        'porcentaje_meta' => $porcentaje_meta,
        'total_ordenes' => intval($financial['total_ordenes']),
        'ticket_promedio' => floatval($financial['ticket_promedio'])
    ];
    
    // ============================================
    // 2. FACTURACIÓN POR DÍA (Últimos 30 días)
    // ============================================
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as fecha,
            COALESCE(SUM(total), 0) as total_dia,
            COUNT(*) as ordenes_dia
        FROM orders 
        WHERE user_id = :user_id 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY fecha ASC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $facturacion_diaria = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $metrics['daily_sales'] = array_map(function($row) {
        return [
            'fecha' => $row['fecha'],
            'total' => floatval($row['total_dia']),
            'ordenes' => intval($row['ordenes_dia'])
        ];
    }, $facturacion_diaria);
    
    // ============================================
    // 3. TOP 5 MAYORES VENTAS (Órdenes individuales)
    // ============================================
    $stmt = $db->prepare("
        SELECT 
            numeric_id,
            client_name,
            vehicle_brand,
            vehicle_plates,
            total,
            created_at,
            status
        FROM orders 
        WHERE user_id = :user_id 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ORDER BY total DESC 
        LIMIT 5
    ");
    $stmt->execute(['user_id' => $user_id]);
    $highest_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $metrics['highest_sales'] = array_map(function($row) {
        return [
            'orden_id' => intval($row['numeric_id']),
            'cliente' => $row['client_name'],
            'vehiculo' => $row['vehicle_brand'] ?? 'N/A',
            'placas' => $row['vehicle_plates'] ?? 'N/A',
            'total' => floatval($row['total']),
            'fecha' => $row['created_at'],
            'status' => $row['status']
        ];
    }, $highest_sales);
    
    // ============================================
    // 4. ÓRDENES ANTIGUAS PENDIENTES (No entregadas)
    // ============================================
    $stmt = $db->prepare("
        SELECT 
            numeric_id,
            client_name,
            vehicle_brand,
            vehicle_plates,
            total,
            created_at,
            status,
            DATEDIFF(CURDATE(), DATE(created_at)) as dias_antiguedad
        FROM orders 
        WHERE user_id = :user_id 
        AND status NOT IN ('Entregado Pagado', 'Entregado pendiente de pago', 'Cancelado')
        ORDER BY created_at ASC 
        LIMIT 20
    ");
    $stmt->execute(['user_id' => $user_id]);
    $pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $metrics['pending_orders'] = array_map(function($row) {
        return [
            'orden_id' => intval($row['numeric_id']),
            'cliente' => $row['client_name'],
            'vehiculo' => $row['vehicle_brand'] ?? 'N/A',
            'placas' => $row['vehicle_plates'] ?? 'N/A',
            'total' => floatval($row['total']),
            'fecha' => $row['created_at'],
            'status' => $row['status'],
            'dias_antiguedad' => intval($row['dias_antiguedad'])
        ];
    }, $pending_orders);
    
    // ============================================
    // 5. DISTRIBUCIÓN POR ESTADO
    // ============================================
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as cantidad,
            COALESCE(SUM(total), 0) as total_estado
        FROM orders 
        WHERE user_id = :user_id 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY status
        ORDER BY cantidad DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $metrics['status_distribution'] = array_map(function($row) {
        return [
            'status' => $row['status'],
            'cantidad' => intval($row['cantidad']),
            'total' => floatval($row['total_estado'])
        ];
    }, $status_distribution);
    
    // ============================================
    // 6. FACTURACIÓN POR MES (Últimos 12 meses)
    // ============================================
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as mes,
            COALESCE(SUM(total), 0) as total_mes,
            COUNT(*) as ordenes_mes
        FROM orders 
        WHERE user_id = :user_id 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY mes ASC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $monthly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $metrics['monthly_sales'] = array_map(function($row) {
        return [
            'mes' => $row['mes'],
            'total' => floatval($row['total_mes']),
            'ordenes' => intval($row['ordenes_mes'])
        ];
    }, $monthly_sales);
    
    // ============================================
    // 7. TOP 10 CLIENTES (Por volumen)
    // ============================================
    $stmt = $db->prepare("
        SELECT 
            client_name,
            COUNT(*) as total_ordenes,
            COALESCE(SUM(total), 0) as total_facturado
        FROM orders 
        WHERE user_id = :user_id 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY client_name
        ORDER BY total_facturado DESC
        LIMIT 10
    ");
    $stmt->execute(['user_id' => $user_id]);
    $top_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $metrics['top_clients'] = array_map(function($row) {
        return [
            'cliente' => $row['client_name'],
            'ordenes' => intval($row['total_ordenes']),
            'total' => floatval($row['total_facturado'])
        ];
    }, $top_clients);
    
    // ============================================
    // 8. ESTADÍSTICAS GENERALES
    // ============================================
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_ordenes_historico,
            COALESCE(SUM(total), 0) as total_historico
        FROM orders 
        WHERE user_id = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
    $general = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Desglose de órdenes por estatus (histórico)
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as cantidad
        FROM orders 
        WHERE user_id = :user_id
        GROUP BY status
        ORDER BY cantidad DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $status_counts_historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $metrics['general_stats'] = [
        'total_ordenes_historico' => intval($general['total_ordenes_historico']),
        'total_historico' => floatval($general['total_historico']),
        'status_counts_historico' => array_map(function($row) {
            return [
                'status' => $row['status'],
                'cantidad' => intval($row['cantidad'])
            ];
        }, $status_counts_historico)
    ];
    
    // ============================================
    // RESPUESTA FINAL
    // ============================================
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'metrics' => $metrics
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener métricas',
        'error' => $e->getMessage()
    ]);
}

$db = null;
?>
