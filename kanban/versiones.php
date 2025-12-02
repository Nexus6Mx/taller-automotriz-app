<?php

// Revisa la version de PHP
echo "<h1>Versiones de PHP y MySQL</h1>";
echo "<h3>PHP:</h3>";
echo "Version de PHP: " . phpversion() . "<br>";
echo "<br>";

// Revisa la version de MySQL
echo "<h3>MySQL:</h3>";
$servidor = "localhost"; 
$usuario = "u185421649_user_kanban";
$contrasena = "Chckci74&";
$basedatos = "u185421649_kanban";

// Crea una nueva conexion
$conexion = new mysqli($servidor, $usuario, $contrasena, $basedatos);

// Verifica la conexion
if ($conexion->connect_error) {
    die("Error de conexion a MySQL: " . $conexion->connect_error);
}

// Muestra la version del servidor MySQL
echo "Version de MySQL: " . $conexion->server_info . "<br>";

// Cierra la conexion
$conexion->close();

?>