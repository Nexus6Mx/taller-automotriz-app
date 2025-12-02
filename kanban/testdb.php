<?php
// testdb.php - borrar después de usar
$servername = 'localhost'; // coincide con la cuenta en phpMyAdmin
$username   = 'u185421649_user_kanban';
$password   = 'Chckcl74!';
$dbname     = 'u185421649_kanban';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    echo 'Conexión OK. MySQL server: ' . $conn->server_info;
    $conn->close();
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage();
}
?>