<?php
// Permitir solicitudes desde cualquier origen (CORS) para que Netlify pueda comunicarse con Hostinger
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Responder a la solicitud preliminar OPTIONS del navegador
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Incluir la librería FPDF (Asegúrate de que esta ruta sea correcta en tu hosting)
require('fpdf186/fpdf.php'); 

// Recibir los datos de la orden enviados desde la aplicación
$json = file_get_contents('php://input');
$data = json_decode($json);

// Si no se reciben datos, detener y enviar un error.
if (!$data || !isset($data->numericId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos de la orden.']);
    exit;
}

// Clase PDF personalizada
class PDF extends FPDF
{
    function Header() {}
    function Footer() {}
}

// Crear el documento PDF
$pdf = new PDF('P','mm','Letter');
$pdf->AddPage();
$pdf->SetFont('Arial','', 10);
$pdf->SetAutoPageBreak(true, 10);

// --- Contenido del PDF ---

// Encabezado con Logo y Título
$pdf->Image($data->logoUrl, 10, 8, 33);
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0, 7, 'ERR Automotriz', 0, 1, 'C');
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 7, 'ORDEN DE SERVICIO', 0, 1, 'C');
$pdf->SetFont('Arial','B',12);
$pdf->Cell(190, 8, '#' . $data->numericId, 1, 1, 'R');
$pdf->Ln(5);

// Datos del Cliente y Vehículo
$pdf->SetFont('Arial','B', 9);
$pdf->Cell(95, 6, 'CLIENTE: ' . utf8_decode($data->client->name), 'LTR');
$pdf->Cell(95, 6, 'MARCA/MODELO: ' . utf8_decode($data->vehicle->brand), 'LTR', 1);
$pdf->SetFont('Arial','', 9);
$pdf->Cell(95, 6, 'DIRECCION: ' . utf8_decode($data->client->address), 'LR');
$pdf->Cell(95, 6, 'PLACAS: ' . utf8_decode($data->vehicle->plates), 'LR', 1);
$pdf->Cell(95, 6, 'TELEFONO: ' . utf8_decode($data->client->cel), 'LRB');
$pdf->Cell(95, 6, 'ANO: ' . utf8_decode($data->vehicle->year) . ' / KM: ' . utf8_decode($data->vehicle->km), 'LRB', 1);
$pdf->Ln(5);

// Tabla de conceptos
$pdf->SetFont('Arial','B',10);
$pdf->Cell(20, 7, 'CANT', 1, 0, 'C');
$pdf->Cell(130, 7, 'DESCRIPCION', 1, 0, 'C');
$pdf->Cell(40, 7, 'IMPORTE', 1, 1, 'C');

$pdf->SetFont('Arial','',10);
foreach ($data->items as $item) {
    $importe = $item->qty * $item->price;
    $pdf->Cell(20, 7, $item->qty, 1, 0, 'C');
    $pdf->Cell(130, 7, utf8_decode($item->description), 1, 0, 'L');
    $pdf->Cell(40, 7, '$' . number_format($importe, 2), 1, 1, 'R');
}
$pdf->Ln(5);

// Observaciones y Totales
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0, 7, 'Observaciones:', 'LTR', 1);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(190, 5, utf8_decode($data->observations), 'LBR');

$pdf->SetFont('Arial','B',10);
$pdf->Cell(130, 7, '', 0, 0);
$pdf->Cell(30, 7, 'SUBTOTAL', 1, 0, 'R');
$pdf->Cell(30, 7, '$' . number_format($data->subtotal, 2), 1, 1, 'R');

if($data->ivaApplied) {
    $pdf->Cell(130, 7, '', 0, 0);
    $pdf->Cell(30, 7, 'IVA (16%)', 1, 0, 'R');
    $pdf->Cell(30, 7, '$' . number_format($data->iva, 2), 1, 1, 'R');
}

$pdf->Cell(130, 7, '', 0, 0);
$pdf->Cell(30, 7, 'TOTAL', 1, 0, 'R');
$pdf->Cell(30, 7, '$' . number_format($data->total, 2), 1, 1, 'R');

// Guardar el archivo PDF en el servidor
$filename = 'Orden_ERR_' . $data->numericId . '.pdf';
if (!file_exists('ordenes')) {
    mkdir('ordenes', 0755, true);
}
$filepath = 'ordenes/' . $filename; 
$pdf->Output('F', $filepath);

// Devolver la URL del archivo guardado
$url = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $filepath;
echo json_encode(['status' => 'success', 'url' => $url, 'message' => 'PDF generado y guardado exitosamente.']);

?>

