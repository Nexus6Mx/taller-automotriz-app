<?php
// api/config/database.example.php
// Copia este archivo a 'database.php' y rellena las credenciales locales.

class Database {
    private $host = "db";
    private $db_name = "u185421649_gestor_ordenes";
    private $username = "your_db_user";
    private $password = "your_db_password";

    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            // No exponer detalles en producción
            error_log("Error de conexión a la base de datos: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>