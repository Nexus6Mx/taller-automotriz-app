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

$database = new Database();
$db = $database->getConnection();

function log_send_email($text) {
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) @mkdir($logsDir, 0755, true);
    $file = $logsDir . '/send_email.log';
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL;
    @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}

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

// Obtener datos de la solicitud
$data = json_decode($rawInput);
if (!$data || !isset($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de orden requerido']);
    exit;
}
$orderId = (int)$data->id;

try {
    // Obtener la orden
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
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

    // Obtener items
    $stmtItems = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmtItems->bindParam(':order_id', $orderId);
    $stmtItems->execute();
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    log_send_email('Order items count: ' . count($items));

    // Preparar datos para PDF (misma forma que generar_pdf)
    $orderData = [
        'numericId' => $order['numeric_id'],
        'status' => $order['status'],
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
        'observations' => $order['observations'],
        'logoUrl' => 'https://errautomotriz.com/assets/images/err.gif'
    ];

    // Generar PDF con helper unificado (guarda archivo en /ordenes)
    require_once __DIR__ . '/pdf_helper.php';
    $pdfResult = generate_order_pdf($orderData);
    if (!$pdfResult['success']) {
        log_send_email('PDF helper error: ' . ($pdfResult['message'] ?? 'unknown'));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al generar PDF']);
        exit;
    }
    $pdfPath = $pdfResult['filepath'];
    if (!file_exists($pdfPath)) {
        log_send_email('PDF file not found: ' . $pdfPath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'PDF no encontrado en servidor']);
        exit;
    }
    $pdfContent = @file_get_contents($pdfPath);
    if ($pdfContent === false) {
        $err = error_get_last();
        log_send_email('PDF read failed: ' . ($err['message'] ?? 'unknown') . ' | path=' . $pdfPath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo leer el PDF generado']);
        exit;
    }
    log_send_email('PDF generated, size: ' . strlen($pdfContent));

    // Enviar email
    $to = $order['client_email'];
    $subject_raw = 'Envío de su Orden de Servicio/Cotización – ERR Automotriz';
    $message = "Estimado/a cliente,\n\nPor este medio le enviamos adjunta su Orden de Servicio o Cotización solicitada.\nQuedamos atentos a cualquier comentario o duda que tenga sobre la misma.\n\nAgradecemos su confianza.\nAtentamente,\nÁrea de Servicio\nERR Automotriz";

    if (file_exists('../../PHPMailer/src/PHPMailer.php')) {
        log_send_email('PHPMailer detected');
        require_once '../../PHPMailer/src/PHPMailer.php';
        require_once '../../PHPMailer/src/SMTP.php';
        require_once '../../PHPMailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'servicio@errautomotriz.online';
            $mail->Password = '3Errauto!';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('servicio@errautomotriz.online', 'ERR Automotriz');
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
        $boundary = md5(time());
        $subject = '=?UTF-8?B?' . base64_encode($subject_raw) . '?=';
        $headers = "From: servicio@errautomotriz.online\r\n";
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
