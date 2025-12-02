<?php
header('Content-Type: text/html; charset=utf-8');

// Configuración
$production_path = __DIR__; // Ruta de la aplicación en producción
$repo_url = "https://api.github.com/repos/Nexus6Mx/taller-automotriz-app/contents/";
$branch = "master";
// Carpetas a ignorar
$ignore_folders = array('.git', 'fpdf186', 'kanban', 'old');

// Función para obtener el contenido de GitHub
function getGithubContent($path) {
    global $repo_url, $branch;
    $url = $repo_url . $path;
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: PHP'
            ]
        ]
    ];
    $context = stream_context_create($opts);
    $content = @file_get_contents($url, false, $context);
    if ($content === false) {
        return null;
    }
    return json_decode($content, true);
}

// Función para comparar archivos
function compareFiles($local_path, $github_content) {
    if (!file_exists($local_path)) {
        return [
            'status' => 'missing_local',
            'message' => 'Archivo no existe en local'
        ];
    }

    $local_hash = hash_file('sha1', $local_path);
    $github_hash = $github_content['sha'];
    
    if ($local_hash !== $github_hash) {
        return [
            'status' => 'different',
            'message' => 'Contenido diferente'
        ];
    }

    return [
        'status' => 'same',
        'message' => 'Archivos idénticos'
    ];
}

// Función para escanear directorio recursivamente
function scanDirectory($dir) {
    global $ignore_folders;
    $result = [];
    $files = scandir($dir);
    foreach ($files as $file) {
        // Ignorar directorios especiales y carpetas en la lista de ignorados
        if ($file === '.' || $file === '..' || in_array($file, $ignore_folders)) continue;
        
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            $result = array_merge($result, scanDirectory($path));
        } else {
            $result[] = str_replace($GLOBALS['production_path'] . '/', '', $path);
        }
    }
    return $result;
}

// Estilos CSS para la salida
echo '<!DOCTYPE html>
<html>
<head>
    <title>Comparación de Archivos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .file { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .different { background-color: #ffe6e6; }
        .missing_local { background-color: #fff3e6; }
        .same { background-color: #e6ffe6; }
        .status { font-weight: bold; }
    </style>
</head>
<body>
<h1>Comparación de Archivos con GitHub</h1>';

// Obtener lista de archivos locales
$local_files = scanDirectory($production_path);

// Comparar cada archivo
foreach ($local_files as $file) {
    if ($file === 'compare_files.php') continue; // Ignorar este script
    
    echo "<div class='file'>";
    echo "<h3>$file</h3>";
    
    $github_content = getGithubContent($file);
    if ($github_content === null) {
        echo "<p class='status' style='color: #ff6b6b;'>No encontrado en GitHub</p>";
        continue;
    }
    
    $comparison = compareFiles($production_path . '/' . $file, $github_content);
    echo "<p class='status {$comparison['status']}'>{$comparison['message']}</p>";
    echo "</div>";
}

echo '</body></html>';
?>