<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Cargar configuración si no está cargada
        if (!defined('DB_HOST')) {
            require_once __DIR__ . '/config.php';
        }
        
        $this->host     = DB_HOST;
        $this->port     = defined('DB_PORT') ? DB_PORT : '3306';
        $this->db_name  = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }

    public function getConnection() {
        $this->conn = null;

        try {
            // Detectar socket Unix de MAMP Pro para evitar problemas de puerto en desarrollo
            $mampSocket = '/Applications/MAMP/tmp/mysql/mysql.sock';
            if (file_exists($mampSocket)) {
                $dsn = "mysql:unix_socket={$mampSocket};dbname={$this->db_name};charset=utf8mb4";
            } else {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            }

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password
            );
            $this->conn->exec("SET NAMES utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                error_log("Database::getConnection() - Error de conexión: " . $e->getMessage());
                echo "Error de conexión: " . $e->getMessage();
            } else {
                error_log("Database connection failed: " . $e->getMessage());
                // En producción, no mostrar detalles del error
                echo "Error de conexión a la base de datos.";
            }
        }

        return $this->conn;
    }
}

