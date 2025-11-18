<?php
// api/orders/send_email.php

// Deshabilitar errores HTML para evitar que se muestren como HTML en lugar de JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../utils/cors.php';
require_once '../auth/verify.php';
require_once '../users/log_audit.php';
require_once __DIR__ . '/pdf_helper.php';

$database = new Database();
$db = $database->getConnection();

// Simple file logger for debugging (writes to api/logs/send_email.log)
function log_send_email($text) {
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) @mkdir($logsDir, 0755, true);
    $file = $logsDir . '/send_email.log';
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL;
    @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}

// Leer el cuerpo una vez y loguearlo
$rawInput = file_get_contents('php://input');
log_send_email('Request received: ' . json_encode(array('method'=>$_SERVER['REQUEST_METHOD'],'payload'=>$rawInput)));

// Verificar autenticación (con polyfill de getallheaders)
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
    echo json_encode(["message"=>"Usuario desactivado."]);
    exit();
}

// Permisos: solo Administrador y Operador pueden enviar correos de orden
if (!in_array($user_role, ['Administrador', 'Operador'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para enviar correos de órdenes.']);
    exit;
}

// Obtener datos de la solicitud desde la misma lectura
$data = json_decode($rawInput);

if (!$data || !isset($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de orden requerido']);
    exit;
}

$orderId = $data->id;

try {
    // Obtener la orden
    $query = "SELECT * FROM orders WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $orderId);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    log_send_email('Order fetch result: ' . json_encode($order ?: []));

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Orden no encontrada']);
        exit;
    }

    if (empty($order['client_email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El cliente no tiene correo electrónico registrado']);
        exit;
    }

    // Obtener items de la orden
    $queryItems = "SELECT * FROM order_items WHERE order_id = :order_id";
    // Usar la conexión correcta ($db)
    $stmtItems = $db->prepare($queryItems);
    $stmtItems->bindParam(':order_id', $orderId);
    $stmtItems->execute();
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    log_send_email('Order items count: ' . count($items));

    // Preparar datos para el PDF (mismo shape que generar_pdf.php)
    // Use the same public logo URL as Imprimir for consistency
    $logoPath = 'https://errautomotriz.com/assets/images/err.png';
    $orderData = [
        'numericId' => $order['numeric_id'],
        'status' => $order['status'],
        'createdAt' => $order['created_at'] ?? null,
        'client' => [
            'name' => $order['client_name'],
            'email' => $order['client_email'],
            'cel' => $order['client_cel'],
            'address' => $order['client_address'],
            'rfc' => $order['client_rfc']
        ],
        'vehicle' => [
            'brand' => $order['vehicle_brand'],
            'plates' => $order['vehicle_plates'],
            'year' => $order['vehicle_year'],
            'km' => $order['vehicle_km'],
            'gasLevel' => $order['vehicle_gas_level']
        ],
        'items' => $items,
        'subtotal' => $order['subtotal'],
        'iva' => $order['iva'],
        'total' => $order['total'],
        'ivaApplied' => isset($order['iva_applied']) ? (bool)$order['iva_applied'] : null,
        'observations' => $order['observations'],
        'logoUrl' => $logoPath // URL pública como en Imprimir
    ];

    // Generar PDF con el mismo maquetado que "Imprimir"
    $pdfContent = generateOrderPDF($orderData);
    log_send_email('PDF generated (helper), size: ' . strlen($pdfContent));

    $to = $order['client_email'];
    // Subject in raw UTF-8; we'll use it directly with PHPMailer and RFC2047-encode for mail()
    $subject_raw = 'Envío de su Orden de Servicio/Cotización – ERR Automotriz';
    $message = "Estimado/a cliente,\n\nPor este medio le enviamos adjunta su Orden de Servicio o Cotización solicitada.\nQuedamos atentos a cualquier comentario o duda que tenga sobre la misma.\n\nAgradecemos su confianza.\nAtentamente,\nÁrea de Servicio\nERR Automotriz";

    // Usar PHPMailer si está disponible
    if (file_exists('../../PHPMailer/src/PHPMailer.php')) {
        log_send_email('PHPMailer detected');
        require_once '../../PHPMailer/src/PHPMailer.php';
        require_once '../../PHPMailer/src/SMTP.php';
        require_once '../../PHPMailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // Asegurar UTF-8 en PHPMailer
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            // SMTP settings from environment
            $mail->Host = getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER') ?: '';
            $mail->Password = getenv('SMTP_PASS') ?: '';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = getenv('SMTP_PORT') ? (int)getenv('SMTP_PORT') : 465;

            $fromEmail = getenv('SMTP_FROM') ?: (getenv('SMTP_USER') ?: '');
            $fromName = getenv('SMTP_FROM_NAME') ?: 'ERR Automotriz';
            if ($fromEmail) {
                $mail->setFrom($fromEmail, $fromName);
            }
            $mail->addAddress($to);
            $mail->addStringAttachment($pdfContent, 'orden_' . $order['numeric_id'] . '.pdf');

            $mail->isHTML(false);
            $mail->Subject = $subject_raw;
            $mail->Body = $message;

            $mail->send();
            log_send_email('PHPMailer send() returned success');
            echo json_encode(['success' => true, 'message' => 'Correo enviado exitosamente']);
            log_audit($db, $user_id, 'order_email_sent', 'order', $orderId, null);
        } catch (Exception $e) {
            log_send_email('PHPMailer error: ' . $e->getMessage() . ' | ErrorInfo: ' . $mail->ErrorInfo);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al enviar el correo: ' . $mail->ErrorInfo]);
        }
    } else {
        log_send_email('PHPMailer not found, using mail()');
    // Usar mail() con adjunto
    $boundary = md5(time());

    // RFC2047-encode subject for UTF-8 when using mail()
    $subject = '=?UTF-8?B?' . base64_encode($subject_raw) . '?=';

    $fromHeader = getenv('SMTP_FROM') ?: (getenv('SMTP_USER') ?: '');
    $headers = $fromHeader ? ("From: $fromHeader\r\n") : '';
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $message . "\r\n\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: application/pdf; name=\"orden_{$order['numeric_id']}.pdf\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"orden_{$order['numeric_id']}.pdf\"\r\n\r\n";
        $body .= chunk_split(base64_encode($pdfContent)) . "\r\n";
        $body .= "--$boundary--";

        $mailResult = mail($to, $subject, $body, $headers);
        log_send_email('mail() result: ' . ($mailResult ? 'true' : 'false'));
        if ($mailResult) {
            echo json_encode(['success' => true, 'message' => 'Correo enviado exitosamente']);
            log_audit($db, $user_id, 'order_email_sent', 'order', $orderId, null);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al enviar el correo']);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>