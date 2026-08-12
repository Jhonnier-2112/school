<?php
namespace App\Services;

use App\Config\Database;
use PDO;
use PDOException;

class AuditLogService {

    /**
     * Registra un evento de auditoría en la base de datos
     */
    public static function log(string $action, string $description, ?array $metadata = null, ?int $userId = null): bool {
        try {
            // Iniciar sesión si no está activa para poder leer el user_id
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if ($userId === null && isset($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
            }

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

            $database = new Database();
            $db = $database->getConnection();
            if (!$db) return false;

            // Auto-creación de tabla por seguridad si no existe
            $db->exec("CREATE TABLE IF NOT EXISTS `audit_logs` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NULL,
              `action` VARCHAR(100) NOT NULL,
              `description` TEXT NOT NULL,
              `ip_address` VARCHAR(45) NULL,
              `user_agent` VARCHAR(255) NULL,
              `metadata` TEXT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $sql = "INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent, metadata, created_at)
                    VALUES (:user_id, :action, :description, :ip, :user_agent, :metadata, NOW())";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':description' => $description,
                ':ip' => substr($ip, 0, 45),
                ':user_agent' => $userAgent,
                ':metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null
            ]);
        } catch (PDOException $e) {
            error_log("AuditLogService::log() Error: " . $e->getMessage());
            return false;
        }
    }
}
