<?php
namespace App\Services;

use App\Config\Database;
use PDO;
use PDOException;

class AccessLogService {

    /**
     * Registra un log de acceso a la página en la base de datos
     */
    public static function logAccess() {
        try {
            // Ignorar solicitudes de archivos estáticos (CSS, JS, imágenes)
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|mp4|webm)$/i', $uri)) {
                return;
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $userId = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            // Si viene una lista de IPs en X-Forwarded-For, tomar la primera
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }

            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
            $referer = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255);

            $database = new Database();
            $db = $database->getConnection();

            if (!$db) return;

            $sql = "INSERT INTO user_access_logs (user_id, ip_address, page_url, method, user_agent, referer, created_at)
                    VALUES (:user_id, :ip_address, :page_url, :method, :user_agent, :referer, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':ip_address' => substr($ip, 0, 45),
                ':page_url' => substr($uri, 0, 255),
                ':method' => $method,
                ':user_agent' => $userAgent,
                ':referer' => $referer
            ]);
        } catch (PDOException $e) {
            // Log silencioso para no interrumpir la navegación del usuario
            error_log("AccessLogService::logAccess Error: " . $e->getMessage());
        }
    }
}
