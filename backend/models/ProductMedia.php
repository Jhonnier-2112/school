<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class ProductMedia {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtiene todos los medios (imágenes y videos) de un producto
     */
    public function getByProductId(int $productId): array {
        try {
            $sql = "SELECT id, product_id, type, url, sort_order 
                    FROM product_media 
                    WHERE product_id = :product_id 
                    ORDER BY sort_order ASC, id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("ProductMedia::getByProductId Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Guarda la lista de medios para un producto (reemplazando la anterior)
     */
    public function saveMedia(int $productId, array $mediaList): bool {
        try {
            $this->conn->beginTransaction();

            // 1. Eliminar medios existentes
            $stmt = $this->conn->prepare("DELETE FROM product_media WHERE product_id = :product_id");
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            // 2. Insertar nuevos medios
            if (!empty($mediaList)) {
                $sql = "INSERT INTO product_media (product_id, type, url, sort_order) 
                        VALUES (:product_id, :type, :url, :sort_order)";
                $stmtInsert = $this->conn->prepare($sql);
                
                foreach ($mediaList as $index => $item) {
                    $urlVal = trim($item['url']);
                    if ($urlVal === '') continue;
                    
                    $stmtInsert->execute([
                        ':product_id' => $productId,
                        ':type' => $item['type'] === 'video' ? 'video' : 'image',
                        ':url' => $urlVal,
                        ':sort_order' => isset($item['sort_order']) ? (int)$item['sort_order'] : $index
                    ]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("ProductMedia::saveMedia Error: " . $e->getMessage());
            return false;
        }
    }
}
