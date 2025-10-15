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
$user_id = verifyToken($db, $token);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token de autenticación requerido']);
    exit;
}

$data = json_decode($rawInput);
if (!$data || !isset($data->id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de orden requerido']);
    exit;
}

$orderId = $data->id;

try {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $orderId, 'user_id' => $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

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

    // generate PDF using FPDF (same as send_email)
    if (!file_exists('../../fpdf186/fpdf.php')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Librería FPDF no encontrada']);
        exit;
    }
    require_once '../../fpdf186/fpdf.php';

    class PDF2 extends FPDF { function Header() {} function Footer() {} }
    $pdf = new PDF2('P','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,7,'ERR Automotriz',0,1,'C');
    $pdf->SetFont('Arial','B',12);
    $label = ($orderData['status'] === 'Cotización') ? 'Cotización: ' : 'No. de Orden: ';
    $pdf->Cell(190,8,$label . $orderData['numericId'],1,1,'R');
    $pdf->Ln(4);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,5,'Cliente: ' . $orderData['client']['name'],0,1);
    $pdf->Cell(0,5,'Email: ' . $orderData['client']['email'],0,1);
    $pdf->Ln(4);
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(80,6,'Descripcion',1);
    $pdf->Cell(20,6,'Cant.',1);
    $pdf->Cell(30,6,'Precio',1);
    $pdf->Cell(30,6,'Importe',1);
    $pdf->Ln();
    $pdf->SetFont('Arial','',9);
    foreach($orderData['items'] as $it){
        $pdf->Cell(80,5,$it['description'],1);
        $pdf->Cell(20,5,$it['qty'],1,0,'C');
        $pdf->Cell(30,5,'$'.number_format($it['price']/$it['qty'],2),1,0,'R');
        $pdf->Cell(30,5,'$'.number_format($it['price'],2),1,0,'R');
        $pdf->Ln();
    }

    $pdfContent = $pdf->Output('S');
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

    // try PHPMailer
    if (file_exists('../../PHPMailer/src/PHPMailer.php')){
        require_once '../../PHPMailer/src/PHPMailer.php';
        require_once '../../PHPMailer/src/SMTP.php';
        require_once '../../PHPMailer/src/Exception.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try{
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'servicio@errautomotriz.online';
            $mail->Password = '3Errauto!';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->setFrom('servicio@errautomotriz.online','ERR Automotriz');
            foreach($toAddresses as $t) $mail->addAddress($t);
            $mail->Subject = $subject;
            $mail->Body = $bodyText;
            $mail->addStringAttachment($pdfContent, 'orden_' . $order['numeric_id'] . '.pdf');
            $mail->send();
            log_send_invoice('PHPMailer send success');
            echo json_encode(['success'=>true,'message'=>'Solicitud de facturación enviada']);
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
    $headers = "From: servicio@errautomotriz.online\r\n";
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
        echo json_encode(['success'=>true,'message'=>'Solicitud de facturación enviada']);
    } else {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Error enviando algunas solicitudes de facturación']);
    }

} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error interno: ' . $e->getMessage()]);
}
