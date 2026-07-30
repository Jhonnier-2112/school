<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class UserCart {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtiene los productos en el carrito guardados en la BD para un usuario
     */
    public function getCart(int $userId): array {
        try {
            $stmt = $this->conn->prepare("SELECT product_id, product_slug as slug, product_name as name, price, quantity as qty, color, gender, size FROM user_cart_items WHERE user_id = :user_id ORDER BY id ASC");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Sincroniza el listado completo de ítems del carrito en la base de datos
     */
    public function syncCart(int $userId, array $items): bool {
        try {
            $this->conn->beginTransaction();

            // 1. Limpiar carrito existente del usuario
            $stmt = $this->conn->prepare("DELETE FROM user_cart_items WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);

            // 2. Insertar ítems actualizados
            $insertSql = "INSERT INTO user_cart_items (user_id, product_id, product_slug, product_name, price, quantity, color, gender, size, created_at)
                          VALUES (:user_id, :product_id, :slug, :name, :price, :qty, :color, :gender, :size, NOW())";
            $insertStmt = $this->conn->prepare($insertSql);

            foreach ($items as $item) {
                $insertStmt->execute([
                    ':user_id' => $userId,
                    ':product_id' => $item['product_id'] ?? null,
                    ':slug' => $item['slug'] ?? 'producto',
                    ':name' => $item['name'] ?? $item['title'] ?? 'Producto',
                    ':price' => $item['price'] ?? 0,
                    ':qty' => $item['qty'] ?? $item['quantity'] ?? 1,
                    ':color' => $item['color'] ?? null,
                    ':gender' => $item['gender'] ?? null,
                    ':size' => $item['size'] ?? null
                ]);
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("UserCart::syncCart Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vacía el carrito del usuario
     */
    public function clearCart(int $userId): bool {
        try {
            $stmt = $this->conn->prepare("DELETE FROM user_cart_items WHERE user_id = :user_id");
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
