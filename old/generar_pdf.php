<?php
// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json; charset=UTF-8");

// Incluir la librería FPDF
require('fpdf186/fpdf.php'); // Asegúrate que la ruta a fpdf.php sea correcta

// Clase PDF personalizada para manejar encabezado y pie de página si es necesario
class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        // Logo y Título
    }

    // Pie de página
    function Footer()
    {
        // Posición a 1.5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Número de página
        $this->Cell(0,10, 'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

// Recibir los datos de la orden desde el archivo HTML
$json = file_get_contents('php://input');
$data = json_decode($json);

// Validar que los datos llegaron
if (!$data || !isset($data->numericId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos de la orden.']);
    exit;
}

// Crear una nueva instancia de PDF
$pdf = new PDF('P','mm','Letter');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','', 10);
$pdf->SetAutoPageBreak(true, 15);

// --- Contenido del PDF ---

// Encabezado con Logo y Título
$pdf->Image($data->logoUrl, 10, 8, 33);
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0, 10, 'ERR Automotriz', 0, 1, 'C');
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 10, 'ORDEN DE SERVICIO', 0, 1, 'C');
$pdf->SetFont('Arial','B',12);
$pdf->Cell(190, 10, '#' . $data->numericId, 1, 1, 'R');
$pdf->Ln(5);

// Datos del Cliente y Vehículo (en una tabla)
$pdf->SetFont('Arial','B', 10);
$pdf->Cell(95, 7, 'CLIENTE: ' . utf8_decode($data->client->name), 'LTR', 0, 'L');
$pdf->Cell(95, 7, 'MARCA/MODELO: ' . utf8_decode($data->vehicle->brand), 'LTR', 1, 'L');
$pdf->SetFont('Arial','', 10);
$pdf->Cell(95, 7, 'DIRECCION: ' . utf8_decode($data->client->address), 'LR', 0, 'L');
$pdf->Cell(95, 7, 'PLACAS: ' . utf8_decode($data->vehicle->plates), 'LR', 1, 'L');
$pdf->Cell(95, 7, 'TELEFONO: ' . utf8_decode($data->client->cel), 'LR', 0, 'L');
$pdf->Cell(95, 7, 'ANO: ' . utf8_decode($data->vehicle->year), 'LR', 1, 'L');
$pdf->Cell(95, 7, 'RFC: ' . utf8_decode($data->client->rfc), 'LBR', 0, 'L');
$pdf->Cell(95, 7, 'KM: ' . utf8_decode($data->vehicle->km), 'LBR', 1, 'L');
$pdf->Ln(5);


// Tabla de conceptos
$pdf->SetFont('Arial','B',10);
$pdf->Cell(20, 7, 'CANT', 1, 0, 'C');
$pdf->Cell(130, 7, 'DESCRIPCION', 1, 0, 'C');
$pdf->Cell(40, 7, 'IMPORTE', 1, 1, 'C');

$pdf->SetFont('Arial','',10);
$subtotal = 0;
foreach ($data->items as $item) {
    $importe = $item->qty * $item->price;
    $subtotal += $importe;
    $pdf->Cell(20, 7, $item->qty, 1, 0, 'C');
    $pdf->Cell(130, 7, utf8_decode($item->description), 1, 0, 'L');
    $pdf->Cell(40, 7, '$' . number_format($importe, 2), 1, 1, 'R');
}
$pdf->Ln(5);

// Observaciones
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0, 7, 'Observaciones:', 'LTR', 1);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(190, 5, utf8_decode($data->observations), 'LBR');
$pdf->Ln(5);

// Totales
$iva = $data->ivaApplied ? $subtotal * 0.16 : 0;
$total = $subtotal + $iva;

$pdf->SetFont('Arial','B',10);
$pdf->Cell(130, 7, '', 0, 0);
$pdf->Cell(30, 7, 'SUBTOTAL', 1, 0, 'R');
$pdf->Cell(30, 7, '$' . number_format($subtotal, 2), 1, 1, 'R');

if($data->ivaApplied) {
    $pdf->Cell(130, 7, '', 0, 0);
    $pdf->Cell(30, 7, 'IVA (16%)', 1, 0, 'R');
    $pdf->Cell(30, 7, '$' . number_format($iva, 2), 1, 1, 'R');
}

$pdf->Cell(130, 7, '', 0, 0);
$pdf->Cell(30, 7, 'TOTAL', 1, 0, 'R');
$pdf->Cell(30, 7, '$' . number_format($total, 2), 1, 1, 'R');


// Guardar el archivo PDF en el servidor
$filename = 'Orden_ERR_' . $data->numericId . '.pdf';
$filepath = 'ordenes/' . $filename; 
$pdf->Output('F', $filepath);

// Devolver la URL del archivo guardado
$url = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $filepath;
echo json_encode(['status' => 'success', 'url' => $url]);

?>
