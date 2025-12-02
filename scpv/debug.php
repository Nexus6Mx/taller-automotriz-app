<?php
/**
 * Script de depuración - Probar edición de proveedor
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug - Edición de Proveedor</h1>";

// Configurar entorno
define('ROOT_PATH', __DIR__);
define('SRC_PATH', ROOT_PATH . '/src');
define('CONFIG_PATH', ROOT_PATH . '/config');

require_once CONFIG_PATH . '/config.php';

// Autoloader
spl_autoload_register(function($class) {
    $file = SRC_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

session_start();

echo "<h2>1. Probando Database</h2>";
try {
    $db = \Core\Database::getInstance();
    echo "✅ Database conectado<br>";
} catch (Exception $e) {
    echo "❌ Error Database: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>2. Probando Modelo Proveedor</h2>";
try {
    $proveedorModel = new \Models\Proveedor();
    echo "✅ Modelo Proveedor instanciado<br>";
    
    // Listar proveedores
    $proveedores = $proveedorModel->all();
    echo "✅ Total proveedores: " . count($proveedores) . "<br>";
    
    if (!empty($proveedores)) {
        $proveedor = $proveedores[0];
        echo "<h3>Primer proveedor:</h3>";
        echo "<pre>" . print_r($proveedor, true) . "</pre>";
        
        // Intentar encontrar por ID
        echo "<h2>3. Probando find()</h2>";
        $id = $proveedor['id'];
        $found = $proveedorModel->find($id);
        if ($found) {
            echo "✅ Proveedor encontrado con ID: $id<br>";
            echo "<pre>" . print_r($found, true) . "</pre>";
        } else {
            echo "❌ No se pudo encontrar proveedor con ID: $id<br>";
        }
        
        // Probar update
        echo "<h2>4. Probando update()</h2>";
        try {
            $testData = [
                'razon_social' => $found['razon_social'] . ' (test)',
                'telefono' => $found['telefono']
            ];
            echo "Datos a actualizar:<br>";
            echo "<pre>" . print_r($testData, true) . "</pre>";
            
            $result = $proveedorModel->update($id, $testData);
            echo "✅ Update exitoso - Resultado: " . ($result ? 'true' : 'false') . "<br>";
            
            // Revertir cambio
            $proveedorModel->update($id, ['razon_social' => $found['razon_social']]);
            
        } catch (Exception $e) {
            echo "❌ Error en update: " . $e->getMessage() . "<br>";
            echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "<br>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    } else {
        echo "⚠️ No hay proveedores en la BD<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>5. Probando ProveedorController</h2>";
try {
    // Simular sesión de usuario admin
    $_SESSION['user'] = [
        'id' => 1,
        'nombre' => 'Admin Test',
        'email' => 'admin@empresa.com',
        'rol' => 'admin'
    ];
    
    $controller = new \Controllers\ProveedorController();
    echo "✅ ProveedorController instanciado<br>";
    
    // Verificar que tiene los métodos
    $methods = ['index', 'create', 'store', 'edit', 'update', 'delete', 'show'];
    foreach ($methods as $method) {
        if (method_exists($controller, $method)) {
            echo "✅ Método $method existe<br>";
        } else {
            echo "❌ Método $method NO existe<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error en Controller: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
