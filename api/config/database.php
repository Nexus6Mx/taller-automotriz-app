<?php
// api/config/database.php

class Database {
    // --- Credenciales para el entorno de desarrollo Docker ---
    // El host 'db' es el nombre del servicio de la base de datos en docker-compose.yml
    private $host = "db";
    private $db_name = "u185421649_gestor_ordenes";
    private $username = "u185421649_gestor_user";
    private $password = "Chckcl74&";
    // ----------------------------------------------------

    private $conn;

    /**
     * Obtiene la conexión a la base de datos.
     * @return PDO|null La conexión PDO o null si falla.
     */
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            // Establece el modo de error de PDO a excepción para un mejor manejo de errores.
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Asegura que la comunicación sea en UTF-8 para soportar acentos y caracteres especiales.
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            // En un entorno de producción, sería mejor registrar este error que imprimirlo.
            echo "Error de conexión a la base de datos: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
