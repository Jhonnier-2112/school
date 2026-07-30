-- Tabla de Categorías de Productos
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `parent_id` INT NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla Pivote para Relación Muchos a Muchos (Categorías <-> Productos)
CREATE TABLE IF NOT EXISTS `category_product` (
  `category_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  PRIMARY KEY (`category_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna category_id en la tabla products si no existe
SET @dbname = DATABASE();
SET @tablename = "products";
SET @columnname = "category_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE products ADD COLUMN category_id INT NULL AFTER category;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Tabla de Órdenes de Compra / Inscripción
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `customer_document` VARCHAR(30) NOT NULL,
  `shipping_address` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL DEFAULT 'Cali',
  `department` VARCHAR(100) NOT NULL DEFAULT 'Valle del Cauca',
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'paid', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'bancolombia_wompi',
  `transaction_reference` VARCHAR(100) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Detalle de Orden (Productos comprados o Cupos de Carrera)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `quantity` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Historial de Transacciones de Pago (Bancolombia / Wompi)
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `payment_gateway` VARCHAR(50) NOT NULL DEFAULT 'bancolombia_wompi',
  `transaction_reference` VARCHAR(100) NOT NULL UNIQUE,
  `gateway_transaction_id` VARCHAR(100) NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'COP',
  `status` VARCHAR(30) NOT NULL DEFAULT 'PENDING',
  `payment_method_type` VARCHAR(50) NULL,
  `raw_response` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed inicial de Categorías principales
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `description`, `is_active`) VALUES
(1, 'Textil', 'textil', 'Prendas e indumentaria deportiva de running', 1),
(2, 'Accesorios', 'accesorios', 'Accesorios, botellas soft flask y equipamiento deportivo', 1),
(3, 'Carrera Oficial', 'carrera-oficial', 'Cupos de inscripción y merchandising oficial de carreras', 1);
