<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Product {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function paginate(int $page = 1, int $perPage = 12, array $filters = [], string $order = 'created_at DESC'): array {
        $offset = max(0, ($page - 1) * $perPage);

        $where = ['is_active = 1'];
        $params = [];

        if (!empty($filters['category'])) {
            $cat = strtolower(trim((string)$filters['category']));
            if ($cat === 'textil' || $cat === 'ropa') {
                $where[] = "(LOWER(category) = 'textil' OR LOWER(type) IN ('camisetas', 'esqueletos', 'licras', 'medias'))";
            } elseif ($cat === 'accesorios') {
                $where[] = "(LOWER(category) = 'accesorios' OR LOWER(type) IN ('botella_plegable', 'accesorios'))";
            } else {
                $where[] = '(category = :category OR category_id IN (SELECT id FROM categories WHERE slug = :category OR name = :category))';
                $params[':category'] = $filters['category'];
            }
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'category_id = :category_id';
            $params[':category_id'] = $filters['category_id'];
        }
        if (!empty($filters['gender'])) {
            $g = strtolower((string)$filters['gender']);
            $where[] = 'LOWER(gender) = :gender';
            $params[':gender'] = $g;
        }
        // Filtro por categoría de prenda (type): soporta uno o varios valores
        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $types = array_values(array_filter(array_map(function($t){ return strtolower((string)$t); }, $filters['type'])));
                if (count($types) > 0) {
                    $phs = [];
                    foreach ($types as $i => $t) {
                        $ph = ":type{$i}";
                        $phs[] = $ph;
                        $params[$ph] = $t;
                    }
                    $where[] = 'LOWER(type) IN (' . implode(', ', $phs) . ')';
                }
            } else {
                $where[] = 'LOWER(type) = :type';
                $params[':type'] = strtolower((string)$filters['type']);
            }
        }
        if (!empty($filters['min_price'])) {
            $where[] = 'price >= :min_price';
            $params[':min_price'] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where[] = 'price <= :max_price';
            $params[':max_price'] = $filters['max_price'];
        }

        $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Total
        $countSql = "SELECT COUNT(*) as total FROM products $whereSql";
        $stmt = $this->conn->prepare($countSql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Items
        $allowedOrder = ['created_at DESC', 'price ASC', 'price DESC', 'name ASC'];
        if (!in_array($order, $allowedOrder)) {
            $order = 'created_at DESC';
        }

        // Orden personalizado para "Más nuevos": priorizar carrera, esqueleto, camiseta negra
        $orderSql = $order;
        if ($order === 'created_at DESC') {
            $orderSql = "CASE
                WHEN slug = 'camiseta_oficial_carrera' THEN 1
                WHEN LOWER(type) = 'esqueletos' THEN 2
                WHEN LOWER(name) LIKE '%negra%' THEN 3
                ELSE 99
            END, created_at DESC";
        }

        $sql = "SELECT id, sku, name, slug, description, category, category_id, gender, type, colors, sizes, price, image, video, images, is_new, is_offer
                FROM products $whereSql ORDER BY $orderSql LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $perPage ? max(1, (int)ceil($total / $perPage)) : 1,
            ],
        ];
    }

    public function findBySlug(string $slug): ?array {
        try {
            $sql = "SELECT id, sku, name, slug, description, category, category_id, gender, type, colors, sizes, price, image, video, images, is_new, is_offer
                    FROM products WHERE slug = :slug AND is_active = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':slug', $slug);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function findById(int $id): ?array {
        try {
            $sql = "SELECT id, sku, name, slug, description, category, category_id, gender, type, colors, sizes, price, image, video, images, is_new, is_offer
                    FROM products WHERE id = :id AND is_active = 1 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Obtiene las categorías asociadas al producto
     */
    public function getCategories(int $productId): array {
        try {
            $sql = "SELECT c.id, c.name, c.slug, c.description 
                    FROM categories c 
                    INNER JOIN category_product cp ON c.id = cp.category_id 
                    WHERE cp.product_id = :p_id AND c.is_active = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':p_id' => $productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}