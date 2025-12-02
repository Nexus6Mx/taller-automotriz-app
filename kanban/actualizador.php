<?php
echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Actualizador de Acciones</title>";
echo "<style>body { font-family: monospace; background: #111; color: #0f0; } h1 { color: #fff; } .file { border-left: 3px solid #0f0; padding-left: 10px; margin-bottom: 5px; } .error { border-left-color: #f00; color: #f00; }</style></head><body>";
echo "<h1>Iniciando actualización de archivos de acción...</h1>";

$actions_dir = __DIR__ . '/actions/';
$find_string = "\$data['current_user_id'] ?? null";
$replace_string = "\$_SESSION['user_id']";
$files_updated = 0;

if (!is_dir($actions_dir)) {
    echo "<div class='error'>Error: El directorio 'actions' no se encuentra.</div>";
    exit;
}

$files = scandir($actions_dir);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $file_path = $actions_dir . $file;
        $content = file_get_contents($file_path);

        if (strpos($content, $find_string) !== false) {
            $new_content = str_replace($find_string, $replace_string, $content);
            file_put_contents($file_path, $new_content);
            echo "<div class='file'>[OK] Archivo actualizado: <strong>{$file}</strong></div>";
            $files_updated++;
        } else {
             echo "<div class='file'>[--] Archivo omitido (no requiere cambios): {$file}</div>";
        }
    }
}

echo "<h1>Proceso finalizado. Se actualizaron {$files_updated} archivos.</h1>";
echo "<h2 style='color: #f00; font-weight: bold;'>¡ACCIÓN REQUERIDA! BORRA ESTE ARCHIVO (actualizador.php) DE TU SERVIDOR AHORA MISMO.</h2>";
echo "</body></html>";
?>