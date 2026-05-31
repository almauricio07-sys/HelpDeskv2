<?php
class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct() {
        $host = 'localhost';
        $db   = 'helpdesk_db'; // Verifica que tu base de datos se llame así
        $user = 'root';
        $pass = ''; 

        try {
            $this->connection = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }
}