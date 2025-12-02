<?php
/**
 * API Endpoint: Reporteador de Órdenes
 *
 * Permite consultar órdenes filtrando por rango de fechas y múltiples estados.
 */

date_default_timezone_set('America/Mexico_City');

header('Content-Type: application/json; charset=utf-8');

include_once '../utils/cors.php';
include_once '../config/database.php';
include_once '../auth/verify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (empty($headers)) {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
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
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit();
    }

    $userId = $user['id'];
    $userRole = $user['role'] ?? 'Operador';

    if ($userRole !== 'Administrador') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo administradores.']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = [];
    }

    $fechaInicio = $input['fecha_inicio'] ?? null;
    $fechaFin = $input['fecha_fin'] ?? null;
    $statuses = $input['statuses'] ?? [];

    $fechaInicio = $fechaInicio ? trim($fechaInicio) : null;
    $fechaFin = $fechaFin ? trim($fechaFin) : null;
    $statuses = is_array($statuses) ? array_values(array_filter($statuses, function ($status) {
        return is_string($status) && $status !== '';
    })) : [];

    $conditions = ['o.user_id = :user_id'];
    $params = ['user_id' => $userId];

    if ($fechaInicio) {
        $dtInicio = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        if (!$dtInicio) {
            throw new InvalidArgumentException('Fecha inicio inválida.');
        }
        $conditions[] = 'DATE(o.created_at) >= :fecha_inicio';
        $params['fecha_inicio'] = $dtInicio->format('Y-m-d');
    }

    if ($fechaFin) {
        $dtFin = DateTime::createFromFormat('Y-m-d', $fechaFin);
        if (!$dtFin) {
            throw new InvalidArgumentException('Fecha fin inválida.');
        }
        $conditions[] = 'DATE(o.created_at) <= :fecha_fin';
        $params['fecha_fin'] = $dtFin->format('Y-m-d');
    }

    if (!empty($statuses)) {
        $placeholders = [];
        foreach ($statuses as $index => $status) {
            $key = "status_{$index}";
            $placeholders[] = ":{$key}";
            $params[$key] = $status;
        }
        $conditions[] = 'o.status IN (' . implode(', ', $placeholders) . ')';
    }

    $query = "
        SELECT 
            o.id AS orden_id,
            o.numeric_id,
            o.status,
            o.total,
            o.created_at,
            o.vehicle_brand,
            o.vehicle_plates,
            COALESCE(NULLIF(o.client_name, ''), c.name, 'Cliente no identificado') AS cliente
        FROM orders o
        LEFT JOIN clients c ON c.user_id = o.user_id AND c.name = o.client_name
        WHERE " . implode(' AND ', $conditions) . "
        ORDER BY o.created_at DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = array_map(function ($order) {
        return [
            'orden_id' => intval($order['orden_id']),
            'numeric_id' => isset($order['numeric_id']) ? intval($order['numeric_id']) : null,
            'status' => $order['status'],
            'total' => isset($order['total']) ? floatval($order['total']) : 0,
            'fecha' => $order['created_at'],
            'cliente' => $order['cliente'],
            'vehiculo' => $order['vehicle_brand'],
            'placas' => $order['vehicle_plates']
        ];
    }, $orders);

    $totalOrdenes = count($results);
    $montoTotal = array_reduce($results, function ($carry, $order) {
        return $carry + ($order['total'] ?? 0);
    }, 0);

    echo json_encode([
        'success' => true,
        'results' => $results,
        'summary' => [
            'total_ordenes' => $totalOrdenes,
            'monto_total' => $montoTotal
        ]
    ], JSON_UNESCAPED_UNICODE);

    $db = null;
} catch (InvalidArgumentException $ex) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $ex->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar el reporte',
        'error' => $e->getMessage()
    ]);
}
