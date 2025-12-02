<?php
// api/orders/send_invoice.php

// Similar to send_email.php but sends to predefined billing addresses
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Variables SMTP globales
$SMTP_CONFIG = [];

// Cargar variables de entorno desde .env si existe
function loadEnvFile($path) {
    global $SMTP_CONFIG;
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue; // Ignorar comentarios
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $SMTP_CONFIG[$key] = $value;
            }
        }
        return true;
    }
    return false;
}

// Helper para obtener config SMTP
function getSmtpConfig($key, $default = '') {
    global $SMTP_CONFIG;
    if (isset($SMTP_CONFIG[$key]) && $SMTP_CONFIG[$key] !== '') {
        return $SMTP_CONFIG[$key];
    }
    $env = getenv($key);
    if ($env !== false && $env !== '') {
        return $env;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    return $default;
}

// Intentar cargar .env desde múltiples ubicaciones posibles
$envLoaded = loadEnvFile(__DIR__ . '/../.env');
if (!$envLoaded) {
    $envLoaded = loadEnvFile(__DIR__ . '/../../api/.env');
}
if (!$envLoaded) {
    $envLoaded = loadEnvFile($_SERVER['DOCUMENT_ROOT'] . '/api/.env');
}

require_once '../config/database.php';
require_once '../utils/cors.php';
require_once '../auth/verify.php';
require_once '../users/log_audit.php';
require_once __DIR__ . '/pdf_helper.php';

$database = new Database();
$db = $database->getConnection();

// Simple logger
function log_send_invoice($text) {
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) @mkdir($logsDir, 0755, true);
    $file = $logsDir . '/send_invoice.log';
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL;
    @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}

$rawInput = file_get_contents('php://input');
log_send_invoice('Request received: ' . $rawInput);

// Safe Authorization header extraction
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
// Fallbacks: allow token via cookie, query string or JSON body (shared hosting may strip Authorization headers)
if (!$token) {
    if (!empty($_COOKIE['authToken'])) {
        $token = $_COOKIE['authToken'];
    }
}
if (!$token) {
    if (!empty($_GET['token'])) {
        $token = $_GET['token'];
    } else if (!empty($rawInput)) {
        $j = json_decode($rawInput, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($j['token'])) {
            $token = $j['token'];
        }
    }
}
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

