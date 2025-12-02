<?php
// --- CONFIGURACIÓN ---
// Define el ID del usuario al que se le asignarán todos los datos.
// Ve a tu base de datos en phpMyAdmin, busca en la tabla `users` el ID de tu cuenta.
// Por lo general, si eres el primer usuario, será 1.
const USER_ID = 1; 
// -------------------

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); // Aumentar el tiempo de ejecución a 10 minutos

include_once 'api/config/database.php';

function format_date_for_mysql($date_string) {
    if (empty($date_string) || $date_string === 'N/A') {
        return date('Y-m-d H:i:s'); // Devuelve la fecha actual si no hay fecha
    }
    try {
        // Formato esperado: "27/9/2025"
        $date = DateTime::createFromFormat('d/n/Y', $date_string);
        if ($date === false) {
             return date('Y-m-d H:i:s');
        }
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return date('Y-m-d H:i:s');
    }
}

function parse_items($items_string) {
    if (empty($items_string)) return [];

    // Ejemplo: "1x Servicio de cambio de kit de distribución ($0.00)"
    $items = [];
    $parts = explode(';', $items_string); // En caso de que haya múltiples items separados por ;

    foreach ($parts as $part) {
        $part = trim($part);
        if (preg_match('/^(\d+x?)\s*(.*?)\s*\(\$(.*?)\)$/', $part, $matches)) {
            $qty_str = $matches[1];
            $description = trim($matches[2]);
            $price = floatval(str_replace(',', '', $matches[3]));
            
            // Limpiar la cantidad, quitando la 'x'
            $qty = intval(str_replace('x', '', $qty_str));
            if ($qty == 0) $qty = 1;

            if (!empty($description)) {
                 $items[] = [
                    'qty' => $qty,
                    'description' => $description,
                    'price' => $price
                ];
            }
        }
    }
    return $items;
}


$action = $_POST['action'] ?? '';
$output = '';

