<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Review {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Inserta un nuevo comentario/calificación en la base de datos
     */
    public function create(array $data): bool {
        try {
            $sql = "INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at)
                    VALUES (:product_id, :user_id, :rating, :comment, NOW())";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':product_id' => (int)$data['product_id'],
                ':user_id' => (int)$data['user_id'],
                ':rating' => (int)$data['rating'],
                ':comment' => trim($data['comment'])
            ]);
        } catch (PDOException $e) {
            error_log("Review::create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los comentarios/calificaciones para un producto específico
     */
    public function getByProductId(int $productId): array {
        try {
            $sql = "SELECT r.id, r.rating, r.comment, r.created_at, u.nombres, u.apellidos, u.email 
                    FROM product_reviews r
                    INNER JOIN users u ON r.user_id = u.id
                    WHERE r.product_id = :product_id
                    ORDER BY r.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Review::getByProductId Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el promedio de calificación para un producto específico
     */
    public function getAverageRating(int $productId): float {
        try {
            $sql = "SELECT AVG(rating) as average FROM product_reviews WHERE product_id = :product_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && $row['average'] !== null ? round((float)$row['average'], 1) : 0.0;
        } catch (PDOException $e) {
            error_log("Review::getAverageRating Error: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Obtiene el conteo total de comentarios para un producto específico
     */
    public function getCountByProductId(int $productId): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM product_reviews WHERE product_id = :product_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['total'] : 0;
        } catch (PDOException $e) {
            error_log("Review::getCountByProductId Error: " . $e->getMessage());
            return 0;
        }
    }
}
