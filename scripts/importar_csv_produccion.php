<?php
/**
 * Script para importar datos de producción desde CSV a base de datos local
 * Uso: php importar_csv_produccion.php
 */

require_once __DIR__ . '/../api/config/database.php';

// Configuración
$csvFile = __DIR__ . '/../historial_completo_ordenes_2025-11-18.csv';
$db = new Database();
$conn = $db->getConnection();

// Contadores
$stats = [
    'clientes_nuevos' => 0,
    'clientes_actualizados' => 0,
    'vehiculos_nuevos' => 0,
    'vehiculos_actualizados' => 0,
    'ordenes_creadas' => 0,
    'ordenes_actualizadas' => 0,
    'items_creados' => 0,
    'errores' => 0,
    'duplicados_ignorados' => 0
];

echo "\n=== IMPORTACIÓN DE DATOS DE PRODUCCIÓN ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "Archivo: $csvFile\n\n";

if (!file_exists($csvFile)) {
    die("ERROR: No se encuentra el archivo CSV\n");
}

// Función para convertir fecha DD/MM/YYYY a YYYY-MM-DD
function convertirFecha($fecha) {
    if (empty($fecha)) return date('Y-m-d');
    $partes = explode('/', $fecha);
    if (count($partes) == 3) {
        return sprintf('%04d-%02d-%02d', $partes[2], $partes[1], $partes[0]);
    }
    return date('Y-m-d');
}

// Función para limpiar texto
function limpiarTexto($texto) {
    return trim($texto);
}

// Función para obtener o crear cliente
function obtenerOCrearCliente($conn, $nombre, $telefono, $direccion, $rfc, $email, &$stats) {
    $nombre = limpiarTexto($nombre);
    if (empty($nombre)) return null;
    
    // Buscar cliente existente por nombre
    $query = "SELECT id FROM clients WHERE LOWER(name) = LOWER(?) LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$nombre]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Cliente existe, actualizarlo si tiene más información
        $clienteId = $result['id'];
        
        $updateFields = [];
        $updateValues = [];
        
        if (!empty($telefono)) {
            $updateFields[] = "cel = ?";
            $updateValues[] = limpiarTexto($telefono);
        }
        if (!empty($direccion)) {
            $updateFields[] = "address = ?";
            $updateValues[] = limpiarTexto($direccion);
        }
        if (!empty($rfc)) {
            $updateFields[] = "rfc = ?";
            $updateValues[] = limpiarTexto($rfc);
        }
        if (!empty($email)) {
            $updateFields[] = "email = ?";
            $updateValues[] = limpiarTexto($email);
        }
        
        if (!empty($updateFields)) {
            $updateValues[] = $clienteId;
            $updateQuery = "UPDATE clients SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute($updateValues);
            $stats['clientes_actualizados']++;
        }
        
        return $clienteId;
    }
    
    // Crear nuevo cliente - necesita user_id y numeric_id
    // Obtener el siguiente numeric_id para clientes
    $query = "SELECT COALESCE(MAX(numeric_id), 0) + 1 as next_id FROM clients";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $nextNumericId = $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];
    
    $query = "INSERT INTO clients (user_id, numeric_id, name, cel, address, rfc, email, created_at) 
              VALUES (1, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $nextNumericId,
        $nombre,
        limpiarTexto($telefono),
        limpiarTexto($direccion),
        limpiarTexto($rfc),
        limpiarTexto($email)
    ]);
    
    $stats['clientes_nuevos']++;
    return $conn->lastInsertId();
}

