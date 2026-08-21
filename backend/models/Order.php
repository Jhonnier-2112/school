<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Order {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Genera un número de orden único (ej. FT-20260723-9842)
     */
    public static function generateOrderNumber(): string {
        return 'FT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }

    /**
     * Crea una orden completa con sus items asociados
     */
    public function createOrder(array $data, array $items): ?array {
        try {
            $this->conn->beginTransaction();

            $orderNumber = $data['order_number'] ?? self::generateOrderNumber();

            $sql = "INSERT INTO orders (
                order_number, user_id, customer_name, customer_email, customer_phone,
                customer_document, shipping_address, city, department, subtotal, tax,
                shipping_fee, total, status, payment_method, transaction_reference, created_at
            ) VALUES (
                :order_number, :user_id, :customer_name, :customer_email, :customer_phone,
                :customer_document, :shipping_address, :city, :department, :subtotal, :tax,
                :shipping_fee, :total, 'pending', :payment_method, :transaction_reference, NOW()
            )";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':order_number' => $orderNumber,
                ':user_id' => $data['user_id'] ?? null,
                ':customer_name' => $data['customer_name'] ?? '',
                ':customer_email' => $data['customer_email'] ?? '',
                ':customer_phone' => $data['customer_phone'] ?? '',
                ':customer_document' => $data['customer_document'] ?? '',
                ':shipping_address' => $data['shipping_address'] ?? '',
                ':city' => $data['city'] ?? 'Cali',
                ':department' => $data['department'] ?? 'Valle del Cauca',
                ':subtotal' => $data['subtotal'] ?? 0.00,
                ':tax' => $data['tax'] ?? 0.00,
                ':shipping_fee' => $data['shipping_fee'] ?? 0.00,
                ':total' => $data['total'] ?? 0.00,
                ':payment_method' => $data['payment_method'] ?? 'bancolombia_wompi',
                ':transaction_reference' => $orderNumber
            ]);

            $orderId = $this->conn->lastInsertId();

            // Insertar items de la orden
            $itemSql = "INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal, created_at)
                        VALUES (:order_id, :product_id, :product_name, :price, :quantity, :subtotal, NOW())";
            $itemStmt = $this->conn->prepare($itemSql);

            foreach ($items as $item) {
                $quantity = intval($item['quantity'] ?? $item['qty'] ?? $item['cantidad'] ?? 1);
                $price = floatval($item['price'] ?? 0);
                $subtotal = $price * $quantity;
                $itemStmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $item['product_id'] ?? null,
                    ':product_name' => $item['name'] ?? $item['product_name'] ?? 'Producto',
                    ':price' => $price,
                    ':quantity' => $quantity,
                    ':subtotal' => $subtotal
                ]);
            }

            $this->conn->commit();

            return [
                'id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $data['total'],
                'customer_email' => $data['customer_email']
            ];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Order::createOrder() Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca orden por número de orden o referencia
     */
    public function findByOrderNumber(string $orderNumber): ?array {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM orders WHERE order_number = :ref OR transaction_reference = :ref LIMIT 1");
            $stmt->execute([':ref' => trim($orderNumber)]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Cargar items
                $itemStmt = $this->conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
                $itemStmt->execute([':order_id' => $order['id']]);
                $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            return $order ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Actualiza el estado de la orden
     */
    public function updateStatus(int $orderId, string $status, ?string $transactionRef = null): bool {
        try {
            $sql = "UPDATE orders SET status = :status, updated_at = NOW()";
            $params = [':status' => $status, ':order_id' => $orderId];

            if ($transactionRef !== null) {
                $sql .= ", transaction_reference = :ref";
                $params[':ref'] = $transactionRef;
            }

            $sql .= " WHERE id = :order_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Registra un pago en la tabla payments
     */
    public function addPayment(array $paymentData): bool {
        try {
            $sql = "INSERT INTO payments (
                order_id, payment_gateway, transaction_reference, gateway_transaction_id,
                amount, currency, status, payment_method_type, raw_response, created_at
            ) VALUES (
                :order_id, :payment_gateway, :transaction_reference, :gateway_transaction_id,
                :amount, :currency, :status, :payment_method_type, :raw_response, NOW()
            )";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':order_id' => $paymentData['order_id'],
                ':payment_gateway' => $paymentData['payment_gateway'] ?? 'bancolombia_wompi',
                ':transaction_reference' => $paymentData['transaction_reference'],
                ':gateway_transaction_id' => $paymentData['gateway_transaction_id'] ?? null,
                ':amount' => $paymentData['amount'],
                ':currency' => $paymentData['currency'] ?? 'COP',
                ':status' => $paymentData['status'] ?? 'PENDING',
                ':payment_method_type' => $paymentData['payment_method_type'] ?? null,
                ':raw_response' => is_array($paymentData['raw_response']) ? json_encode($paymentData['raw_response']) : $paymentData['raw_response']
            ]);
        } catch (PDOException $e) {
            error_log("Order::addPayment() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el historial de compras asociadas a un usuario
     */
    public function getUserOrders(int $userId): array {
        try {
            $stmt = $this->conn->prepare("SELECT id, order_number, total, status, payment_method, created_at FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
            $stmt->execute([':user_id' => $userId]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($orders as &$ord) {
                $itemStmt = $this->conn->prepare("SELECT product_name, price, quantity, subtotal FROM order_items WHERE order_id = :order_id");
                $itemStmt->execute([':order_id' => $ord['id']]);
                $ord['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            return $orders;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Actualiza el estado del pago mediante la referencia de transacción
     */
    public function updatePaymentStatus(string $reference, string $status, ?string $gatewayTxId = null, $rawResponse = null): bool {
        try {
            $sql = "UPDATE payments SET status = :status, updated_at = NOW()";
            $params = [':status' => $status, ':ref' => $reference];

            if ($gatewayTxId !== null) {
                $sql .= ", gateway_transaction_id = :gtx_id";
                $params[':gtx_id'] = $gatewayTxId;
            }

            if ($rawResponse !== null) {
                $sql .= ", raw_response = :raw";
                $params[':raw'] = is_array($rawResponse) ? json_encode($rawResponse) : $rawResponse;
            }

            $sql .= " WHERE transaction_reference = :ref";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Registra un log de webhook de pago en la base de datos para auditorías
     */
    public function logWebhookAttempt(array $logData): bool {
        try {
            // Asegurar que la tabla existe
            $this->conn->exec("CREATE TABLE IF NOT EXISTS `payment_webhook_logs` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `ip_address` VARCHAR(45) NOT NULL,
              `payload` TEXT NOT NULL,
              `checksum_received` VARCHAR(255) NULL,
              `is_valid` TINYINT(1) NOT NULL DEFAULT 0,
              `error_message` VARCHAR(255) NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $sql = "INSERT INTO payment_webhook_logs (ip_address, payload, checksum_received, is_valid, error_message, created_at)
                    VALUES (:ip_address, :payload, :checksum_received, :is_valid, :error_message, NOW())";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':ip_address' => $logData['ip_address'],
                ':payload' => is_array($logData['payload']) ? json_encode($logData['payload']) : $logData['payload'],
                ':checksum_received' => $logData['checksum_received'] ?? null,
                ':is_valid' => $logData['is_valid'] ? 1 : 0,
                ':error_message' => $logData['error_message'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Order::logWebhookAttempt() Error: " . $e->getMessage());
            return false;
        }
    }
}
