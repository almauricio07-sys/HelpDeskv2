<?php
/**
 * Clase de conexión a la base de datos usando PDO.
 * Patrón Singleton para mantener una sola instancia de conexión.
 * 
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    // Configuración de la base de datos
    private string $host     = 'localhost';
    private string $dbName   = 'helpdesk';
    private string $username = 'root';
    private string $password = '';
    private string $charset  = 'utf8mb4';

    /**
     * Constructor privado para evitar instanciación directa.
     */
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // En producción, no exponer detalles del error
            error_log('Error de conexión a BD: ' . $e->getMessage());
            die(json_encode(['error' => 'Error de conexión a la base de datos. Contacte al administrador.']));
        }
    }

    /**
     * Retorna la instancia única de la clase (Singleton).
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna el objeto PDO para realizar consultas.
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    // Prevenir clonación y deserialización
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("No se puede deserializar un Singleton.");
    }
}
