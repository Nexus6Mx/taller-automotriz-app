<?php
/**
 * Clase para manejar la conexión a la base de datos
 */

namespace Core;

class Database
{
    private static $instance = null;
    private $connection;
    
    private function __construct()
    {
        $this->connect();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function connect()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_PERSISTENT => true
            ];
            
            $this->connection = new \PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (\PDOException $e) {
            error_log('Error de conexión a BD: ' . $e->getMessage());
            throw new \Exception('Error de conexión a la base de datos');
        }
    }
    
    public function getConnection()
    {
        // Verificar que la conexión sigue activa
        try {
            $this->connection->query('SELECT 1');
        } catch (\PDOException $e) {
            $this->connect();
        }
        
        return $this->connection;
    }
    
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            error_log('Error en consulta: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new \Exception('Error en la consulta a la base de datos');
        }
    }
    
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function execute($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }
    
    public function commit()
    {
        return $this->connection->commit();
    }
    
    public function rollback()
    {
        return $this->connection->rollback();
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup()
    {
        throw new \Exception("No se puede deserializar la conexión a BD");
    }
}