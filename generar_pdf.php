<?php
// Permitir solicitudes desde cualquier origen (CORS) para que Netlify pueda comunicarse con Hostinger
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Responder a la solicitud preliminar OPTIONS del navegador
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Usar el helper unificado para garantizar el mismo maquetado que Enviar/Facturar
require_once __DIR__ . '/api/orders/pdf_helper.php';

// Recibir los datos de la orden enviados desde la aplicación
$json = file_get_contents('php://input');
$data = json_decode($json);

// Si no se reciben datos, detener y enviar un error.
if (!$data || !isset($data->numericId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos de la orden.']);
    exit;
}

// Normalizar datos a la misma forma que el helper espera
$orderData = [
    'numericId' => $data->numericId ?? '',
    'status' => $data->status ?? '',
    'createdAt' => $data->createdAt ?? null,
    'client' => [
        'name' => $data->client->name ?? '',
        'email' => $data->client->email ?? '',
        'cel' => $data->client->cel ?? '',
        'address' => $data->client->address ?? '',
        'rfc' => $data->client->rfc ?? ''
    ],
    'vehicle' => [
        'brand' => $data->vehicle->brand ?? '',
        'plates' => $data->vehicle->plates ?? '',
        'year' => $data->vehicle->year ?? '',
        'km' => $data->vehicle->km ?? '',
        'gasLevel' => $data->vehicle->gasLevel ?? ''
    ],
    'items' => [],
    'subtotal' => (float)($data->subtotal ?? 0),
    'iva' => (float)($data->iva ?? 0),
    'total' => (float)($data->total ?? 0),
    'ivaApplied' => isset($data->ivaApplied) ? (bool)$data->ivaApplied : null,
    'observations' => $data->observations ?? '',
    'logoUrl' => $data->logoUrl ?? null,
];

if (!empty($data->items) && is_array($data->items)) {
    foreach ($data->items as $it) {
        $orderData['items'][] = [
            'qty' => $it->qty ?? 0,
            'description' => $it->description ?? '',
            'price' => $it->price ?? 0,
        ];
    }
}

// Generar PDF con el helper unificado
$pdfBytes = generateOrderPDF($orderData);

// Guardar el archivo PDF en el servidor
$filename = ((isset($data->status) && stripos((string)$data->status, 'cotiz') !== false) ? 'Cotizacion_ERR_' : 'Orden_ERR_') . $data->numericId . '.pdf';
if (!file_exists('ordenes')) {
    mkdir('ordenes', 0755, true);
}
$filepath = 'ordenes/' . $filename;
file_put_contents($filepath, $pdfBytes);

// Devolver la URL del archivo guardado
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$url = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . $filepath;
echo json_encode(['status' => 'success', 'url' => $url, 'message' => 'PDF generado y guardado exitosamente.']);

?>