if ($action === 'migrate') {
    ob_start();
    
    echo "<h1>Iniciando migración...</h1>";

    $database = new Database();
    $db = $database->getConnection();
    $csvFile = 'historial_completo_ordenes_2025-09-28.csv';

    if (!file_exists($csvFile)) {
        die("<p style='color:red;'>ERROR: No se encontró el archivo '$csvFile'. Asegúrate de haberlo subido a la misma carpeta que este script.</p>");
    }

    $db->beginTransaction();
    try {
        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Leer la cabecera para ignorarla
        $rowCount = 0;

        while (($row = fgetcsv($file)) !== FALSE) {
            $rowCount++;
            // Mapeo de columnas según tu CSV
            $numeric_id = $row[0];
            $created_at = format_date_for_mysql($row[1]);
            $status = $row[2];
            $client_name = trim($row[3]);
            $client_cel = $row[4];
            $client_address = $row[5];
            $client_rfc = $row[6];
            $client_email = $row[7];
            $vehicle_brand = $row[8];
            $vehicle_plates = $row[9];
            $vehicle_year = $row[10] ? (int)$row[10] : null;
            $vehicle_km = $row[11] ? (int)$row[11] : null;
            $vehicle_gas_level = $row[12];
            $items_str = $row[13];
            $subtotal = (float)$row[14];
            $iva = (float)$row[15];
            $total = (float)$row[16];
            $observations = $row[17];
            $iva_applied = $iva > 0;

            echo "<hr><strong>Procesando Orden #$numeric_id para '$client_name'</strong><br>";

            // 1. Insertar orden principal
            $stmt = $db->prepare(
                "INSERT INTO orders (user_id, numeric_id, client_name, client_cel, client_address, client_rfc, client_email, vehicle_brand, vehicle_plates, vehicle_year, vehicle_km, vehicle_gas_level, observations, status, subtotal, iva, total, iva_applied, created_at, updated_at) 
                 VALUES (:user_id, :numeric_id, :client_name, :client_cel, :client_address, :client_rfc, :client_email, :vehicle_brand, :vehicle_plates, :vehicle_year, :vehicle_km, :vehicle_gas_level, :observations, :status, :subtotal, :iva, :total, :iva_applied, :created_at, :updated_at)"
            );
            $stmt->execute([
                'user_id' => USER_ID,
                'numeric_id' => $numeric_id,
                'client_name' => $client_name,
                'client_cel' => $client_cel,
                'client_address' => $client_address,
                'client_rfc' => $client_rfc,
                'client_email' => $client_email,
                'vehicle_brand' => $vehicle_brand,
                'vehicle_plates' => $vehicle_plates,
                'vehicle_year' => $vehicle_year,
                'vehicle_km' => $vehicle_km,
                'vehicle_gas_level' => $vehicle_gas_level,
                'observations' => $observations,
                'status' => $status,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'iva_applied' => $iva_applied,
                'created_at' => $created_at,
                'updated_at' => $created_at
            ]);
            $order_id = $db->lastInsertId();
            echo "-> Orden principal insertada con ID: $order_id.<br>";
            
            // 2. Parsear e insertar items
            $items = parse_items($items_str);
            if (!empty($items)) {
                $item_stmt = $db->prepare("INSERT INTO order_items (order_id, qty, description, price) VALUES (:order_id, :qty, :description, :price)");
                foreach ($items as $item) {
                    $item_stmt->execute([
                        'order_id' => $order_id,
                        'qty' => $item['qty'],
                        'description' => $item['description'],
                        'price' => $item['price']
                    ]);
                }
                 echo "-> Se insertaron " . count($items) . " conceptos.<br>";
            } else {
                 echo "-> No se encontraron conceptos para esta orden.<br>";
            }
        }
        
        fclose($file);
        $db->commit();
        echo "<hr><h2 style='color:green;'>¡MIGRACIÓN COMPLETADA EXITOSAMENTE!</h2>";
        echo "<p>Se procesaron $rowCount órdenes.</p>";
        echo "<p style='color:red; font-weight:bold;'>POR SEGURIDAD, AHORA DEBES ELIMINAR EL ARCHIVO 'migrate.php' Y EL ARCHIVO CSV DE TU SERVIDOR.</p>";

    } catch (Exception $e) {
        $db->rollBack();
        echo "<p style='color:red;'>ERROR DURANTE LA MIGRACIÓN: " . $e->getMessage() . "</p>";
        echo "<p>No se guardó ningún dato. Por favor, revisa el error y vuelve a intentarlo.</p>";
    }

    $output = ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistente de Migración de Datos</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        button { background-color: #28a745; color: white; padding: 15px 25px; border: none; border-radius: 5px; font-size: 1.2em; cursor: pointer; }
        button:hover { background-color: #218838; }
        .output { margin-top: 20px; padding: 15px; background-color: #333; color: #f4f4f4; border-radius: 5px; max-height: 400px; overflow-y: auto; font-family: monospace; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Asistente de Migración de Datos (CSV a MySQL)</h1>
        <?php if (empty($action)): ?>
            <p>Este script importará los datos de tu archivo <strong>historial_completo_ordenes_2025-09-28.csv</strong> a tu nueva base de datos MySQL.</p>
            <div class="warning">
                <strong>¡Importante!</strong>
                <ol>
                    <li>Asegúrate de haber subido el archivo CSV a la misma carpeta que este script.</li>
                    <li>Este proceso solo debe ejecutarse <strong>UNA VEZ</strong>. Si lo ejecutas varias veces, los datos se duplicarán.</li>
                    <li>Antes de empezar, edita la primera línea de este archivo (`migrate.php`) y asegúrate de que el `USER_ID` sea el correcto para tu cuenta.</li>
                </ol>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="migrate">
                <button type="submit">Iniciar Migración</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($output)): ?>
            <div class="output"><?php echo $output; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
