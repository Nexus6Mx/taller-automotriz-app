<?php
/**
 * Script para eliminar órdenes duplicadas en producción
 * 
 * USO:
 * 1. Sube este archivo a la carpeta /scripts/ en tu servidor
 * 2. Visita: https://tu-dominio.com/scripts/eliminar_ordenes_duplicadas.php
 * 3. El script mostrará las órdenes a eliminar y pedirá confirmación
 * 
 * IMPORTANTE: Este script hace un backup automático antes de eliminar
 */

// Configuración de la base de datos de producción
// MODIFICA ESTOS VALORES CON TUS CREDENCIALES DE PRODUCCIÓN
$host = "localhost";  // o la IP de tu servidor MySQL
$db_name = "u185421649_gestor_ordenes";
$username = "u185421649_gestor_user";
$password = "Chckcl74&";

// Conectar a la base de datos
try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: No se pudo conectar a la base de datos: " . $e->getMessage());
}

// HTML header
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Órdenes Duplicadas</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #e74c3c; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .steps { background: #e3f2fd; padding: 20px; border-radius: 4px; margin: 20px 0; }
        .step { margin: 10px 0; padding-left: 30px; position: relative; }
        .step:before { content: "✓"; position: absolute; left: 0; color: #28a745; font-weight: bold; font-size: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ Eliminar Órdenes Duplicadas</h1>
        
<?php

// Definir las órdenes a eliminar
$ordenes_duplicadas = [
    ['numeric_id' => 10000, 'client_name' => 'Guadalupe', 'fecha' => '2025-11-07'],
    ['numeric_id' => 10000, 'client_name' => 'Protector Intercontinental', 'fecha' => '2025-11-12'],
    ['numeric_id' => 10001, 'client_name' => 'Estacionamientos Corsa ERIKA', 'fecha' => null],
    ['numeric_id' => 10002, 'client_name' => 'Estacionamientos Corsa ERIKA', 'fecha' => null],
    ['numeric_id' => 10003, 'client_name' => 'Protector Intercontinental', 'fecha' => null],
    ['numeric_id' => 10004, 'client_name' => 'Protector Intercontinental', 'fecha' => null],
];

// Construir la condición WHERE
$where_conditions = [];
foreach ($ordenes_duplicadas as $orden) {
    if ($orden['fecha']) {
        $where_conditions[] = "(o.numeric_id = {$orden['numeric_id']} AND o.client_name = '{$orden['client_name']}' AND DATE(o.created_at) = '{$orden['fecha']}')";
    } else {
        $where_conditions[] = "(o.numeric_id = {$orden['numeric_id']} AND o.client_name = '{$orden['client_name']}')";
    }
}
$where_clause = implode(" OR ", $where_conditions);

// Verificar si se solicitó la eliminación
$action = $_GET['action'] ?? 'preview';

if ($action === 'preview') {
    // PASO 1: Mostrar las órdenes que se eliminarán
    echo '<div class="warning">';
    echo '<strong>⚠️ ADVERTENCIA:</strong> Estás a punto de eliminar órdenes de la base de datos de PRODUCCIÓN.<br>';
    echo 'Revisa cuidadosamente la lista antes de continuar.';
    echo '</div>';
    
    echo '<h2>📋 Órdenes que se eliminarán:</h2>';
    
    $query = "SELECT 
        o.id,
        o.numeric_id,
        o.created_at,
        o.client_name,
        o.total,
        o.status,
        COUNT(oi.id) as num_items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE $where_clause
    GROUP BY o.id
    ORDER BY o.numeric_id, o.created_at";
    
    $stmt = $conn->query($query);
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($ordenes) > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>No. Orden</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Items</th></tr>';
        foreach ($ordenes as $orden) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($orden['id']) . '</td>';
            echo '<td><strong>' . htmlspecialchars($orden['numeric_id']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($orden['created_at']) . '</td>';
            echo '<td>' . htmlspecialchars($orden['client_name']) . '</td>';
            echo '<td>$' . number_format($orden['total'], 2) . '</td>';
            echo '<td>' . htmlspecialchars($orden['status']) . '</td>';
            echo '<td>' . htmlspecialchars($orden['num_items']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        echo '<p><strong>Total de órdenes a eliminar: ' . count($ordenes) . '</strong></p>';
        
        echo '<div class="steps">';
        echo '<h3>📝 Al hacer clic en "Eliminar", el script:</h3>';
        echo '<div class="step">Creará un backup automático de las órdenes</div>';
        echo '<div class="step">Eliminará los items de las órdenes</div>';
        echo '<div class="step">Eliminará las órdenes</div>';
        echo '<div class="step">Mostrará un resumen de la operación</div>';
        echo '</div>';
        
        echo '<a href="?action=delete" class="btn btn-danger" onclick="return confirm(\'¿Estás SEGURO de que deseas eliminar estas ' . count($ordenes) . ' órdenes?\\n\\nEsta acción NO se puede deshacer.\')">🗑️ Sí, Eliminar Órdenes</a>';
        echo '<a href="?" class="btn btn-secondary">↻ Recargar</a>';
    } else {
        echo '<div class="success">';
        echo '✅ No se encontraron órdenes para eliminar. Es posible que ya hayan sido eliminadas.';
        echo '</div>';
    }
    
} elseif ($action === 'delete') {
    // PASO 2: Eliminar las órdenes
    echo '<h2>🔄 Procesando eliminación...</h2>';
    
    try {
        // Iniciar transacción
        $conn->beginTransaction();
        
        // 1. Crear backup
        echo '<div class="step">Creando backup...</div>';
        $backup_dir = __DIR__ . '/backups';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        $backup_file = $backup_dir . '/ordenes_eliminadas_' . date('Y-m-d_His') . '.json';
        
        $query = "SELECT o.*, 
            (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', oi.id, 'qty', oi.qty, 'description', oi.description, 'price', oi.price))
             FROM order_items oi WHERE oi.order_id = o.id) as items
        FROM orders o
        WHERE $where_clause";
        
        $stmt = $conn->query($query);
        $backup_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo '<div class="success">✅ Backup creado: ' . basename($backup_file) . '</div>';
        
        // 2. Eliminar items
        echo '<div class="step">Eliminando items de las órdenes...</div>';
        $query_delete_items = "DELETE oi FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE $where_clause";
        
        $stmt = $conn->prepare($query_delete_items);
        $stmt->execute();
        $items_deleted = $stmt->rowCount();
        
        echo '<div class="success">✅ ' . $items_deleted . ' items eliminados</div>';
        
        // 3. Eliminar órdenes
        echo '<div class="step">Eliminando órdenes...</div>';
        $query_delete_orders = "DELETE FROM orders WHERE " . str_replace('o.', '', $where_clause);
        
        $stmt = $conn->prepare($query_delete_orders);
        $stmt->execute();
        $orders_deleted = $stmt->rowCount();
        
        echo '<div class="success">✅ ' . $orders_deleted . ' órdenes eliminadas</div>';
        
        // Commit
        $conn->commit();
        
        echo '<div class="success">';
        echo '<h3>✅ ¡Eliminación completada con éxito!</h3>';
        echo '<p><strong>Resumen:</strong></p>';
        echo '<ul>';
        echo '<li>Órdenes eliminadas: ' . $orders_deleted . '</li>';
        echo '<li>Items eliminados: ' . $items_deleted . '</li>';
        echo '<li>Backup guardado en: ' . $backup_file . '</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '<a href="?" class="btn btn-secondary">← Volver</a>';
        
    } catch (Exception $e) {
        // Rollback en caso de error
        $conn->rollBack();
        
        echo '<div class="error">';
        echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
        echo '<a href="?" class="btn btn-secondary">← Volver</a>';
    }
}

?>
    </div>
</body>
</html>
<?php
$conn = null;
?>
