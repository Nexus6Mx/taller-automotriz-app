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
$user_id = verifyToken($db, $token);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token de autenticación requerido']);
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
    $query = "SELECT * FROM orders WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $orderId);
    $stmt->bindParam(':user_id', $user_id);
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

    // Preparar datos para el PDF
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
        'logoUrl' => 'https://errautomotriz.com/assets/images/err.gif' // Ajustar según sea necesario
    ];

    // Generar PDF
    if (!file_exists('../../fpdf186/fpdf.php')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Librería FPDF no encontrada. Instale FPDF en la carpeta fpdf186 del directorio raíz.']);
        exit;
    }
    require_once '../../fpdf186/fpdf.php';

    class PDF extends FPDF {
        function Header() {}
        function Footer() {}
    }

    $pdf = new PDF('P','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFont('Arial','', 10);
    $pdf->SetAutoPageBreak(true, 10);

    try {

    // Encabezado
    // $pdf->Image($orderData['logoUrl'], 10, 8, 33); // Temporalmente comentado para evitar errores con URLs externas
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0, 7, 'ERR Automotriz', 0, 1, 'C');
    $pdf->SetFont('Arial','B',14);
    $title = ($orderData['status'] === 'Cotización') ? 'COTIZACIÓN' : 'ORDEN DE SERVICIO';
    $pdf->Cell(0, 7, $title, 0, 1, 'C');
    $pdf->SetFont('Arial','B',12);
    $label = ($orderData['status'] === 'Cotización') ? 'Cotización: ' : 'No. de Orden: ';
    $pdf->Cell(190, 8, $label . $orderData['numericId'], 1, 1, 'R');

    // Cliente
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0, 6, 'Datos del Cliente', 0, 1);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0, 5, 'Nombre: ' . $orderData['client']['name'], 0, 1);
    $pdf->Cell(0, 5, 'Teléfono: ' . $orderData['client']['cel'], 0, 1);
    $pdf->Cell(0, 5, 'Dirección: ' . $orderData['client']['address'], 0, 1);
    $pdf->Cell(0, 5, 'RFC: ' . $orderData['client']['rfc'], 0, 1);

    // Vehículo
    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0, 6, 'Datos del Vehículo', 0, 1);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0, 5, 'Marca/Modelo: ' . $orderData['vehicle']['brand'], 0, 1);
    $pdf->Cell(0, 5, 'Placas: ' . $orderData['vehicle']['plates'], 0, 1);
    $pdf->Cell(0, 5, 'Año: ' . $orderData['vehicle']['year'], 0, 1);
    $pdf->Cell(0, 5, 'Kilometraje: ' . $orderData['vehicle']['km'], 0, 1);
    $pdf->Cell(0, 5, 'Nivel de Gasolina: ' . $orderData['vehicle']['gasLevel'], 0, 1);

    // Items
    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0, 6, 'Conceptos', 0, 1);
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(80, 6, 'Descripción', 1);
    $pdf->Cell(20, 6, 'Cant.', 1);
    $pdf->Cell(30, 6, 'Precio Unit.', 1);
    $pdf->Cell(30, 6, 'Importe', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial','',9);
    foreach ($orderData['items'] as $item) {
        $pdf->Cell(80, 5, $item['description'], 1);
        $pdf->Cell(20, 5, $item['qty'], 1, 0, 'C');
        $pdf->Cell(30, 5, '$' . number_format($item['price'] / $item['qty'], 2), 1, 0, 'R');
        $pdf->Cell(30, 5, '$' . number_format($item['price'], 2), 1, 0, 'R');
        $pdf->Ln();
    }

    // Totales
    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(130, 6, '', 0);
    $pdf->Cell(30, 6, 'Subtotal:', 0);
    $pdf->Cell(30, 6, '$' . number_format($orderData['subtotal'], 2), 0, 1, 'R');
    $pdf->Cell(130, 6, '', 0);
    $pdf->Cell(30, 6, 'IVA:', 0);
    $pdf->Cell(30, 6, '$' . number_format($orderData['iva'], 2), 0, 1, 'R');
    $pdf->Cell(130, 6, '', 0);
    $pdf->Cell(30, 6, 'Total:', 0);
    $pdf->Cell(30, 6, '$' . number_format($orderData['total'], 2), 0, 1, 'R');

    // Observaciones
    if (!empty($orderData['observations'])) {
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(0, 6, 'Observaciones', 0, 1);
        $pdf->SetFont('Arial','',10);
        $pdf->MultiCell(0, 5, $orderData['observations']);
    }

        // Guardar PDF en memoria
        $pdfContent = $pdf->Output('S');
        log_send_email('PDF generated, size: ' . strlen($pdfContent));
    } catch (Exception $e) {
        log_send_email('PDF generation error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al generar PDF: ' . $e->getMessage()]);
        exit;
    }    // Enviar email
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