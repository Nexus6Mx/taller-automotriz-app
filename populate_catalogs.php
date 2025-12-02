<?php
// --- CONFIGURACIÓN ---
// Asegúrate de que este ID sea el de tu usuario en la tabla `users` de la base de datos.
// Si eres el único usuario, casi siempre es 1.
const USER_ID = 1; 
// -------------------

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); 

include_once 'api/config/database.php';

$action = $_POST['action'] ?? '';
$output = '';

if ($action === 'populate') {
    ob_start();
    
    echo "<h1>Poblando Catálogos desde el Historial de Órdenes...</h1>";

    $database = new Database();
    $db = $database->getConnection();

    $db->beginTransaction();
    try {
        // --- 1. POBLAR CATÁLOGO DE CLIENTES ---
        echo "<hr><strong>Procesando Clientes...</strong><br>";
        $stmt = $db->prepare("SELECT DISTINCT client_name, client_cel, client_address, client_rfc, client_email FROM orders WHERE user_id = :user_id");
        $stmt->execute(['user_id' => USER_ID]);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $client_count = 0;

        foreach ($clients as $client) {
            $client_name = trim($client['client_name']);
            if (empty($client_name)) continue;

            $check_stmt = $db->prepare("SELECT id FROM clients WHERE user_id = :user_id AND name = :name");
            $check_stmt->execute(['user_id' => USER_ID, 'name' => $client_name]);

            if ($check_stmt->fetch() === false) {
                // El cliente no existe, lo insertamos
                $id_stmt = $db->prepare("SELECT IFNULL(MAX(numeric_id), 0) + 1 FROM clients WHERE user_id = :user_id");
                $id_stmt->execute(['user_id' => USER_ID]);
                $numeric_id = $id_stmt->fetchColumn();

                $insert_stmt = $db->prepare(
                    "INSERT INTO clients (user_id, numeric_id, name, cel, address, rfc, email, created_at)
                     VALUES (:user_id, :numeric_id, :name, :cel, :address, :rfc, :email, NOW())"
                );
                $insert_stmt->execute([
                    'user_id' => USER_ID, 'numeric_id' => $numeric_id, 'name' => $client_name,
                    'cel' => $client['client_cel'], 'address' => $client['client_address'],
                    'rfc' => $client['client_rfc'], 'email' => $client['client_email']
                ]);
                $client_count++;
                echo "-> Cliente nuevo agregado: '$client_name'<br>";
            }
        }
        echo "<strong>Se agregaron $client_count clientes nuevos.</strong><br>";

        // --- 2. POBLAR CATÁLOGO DE VEHÍCULOS ---
        echo "<hr><strong>Procesando Vehículos...</strong><br>";
        $stmt = $db->prepare("SELECT DISTINCT vehicle_plates, vehicle_brand, vehicle_year, client_name FROM orders WHERE user_id = :user_id AND vehicle_plates IS NOT NULL AND vehicle_plates != ''");
        $stmt->execute(['user_id' => USER_ID]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $vehicle_count = 0;

        foreach ($vehicles as $vehicle) {
            $vehicle_plates = trim($vehicle['vehicle_plates']);
            $client_name = trim($vehicle['client_name']);

            $check_stmt = $db->prepare("SELECT id FROM vehicles WHERE user_id = :user_id AND plates = :plates");
            $check_stmt->execute(['user_id' => USER_ID, 'plates' => $vehicle_plates]);

            if ($check_stmt->fetch() === false) {
                // El vehículo no existe, lo insertamos
                $client_id_stmt = $db->prepare("SELECT id FROM clients WHERE user_id = :user_id AND name = :name");
                $client_id_stmt->execute(['user_id' => USER_ID, 'name' => $client_name]);
                $client_id = $client_id_stmt->fetchColumn();

                if($client_id) {
                    $id_stmt = $db->prepare("SELECT IFNULL(MAX(numeric_id), 0) + 1 FROM vehicles WHERE user_id = :user_id");
                    $id_stmt->execute(['user_id' => USER_ID]);
                    $numeric_id = $id_stmt->fetchColumn();

                    $insert_stmt = $db->prepare(
                        "INSERT INTO vehicles (user_id, numeric_id, client_id, client_name, brand, plates, year, created_at)
                         VALUES (:user_id, :numeric_id, :client_id, :client_name, :brand, :plates, :year, NOW())"
                    );
                    $insert_stmt->execute([
                        'user_id' => USER_ID, 'numeric_id' => $numeric_id, 'client_id' => $client_id,
                        'client_name' => $client_name, 'brand' => $vehicle['vehicle_brand'],
                        'plates' => $vehicle_plates, 'year' => $vehicle['vehicle_year']
                    ]);
                    $vehicle_count++;
                    echo "-> Vehículo nuevo agregado: '$vehicle_plates' para '$client_name'<br>";
                }
            }
        }
        echo "<strong>Se agregaron $vehicle_count vehículos nuevos.</strong><br>";
        
        // --- 3. POBLAR CATÁLOGO DE INSUMOS ---
        echo "<hr><strong>Procesando Insumos/Servicios...</strong><br>";
        $stmt = $db->prepare("SELECT DISTINCT description, price FROM order_items");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $supply_count = 0;

        foreach ($items as $item) {
            $description = trim($item['description']);
            if(empty($description)) continue;

            $check_stmt = $db->prepare("SELECT id FROM supplies WHERE user_id = :user_id AND description = :description");
            $check_stmt->execute(['user_id' => USER_ID, 'description' => $description]);

            if ($check_stmt->fetch() === false) {
                $id_stmt = $db->prepare("SELECT IFNULL(MAX(numeric_id), 0) + 1 FROM supplies WHERE user_id = :user_id");
                $id_stmt->execute(['user_id' => USER_ID]);
                $numeric_id = $id_stmt->fetchColumn();

                $insert_stmt = $db->prepare(
                    "INSERT INTO supplies (user_id, numeric_id, description, price, created_at)
                     VALUES (:user_id, :numeric_id, :description, :price, NOW())"
                );
                $insert_stmt->execute([
                    'user_id' => USER_ID, 'numeric_id' => $numeric_id, 
                    'description' => $description, 'price' => $item['price']
                ]);
                $supply_count++;
                echo "-> Insumo nuevo agregado: '$description'<br>";
            }
        }
        echo "<strong>Se agregaron $supply_count insumos/servicios nuevos.</strong><br>";

        $db->commit();
        echo "<hr><h2 style='color:green;'>¡PROCESO COMPLETADO!</h2>";
        echo "<p>Tus catálogos han sido poblados con la información de tu historial.</p>";
        echo "<p style='color:red; font-weight:bold;'>POR SEGURIDAD, AHORA DEBES ELIMINAR ESTE ARCHIVO ('populate_catalogs.php') DE TU SERVIDOR.</p>";

    } catch (Exception $e) {
        $db->rollBack();
        echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
    }

    $output = ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistente para Poblar Catálogos</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        button { background-color: #007bff; color: white; padding: 15px 25px; border: none; border-radius: 5px; font-size: 1.2em; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .output { margin-top: 20px; padding: 15px; background-color: #333; color: #f4f4f4; border-radius: 5px; max-height: 400px; overflow-y: auto; font-family: monospace; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Asistente para Poblar Catálogos</h1>
        <?php if (empty($action)): ?>
            <p>Este script leerá tu historial de órdenes y creará las entradas correspondientes en los catálogos de <strong>Clientes, Vehículos e Insumos</strong>.</p>
            <div class="warning">
                <strong>¡Atención!</strong> Este proceso solo debe ejecutarse <strong>UNA VEZ</strong> para llenar los catálogos con tus datos históricos.
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="populate">
                <button type="submit">Poblar Catálogos Ahora</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($output)): ?>
            <div class="output"><?php echo $output; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