// Función para obtener o crear vehículo
function obtenerOCrearVehiculo($conn, $clienteId, $marcaModelo, $placas, $anio, $kilometraje, $nivelGasolina, &$stats) {
    if (empty($clienteId)) return null;
    
    $placas = limpiarTexto($placas);
    
    // Buscar vehículo por placas si existen
    if (!empty($placas) && $placas != 'SIN PLACAS' && $placas != 'NO' && $placas != '00000') {
        $query = "SELECT id FROM vehicles WHERE LOWER(plates) = LOWER(?) LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute([$placas]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $vehiculoId = $result['id'];
            
            // Actualizar información del vehículo
            $updateFields = [];
            $updateValues = [];
            
            if (!empty($marcaModelo)) {
                $updateFields[] = "brand = ?";
                $updateValues[] = limpiarTexto($marcaModelo);
            }
            if (!empty($anio) && $anio != '0') {
                $updateFields[] = "year = ?";
                $updateValues[] = intval($anio);
            }
            if (!empty($kilometraje) && $kilometraje != '0') {
                $updateFields[] = "km = ?";
                $updateValues[] = intval($kilometraje);
            }
            if (!empty($nivelGasolina)) {
                $updateFields[] = "gas_level = ?";
                $updateValues[] = limpiarTexto($nivelGasolina);
            }
            
            if (!empty($updateFields)) {
                $updateValues[] = $vehiculoId;
                $updateQuery = "UPDATE vehicles SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->execute($updateValues);
                $stats['vehiculos_actualizados']++;
            }
            
            return $vehiculoId;
        }
    }
    
    // Obtener nombre del cliente para el vehículo
    $queryCliente = "SELECT name FROM clients WHERE id = ?";
    $stmtCliente = $conn->prepare($queryCliente);
    $stmtCliente->execute([$clienteId]);
    $clienteNombre = $stmtCliente->fetch(PDO::FETCH_ASSOC)['name'];
    
    // Obtener el siguiente numeric_id para vehículos
    $query = "SELECT COALESCE(MAX(numeric_id), 0) + 1 as next_id FROM vehicles";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $nextNumericId = $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];
    
    // Crear nuevo vehículo
    $query = "INSERT INTO vehicles (user_id, numeric_id, client_id, client_name, brand, plates, year, km, gas_level, created_at) 
              VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $nextNumericId,
        $clienteId,
        $clienteNombre,
        limpiarTexto($marcaModelo),
        $placas,
        !empty($anio) && $anio != '0' ? intval($anio) : null,
        !empty($kilometraje) && $kilometraje != '0' ? intval($kilometraje) : null,
        limpiarTexto($nivelGasolina)
    ]);
    
    $stats['vehiculos_nuevos']++;
    return $conn->lastInsertId();
}

// Función para parsear items del CSV
function parsearItems($itemsStr) {
    $items = [];
    if (empty($itemsStr)) return $items;
    
    // Los items están separados por punto y coma
    $itemsList = explode(';', $itemsStr);
    
    foreach ($itemsList as $item) {
        $item = trim($item);
        if (empty($item)) continue;
        
        // Formato: "1x Nombre del item ($precio)"
        if (preg_match('/^(\d+)x\s+(.+?)\s+\(\$?([0-9.]+)\)$/', $item, $matches)) {
            $items[] = [
                'quantity' => intval($matches[1]),
                'description' => trim($matches[2]),
                'unit_price' => floatval($matches[3])
            ];
        }
    }
    
    return $items;
}

// Iniciar transacción
$conn->beginTransaction();

