<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

/**
 * Modelo de Roles del sistema
 *
 * Gestiona los roles de usuario con UUID como clave primaria.
 * Roles predefinidos:
 *   - Cliente       (slug: cliente,       id: a1b2c3d4-0001-0001-0001-000000000001)
 *   - Administrador (slug: administrador, id: a1b2c3d4-0002-0002-0002-000000000002)
 */
class Role {

    // UUIDs semilla predefinidos
    const CLIENTE_ID       = 'a1b2c3d4-0001-0001-0001-000000000001';
    const ADMIN_ID         = 'a1b2c3d4-0002-0002-0002-000000000002';
    const CLIENTE_SLUG     = 'cliente';
    const ADMIN_SLUG       = 'administrador';

    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Retorna todos los roles activos
     */
    public function getAll(): array {
        try {
            $stmt = $this->conn->query("SELECT * FROM roles WHERE is_active = 1 ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Role::getAll() Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca un rol por su UUID
     */
    public function findById(string $id): ?array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM roles WHERE id = :id AND is_active = 1 LIMIT 1");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Role::findById() Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca un rol por su slug
     */
    public function findBySlug(string $slug): ?array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM roles WHERE slug = :slug AND is_active = 1 LIMIT 1");
            $stmt->execute([':slug' => strtolower(trim($slug))]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Role::findBySlug() Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Asigna un rol a un usuario por UUID de rol
     */
    public function assignToUser(int $userId, string $roleId): bool {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET role_id = :role_id WHERE id = :user_id");
            return $stmt->execute([':role_id' => $roleId, ':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Role::assignToUser() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna el UUID del rol Cliente (semilla predefinida)
     */
    public static function getClienteId(): string {
        return self::CLIENTE_ID;
    }

    /**
     * Retorna el UUID del rol Administrador (semilla predefinida)
     */
    public static function getAdminId(): string {
        return self::ADMIN_ID;
    }

    /**
     * Comprueba si un UUID de rol corresponde al Administrador
     */
    public static function isAdmin(string $roleId): bool {
        return $roleId === self::ADMIN_ID;
    }

    /**
     * Comprueba si un UUID de rol corresponde al Cliente
     */
    public static function isCliente(string $roleId): bool {
        return $roleId === self::CLIENTE_ID;
    }
}