// Permisos: solo Administrador y Operador pueden solicitar facturación
if (!in_array($user_role, ['Administrador', 'Operador'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para solicitar facturación.']);
    exit;
}

$jsonObj = json_decode($rawInput, true);
$orderId = null;
$numericId = null;
if (is_array($jsonObj)) {
    if (isset($jsonObj['id'])) $orderId = (int)$jsonObj['id'];
    if (isset($jsonObj['numericId'])) $numericId = (int)$jsonObj['numericId'];
}
// Fallbacks for form or query submissions
if (!$orderId && isset($_POST['id'])) $orderId = (int)$_POST['id'];
if (!$numericId && isset($_POST['numericId'])) $numericId = (int)$_POST['numericId'];
if (!$orderId && isset($_GET['id'])) $orderId = (int)$_GET['id'];
if (!$numericId && isset($_GET['numericId'])) $numericId = (int)$_GET['numericId'];

if (!$orderId && !$numericId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de orden requerido']);
    exit;
}

try {
    $order = null;
    if ($orderId) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // If not found by id and we have numericId, try by numeric_id
    if (!$order && $numericId) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE numeric_id = :numeric_id LIMIT 1");
        $stmt->execute(['numeric_id' => $numericId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order) {
            $orderId = (int)$order['id'];
        }
    }

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Orden no encontrada']);
        exit;
    }

    // fetch items
    $stmtItems = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmtItems->execute(['order_id' => $orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // prepare orderData (same shape as send_email)
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
        'logoUrl' => $logoPath
    ];

    // generate PDF using shared helper (same layout as imprimir)
    $pdfContent = generateOrderPDF($orderData);
    log_send_invoice('PDF size (helper): ' . strlen($pdfContent));

    // Helpers to determine admin user and fetch recipients
    $isAdmin = false;
    $adminUserId = null;
    try {
        $stmtUser = $db->prepare("SELECT email FROM users WHERE id = :id");
        $stmtUser->execute(['id' => $user_id]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $email = $userRow ? strtolower(trim($userRow['email'])) : '';
        // Assumption: admin is the account 'admin@errautomotriz.online'
        $isAdmin = ($email === 'admin@errautomotriz.online');
        // Find admin user id (by the same email)
        $stmtAdmin = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtAdmin->execute(['email' => 'admin@errautomotriz.online']);
        $adminUserId = ($row = $stmtAdmin->fetch(PDO::FETCH_ASSOC)) ? (int)$row['id'] : null;
    } catch (Exception $e) {
        // If we cannot determine admin, proceed to use current user recipients
        log_send_invoice('Admin detection error: ' . $e->getMessage());
    }

    // Ensure recipients table exists (first-run safety on production)
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS invoice_recipients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_invoice_recipients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {
        log_send_invoice('Ensure table failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo preparar la tabla de destinatarios de facturación.']);
        exit;
    }

    // recipients for billing (always use admin-configured list if available; else use current user's list)
    $targetUserId = ($adminUserId && !$isAdmin) ? $adminUserId : $user_id;
    try {
        $stmtRecipients = $db->prepare("SELECT email FROM invoice_recipients WHERE user_id = :user_id ORDER BY id ASC");
        $stmtRecipients->execute(['user_id' => $targetUserId]);
        $toAddresses = $stmtRecipients->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        log_send_invoice('Recipients fetch failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al obtener correos de facturación.']);
        exit;
    }
    log_send_invoice('Recipients (user ' . $targetUserId . '): ' . implode(', ', $toAddresses));

    if (empty($toAddresses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No hay correos de facturación configurados. Pide a un administrador configurar los destinatarios en la sección de configuración.']);
        exit;
    }
    $subject = 'Solicitud de facturación de orden – ERR Automotriz';
    $bodyText = "Estimados,\n\nPor este medio solicitamos la facturación correspondiente de la siguiente orden de servicio, que se adjunta en PDF.\n\nAgradecemos su pronta atención y quedamos atentos a cualquier requisito adicional o comentario para poder completar el proceso.\n\nAtentamente,\nÁrea de Servicio\nERR Automotriz";

    // Before sending, ensure status is set to 'En Facturación' (idempotent safeguard)
    try {
        if ($order['status'] !== 'En Facturación') {
            $stmtUpd = $db->prepare("UPDATE orders SET status = 'En Facturación' WHERE id = :id");
            $stmtUpd->execute(['id' => $orderId]);
        }
    } catch (Exception $e) {
        log_send_invoice('Status update safeguard failed: ' . $e->getMessage());
        // Continue anyway; frontend may have updated it already
    }

    // try PHPMailer
    if (file_exists('../../PHPMailer/src/PHPMailer.php')){
        require_once '../../PHPMailer/src/PHPMailer.php';
        require_once '../../PHPMailer/src/SMTP.php';
        require_once '../../PHPMailer/src/Exception.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try{
            // Obtener configuración SMTP
            $smtpHost = getSmtpConfig('SMTP_HOST', 'smtp.hostinger.com');
            $smtpUser = getSmtpConfig('SMTP_USER', '');
            $smtpPass = getSmtpConfig('SMTP_PASS', '');
            $smtpPort = (int)getSmtpConfig('SMTP_PORT', '465');
            $smtpFrom = getSmtpConfig('SMTP_FROM', $smtpUser);
            $smtpFromName = getSmtpConfig('SMTP_FROM_NAME', 'ERR Automotriz');
            
            log_send_invoice("SMTP Config - Host: $smtpHost, Port: $smtpPort, User: $smtpUser, From: $smtpFrom");
            
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpPort;

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            if ($smtpFrom) {
                $mail->setFrom($smtpFrom, $smtpFromName);
            }
            foreach($toAddresses as $t) $mail->addAddress($t);
            $mail->Subject = $subject;
            $mail->Body = $bodyText;
            $mail->addStringAttachment($pdfContent, 'orden_' . $order['numeric_id'] . '.pdf');
            $mail->send();
            log_send_invoice('PHPMailer send success');
            echo json_encode(['success'=>true,'message'=>'Solicitud de facturación enviada con éxito']);
            log_send_invoice('Audit: order_invoice_requested');
            log_audit($db, $user_id, 'order_invoice_requested', 'order', $orderId, null);
            exit;
        } catch(Exception $e){
            log_send_invoice('PHPMailer error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success'=>false,'message'=>'Error enviando con PHPMailer: ' . $e->getMessage()]);
            exit;
        }
    }

    // fallback to mail()
    $boundary = md5(time());
    $fromHeader = getenv('SMTP_FROM') ?: (getenv('SMTP_USER') ?: '');
    $headers = $fromHeader ? ("From: $fromHeader\r\n") : '';
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $bodyText . "\r\n\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/pdf; name=\"orden_{$order['numeric_id']}.pdf\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"orden_{$order['numeric_id']}.pdf\"\r\n\r\n";
    $body .= chunk_split(base64_encode($pdfContent)) . "\r\n";
    $body .= "--$boundary--";

    $allSent = true;
    foreach($toAddresses as $t){
        $res = mail($t, $subjectEnc, $body, $headers);
        log_send_invoice('mail() to ' . $t . ' => ' . ($res ? 'true' : 'false'));
        if (!$res) $allSent = false;
    }

    if ($allSent){
    echo json_encode(['success'=>true,'message'=>'Solicitud de facturación enviada con éxito']);
        log_audit($db, $user_id, 'order_invoice_requested', 'order', $orderId, null);
    } else {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Error enviando algunas solicitudes de facturación']);
    }

} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error interno: ' . $e->getMessage()]);
}