try {
    // Leer CSV
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        throw new Exception("No se puede abrir el archivo CSV");
    }
    
    // Saltar encabezado
    $header = fgetcsv($handle);
    
    $lineNum = 1;
    while (($data = fgetcsv($handle)) !== false) {
        $lineNum++;
        
        try {
            // Extraer datos del CSV
            $numericId = intval($data[0]);
            $fechaCreacion = convertirFecha($data[1]);
            $estado = limpiarTexto($data[2]);
            $clienteNombre = limpiarTexto($data[3]);
            $clienteTelefono = limpiarTexto($data[4]);
            $clienteDireccion = limpiarTexto($data[5]);
            $clienteRFC = limpiarTexto($data[6]);
            $clienteEmail = limpiarTexto($data[7]);
            $vehiculoMarcaModelo = limpiarTexto($data[8]);
            $vehiculoPlacas = limpiarTexto($data[9]);
            $vehiculoAnio = $data[10];
            $vehiculoKilometraje = $data[11];
            $vehiculoNivelGasolina = limpiarTexto($data[12]);
            $itemsStr = $data[13];
            $subtotal = floatval($data[14]);
            $iva = floatval($data[15]);
            $total = floatval($data[16]);
            $observaciones = limpiarTexto($data[17]);
            
            // Validar datos mínimos
            if (empty($clienteNombre) || $numericId == 0) {
                echo "⚠ Línea $lineNum: Datos insuficientes (cliente vacío o ID 0), ignorando...\n";
                $stats['duplicados_ignorados']++;
                continue;
            }
            
            // Verificar si la orden ya existe
            $checkQuery = "SELECT id FROM orders WHERE numeric_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->execute([$numericId]);
            
            if ($checkStmt->fetch()) {
                echo "⚠ Línea $lineNum: Orden #$numericId ya existe, ignorando...\n";
                $stats['duplicados_ignorados']++;
                continue;
            }
            
            // Obtener o crear cliente
            $clienteId = obtenerOCrearCliente($conn, $clienteNombre, $clienteTelefono, $clienteDireccion, $clienteRFC, $clienteEmail, $stats);
            
            if (!$clienteId) {
                echo "⚠ Línea $lineNum: No se pudo crear/encontrar cliente, ignorando...\n";
                $stats['errores']++;
                continue;
            }
            
            // Obtener o crear vehículo
            $vehiculoId = obtenerOCrearVehiculo($conn, $clienteId, $vehiculoMarcaModelo, $vehiculoPlacas, $vehiculoAnio, $vehiculoKilometraje, $vehiculoNivelGasolina, $stats);
            
            // Crear orden con esquema desnormalizado
            // Obtener datos del cliente
            $queryCliente = "SELECT name, cel, address, rfc, email FROM clients WHERE id = ?";
            $stmtCliente = $conn->prepare($queryCliente);
            $stmtCliente->execute([$clienteId]);
            $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);
            
            // Obtener datos del vehículo si existe
            $vehiculoBrand = '';
            $vehiculoPlates = '';
            $vehiculoYear = null;
            $vehiculoKm = null;
            $vehiculoGasLevel = '';
            
            if ($vehiculoId) {
                $queryVehiculo = "SELECT brand, plates, year, km, gas_level FROM vehicles WHERE id = ?";
                $stmtVehiculo = $conn->prepare($queryVehiculo);
                $stmtVehiculo->execute([$vehiculoId]);
                $vehiculo = $stmtVehiculo->fetch(PDO::FETCH_ASSOC);
                
                if ($vehiculo) {
                    $vehiculoBrand = $vehiculo['brand'];
                    $vehiculoPlates = $vehiculo['plates'];
                    $vehiculoYear = $vehiculo['year'];
                    $vehiculoKm = $vehiculo['km'];
                    $vehiculoGasLevel = $vehiculo['gas_level'];
                }
            }
            
            $queryOrder = "INSERT INTO orders (user_id, numeric_id, client_name, client_cel, client_address, client_rfc, client_email, 
                                              vehicle_brand, vehicle_plates, vehicle_year, vehicle_km, vehicle_gas_level,
                                              status, observations, subtotal, iva, total, created_at, updated_at) 
                          VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmtOrder = $conn->prepare($queryOrder);
            $stmtOrder->execute([
                $numericId,
                $cliente['name'],
                $cliente['cel'],
                $cliente['address'],
                $cliente['rfc'],
                $cliente['email'],
                $vehiculoBrand,
                $vehiculoPlates,
                $vehiculoYear,
                $vehiculoKm,
                $vehiculoGasLevel,
                $estado,
                $observaciones,
                $subtotal,
                $iva,
                $total,
                $fechaCreacion
            ]);
            
            $ordenId = $conn->lastInsertId();
            $stats['ordenes_creadas']++;
            
            // Parsear y crear items
            $items = parsearItems($itemsStr);
            
            if (!empty($items)) {
                $queryItem = "INSERT INTO order_items (order_id, description, qty, price) 
                             VALUES (?, ?, ?, ?)";
                $stmtItem = $conn->prepare($queryItem);
                
                foreach ($items as $item) {
                    $stmtItem->execute([
                        $ordenId,
                        $item['description'],
                        $item['quantity'],
                        $item['unit_price']
                    ]);
                    $stats['items_creados']++;
                }
            }
            
            echo "✓ Orden #$numericId importada correctamente\n";
            
        } catch (Exception $e) {
            echo "✗ Línea $lineNum: Error - " . $e->getMessage() . "\n";
            $stats['errores']++;
        }
    }
    
    fclose($handle);
    
    // Confirmar transacción
    $conn->commit();
    
    echo "\n=== RESUMEN DE IMPORTACIÓN ===\n";
    echo "Clientes nuevos: " . $stats['clientes_nuevos'] . "\n";
    echo "Clientes actualizados: " . $stats['clientes_actualizados'] . "\n";
    echo "Vehículos nuevos: " . $stats['vehiculos_nuevos'] . "\n";
    echo "Vehículos actualizados: " . $stats['vehiculos_actualizados'] . "\n";
    echo "Órdenes creadas: " . $stats['ordenes_creadas'] . "\n";
    echo "Items creados: " . $stats['items_creados'] . "\n";
    echo "Duplicados ignorados: " . $stats['duplicados_ignorados'] . "\n";
    echo "Errores: " . $stats['errores'] . "\n";
    echo "\n✓ IMPORTACIÓN COMPLETADA EXITOSAMENTE\n\n";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "\n✗ ERROR FATAL: " . $e->getMessage() . "\n";
    echo "La transacción ha sido revertida.\n\n";
    exit(1);
}
