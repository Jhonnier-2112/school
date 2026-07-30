<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Category {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtiene todas las categorías activas
     */
    public function getAll(): array {
        try {
            $stmt = $this->conn->query("SELECT id, name, slug, description, image, parent_id, created_at FROM categories WHERE is_active = 1 ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Busca categoría por slug
     */
    public function findBySlug(string $slug): ?array {
        try {
            $stmt = $this->conn->prepare("SELECT id, name, slug, description, image, parent_id, created_at FROM categories WHERE slug = :slug AND is_active = 1 LIMIT 1");
            $stmt->execute([':slug' => trim($slug)]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Busca categoría por ID
     */
    public function findById(int $id): ?array {
        try {
            $stmt = $this->conn->prepare("SELECT id, name, slug, description, image, parent_id, created_at FROM categories WHERE id = :id AND is_active = 1 LIMIT 1");
            $stmt->execute([':id' => $id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Crea una nueva categoría
     */
    public function create(array $data) {
        try {
            $sql = "INSERT INTO categories (name, slug, description, image, parent_id, is_active, created_at) 
                    VALUES (:name, :slug, :description, :image, :parent_id, 1, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':image' => $data['image'] ?? null,
                ':parent_id' => !empty($data['parent_id']) ? $data['parent_id'] : null
            ]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Obtiene los productos pertenecientes a una categoría
     */
    public function getProducts(int $categoryId, int $limit = 12, int $offset = 0): array {
        try {
            $sql = "SELECT p.id, p.sku, p.name, p.slug, p.description, p.category, p.gender, p.type, p.price, p.image, p.is_new, p.is_offer 
                    FROM products p 
                    LEFT JOIN category_product cp ON p.id = cp.product_id 
                    WHERE (cp.category_id = :cat_id OR p.category_id = :cat_id) AND p.is_active = 1 
                    GROUP BY p.id 
                    ORDER BY p.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':cat_id', $categoryId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Vincula un producto a una categoría en la tabla pivote category_product
     */
    public function assignProduct(int $categoryId, int $productId): bool {
        try {
            $sql = "INSERT IGNORE INTO category_product (category_id, product_id) VALUES (:category_id, :product_id)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':category_id' => $categoryId,
                ':product_id' => $productId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
