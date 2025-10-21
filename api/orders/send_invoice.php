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

require_once '../config/database.php';
require_once '../utils/cors.php';
require_once '../auth/verify.php';
require_once '../users/log_audit.php';

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

        // Use helper to generate and store the PDF file to ensure identical output as generar_pdf
        require_once __DIR__ . '/pdf_helper.php';
        $pdfResult = generate_order_pdf($orderData);
        if (!$pdfResult['success']) {
            $msg = $pdfResult['message'] ?? 'Error al generar PDF';
            log_send_invoice('PDF helper error: ' . $msg);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $pdfPath = $pdfResult['filepath'];
        if (!file_exists($pdfPath)) {
            log_send_invoice('PDF file not found: ' . $pdfPath);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'PDF no encontrado en servidor']);
            exit;
        }
        $pdfContent = @file_get_contents($pdfPath);
        if ($pdfContent === false) {
            $err = error_get_last();
            log_send_invoice('PDF read failed: ' . ($err['message'] ?? 'unknown') . ' | path=' . $pdfPath);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo leer el PDF generado']);
            exit;
        }
        log_send_invoice('PDF size: ' . strlen($pdfContent));

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

    // recipients for billing (always use admin-configured list if available; else use current user's list)
    $targetUserId = ($adminUserId && !$isAdmin) ? $adminUserId : $user_id;
    $stmtRecipients = $db->prepare("SELECT email FROM invoice_recipients WHERE user_id = :user_id ORDER BY id ASC");
    $stmtRecipients->execute(['user_id' => $targetUserId]);
    $toAddresses = $stmtRecipients->fetchAll(PDO::FETCH_COLUMN);
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

    // Envío de correo configurable
    $mailCfg = null;
    if (file_exists(__DIR__ . '/../config/mail.php')) {
        $mailCfg = require __DIR__ . '/../config/mail.php';
    } elseif (file_exists(__DIR__ . '/../config/mail.example.php')) {
        $mailCfg = require __DIR__ . '/../config/mail.example.php';
    }
    $transport = is_array($mailCfg) && !empty($mailCfg['transport']) ? strtolower($mailCfg['transport']) : 'mail';
    $fromCfg = $mailCfg['smtp']['from'] ?? ['address' => 'servicio@errautomotriz.online', 'name' => 'ERR Automotriz'];
    $phpMailerPath = __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
    $phpMailerAvailable = file_exists($phpMailerPath);
    $smtpWanted = ($transport === 'smtp');

    // Modo 'log': guardar EML para cada destinatario y responder success
    if ($transport === 'log') {
        $boundary = md5(time());
        $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $fromHeader = isset($fromCfg['address']) ? $fromCfg['address'] : 'servicio@errautomotriz.online';
        $outbox = __DIR__ . '/../logs/outbox';
        if (!is_dir($outbox)) @mkdir($outbox, 0755, true);
        foreach ($toAddresses as $t) {
            $eml = '';
            $eml .= 'From: ' . $fromHeader . "\r\n";
            $eml .= 'To: ' . $t . "\r\n";
            $eml .= 'Subject: ' . $subjectEnc . "\r\n";
            $eml .= 'MIME-Version: 1.0' . "\r\n";
            $eml .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n\r\n";
            $eml .= '--' . $boundary . "\r\n";
            $eml .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
            $eml .= 'Content-Transfer-Encoding: 7bit' . "\r\n\r\n";
            $eml .= $bodyText . "\r\n\r\n";
            $eml .= '--' . $boundary . "\r\n";
            $eml .= 'Content-Type: application/pdf; name="orden_' . $order['numeric_id'] . '.pdf"' . "\r\n";
            $eml .= 'Content-Transfer-Encoding: base64' . "\r\n";
            $eml .= 'Content-Disposition: attachment; filename="orden_' . $order['numeric_id'] . '.pdf"' . "\r\n\r\n";
            $eml .= chunk_split(base64_encode($pdfContent)) . "\r\n";
            $eml .= '--' . $boundary . '--';

            $fname = date('Ymd_His') . '_invoice_orden_' . $order['numeric_id'] . '_' . preg_replace('/[^a-z0-9_.-]+/i','_', $t) . '.eml';
            @file_put_contents($outbox . '/' . $fname, $eml);
        }
        echo json_encode(['success'=>true,'message'=>'Solicitudes guardadas en outbox (modo log)']);
        log_audit($db, $user_id, 'order_invoice_requested', 'order', $orderId, null);
        exit;
    }

    $sentAll = false;
    if ($smtpWanted && $phpMailerAvailable) {
        require_once $phpMailerPath;
        require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $smtp = $mailCfg['smtp'] ?? [];
            $mail->Host = $smtp['host'] ?? 'localhost';
            $mail->Port = $smtp['port'] ?? 25;
            $mail->SMTPAuth = !empty($smtp['username']);
            if ($mail->SMTPAuth) {
                $mail->Username = $smtp['username'];
                $mail->Password = $smtp['password'] ?? '';
            }
            $secure = strtolower($smtp['secure'] ?? '');
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            if (!empty($smtp['timeout'])) $mail->Timeout = (int)$smtp['timeout'];

            $mail->setFrom($fromCfg['address'], $fromCfg['name']);
            foreach ($toAddresses as $t) $mail->addAddress($t);
            $mail->Subject = $subject;
            $mail->Body = $bodyText;
            $mail->isHTML(false);
            $mail->addStringAttachment($pdfContent, 'orden_' . $order['numeric_id'] . '.pdf');
            $mail->send();
            log_send_invoice('PHPMailer send success');
            $sentAll = true;
        } catch (Exception $e) {
            log_send_invoice('PHPMailer SMTP error: ' . $e->getMessage());
            // Fallback a mail()
        }
    } elseif ($smtpWanted && !$phpMailerAvailable) {
        log_send_invoice('SMTP configured but PHPMailer not found, falling back to mail()');
    }

    if (!$sentAll) {
        // fallback to mail()
        $boundary = md5(time());
        $headers = "From: {$fromCfg['address']}\r\n";
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
        foreach ($toAddresses as $t) {
            $res = mail($t, $subjectEnc, $body, $headers);
            log_send_invoice('mail() to ' . $t . ' => ' . ($res ? 'true' : 'false'));
            if (!$res) $allSent = false;
        }

        if (!$allSent) {
            log_send_invoice('mail() failed for one or more recipients; no SMTP or mail() available');
        }
        $sentAll = $allSent;
    }

    if ($sentAll) {
        echo json_encode(['success'=>true,'message'=>'Solicitud de facturación enviada con éxito']);
        log_audit($db, $user_id, 'order_invoice_requested', 'order', $orderId, null);
    } else {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'No se pudieron enviar correos. Configure api/config/mail.php (SMTP válido) o use transport = "log" en desarrollo.']);
    }

} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error interno: ' . $e->getMessage()]);
}
