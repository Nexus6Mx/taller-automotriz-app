<?php
// Helper para generar y guardar PDF de una orden. Devuelve arreglo con success, filepath y url o message.
require_once __DIR__ . '/../../fpdf186/fpdf.php';

function generate_order_pdf($data) {
    try {
        // Aceptar tanto objetos como arrays
        if (is_object($data)) $d = $data;
        else $d = json_decode(json_encode($data));

        class PDFHelper extends FPDF { function Header() {} function Footer() {} }

        $pdf = new PDFHelper('P','mm','Letter');
        $pdf->AddPage();
        $pdf->SetFont('Arial','', 10);
        $pdf->SetAutoPageBreak(true, 10);

        // Encabezado con Logo y Título
        if (!empty($d->logoUrl)) {
            $logo = $d->logoUrl;
            $allowUrlFopen = ini_get('allow_url_fopen');
            // Permitir solo rutas locales o URLs si allow_url_fopen está habilitado
            $isRemote = (stripos($logo, 'http://') === 0 || stripos($logo, 'https://') === 0);
            if (!$isRemote || $allowUrlFopen) {
                @ $pdf->Image($logo, 10, 8, 33);
            }
        }
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0, 7, 'ERR Automotriz', 0, 1, 'C');
        $pdf->SetFont('Arial','B',14);
        $title = (isset($d->status) && $d->status === 'Cotizaci\u00f3n') ? 'COTIZACI\u00d3N' : 'ORDEN DE SERVICIO';
        $pdf->Cell(0, 7, $title, 0, 1, 'C');
        $pdf->SetFont('Arial','B',12);
        $label = (isset($d->status) && $d->status === 'Cotizaci\u00f3n') ? 'Cotizaci\u00f3n: ' : 'No. de Orden: ';
        $numericId = isset($d->numericId) ? $d->numericId : (isset($d->numeric_id) ? $d->numeric_id : '0');
        $pdf->Cell(190, 8, $label . $numericId, 1, 1, 'R');
        $pdf->Ln(5);

        // Datos del Cliente y Vehiculo
        $pdf->SetFont('Arial','B', 9);
        $clientName = isset($d->client->name) ? $d->client->name : (isset($d->client['name']) ? $d->client['name'] : '');
        $vehicleBrand = isset($d->vehicle->brand) ? $d->vehicle->brand : (isset($d->vehicle['brand']) ? $d->vehicle['brand'] : '');
        $pdf->Cell(95, 6, 'CLIENTE: ' . utf8_decode($clientName), 'LTR');
        $pdf->Cell(95, 6, 'MARCA/MODELO: ' . utf8_decode($vehicleBrand), 'LTR', 1);
        $pdf->SetFont('Arial','', 9);
        $clientAddress = isset($d->client->address) ? $d->client->address : '';
        $vehiclePlates = isset($d->vehicle->plates) ? $d->vehicle->plates : '';
        $pdf->Cell(95, 6, 'DIRECCION: ' . utf8_decode($clientAddress), 'LR');
        $pdf->Cell(95, 6, 'PLACAS: ' . utf8_decode($vehiclePlates), 'LR', 1);
        $clientCel = isset($d->client->cel) ? $d->client->cel : '';
        $vehicleYear = isset($d->vehicle->year) ? $d->vehicle->year : '';
        $vehicleKm = isset($d->vehicle->km) ? $d->vehicle->km : '';
        $pdf->Cell(95, 6, 'TELEFONO: ' . utf8_decode($clientCel), 'LRB');
        $pdf->Cell(95, 6, 'ANO: ' . utf8_decode($vehicleYear) . ' / KM: ' . utf8_decode($vehicleKm), 'LRB', 1);
        $pdf->Ln(5);

        // Tabla de conceptos
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(20, 7, 'CANT', 1, 0, 'C');
        $pdf->Cell(130, 7, 'DESCRIPCION', 1, 0, 'C');
        $pdf->Cell(40, 7, 'IMPORTE', 1, 1, 'C');
        $pdf->SetFont('Arial','',10);

        $items = [];
        if (isset($d->items) && is_array((array)$d->items)) $items = $d->items;
        foreach ($items as $item) {
            $qty = isset($item->qty) ? $item->qty : (isset($item['qty']) ? $item['qty'] : '');
            $description = isset($item->description) ? $item->description : (isset($item['description']) ? $item['description'] : '');
            $importe = isset($item->price) ? $item->price : (isset($item['price']) ? $item['price'] : 0);
            $pdf->Cell(20, 7, $qty, 1, 0, 'C');
            $pdf->Cell(130, 7, utf8_decode($description), 1, 0, 'L');
            $pdf->Cell(40, 7, '$' . number_format($importe, 2), 1, 1, 'R');
        }
        $pdf->Ln(5);

        // Observaciones y Totales
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(0, 7, 'Observaciones:', 'LTR', 1);
        $pdf->SetFont('Arial','',10);
        $observations = isset($d->observations) ? $d->observations : '';
        $pdf->MultiCell(190, 5, utf8_decode($observations), 'LBR');

        $subtotal = isset($d->subtotal) ? $d->subtotal : 0;
        $iva = isset($d->iva) ? $d->iva : 0;
        $total = isset($d->total) ? $d->total : 0;
        $ivaApplied = isset($d->ivaApplied) ? $d->ivaApplied : (isset($d->iva) && $d->iva > 0);

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(130, 7, '', 0, 0);
        $pdf->Cell(30, 7, 'SUBTOTAL', 1, 0, 'R');
        $pdf->Cell(30, 7, '$' . number_format($subtotal, 2), 1, 1, 'R');
        if($ivaApplied) {
            $pdf->Cell(130, 7, '', 0, 0);
            $pdf->Cell(30, 7, 'IVA (16%)', 1, 0, 'R');
            $pdf->Cell(30, 7, '$' . number_format($iva, 2), 1, 1, 'R');
        }
        $pdf->Cell(130, 7, '', 0, 0);
        $pdf->Cell(30, 7, 'TOTAL', 1, 0, 'R');
        $pdf->Cell(30, 7, '$' . number_format($total, 2), 1, 1, 'R');

        // Guardar archivo
        $root = dirname(__DIR__, 2); // repo root
        $ordenesDir = $root . '/ordenes';
        if (!file_exists($ordenesDir)) {
            @mkdir($ordenesDir, 0755, true);
        }
        $filename = ((isset($d->status) && $d->status === 'Cotizaci\u00f3n') ? 'Cotizacion_ERR_' : 'Orden_ERR_') . $numericId . '.pdf';
        $filepath = $ordenesDir . '/' . $filename;
        $pdf->Output('F', $filepath);

        // URL pública relativa al host
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $url = $scheme . '://' . $host . '/ordenes/' . $filename;

        return ['success' => true, 'filepath' => $filepath, 'url' => $url, 'filename' => $filename];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
