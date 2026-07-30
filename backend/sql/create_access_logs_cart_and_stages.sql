-- Tabla 1: Registro de Logs de Acceso a la Página
CREATE TABLE IF NOT EXISTS `user_access_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `page_url` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL DEFAULT 'GET',
  `user_agent` VARCHAR(255) NULL,
  `referer` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_access_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla 2: Carrito de Compras Persistente en Base de Datos
CREATE TABLE IF NOT EXISTS `user_cart_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NULL,
  `product_slug` VARCHAR(100) NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `quantity` INT NOT NULL DEFAULT 1,
  `color` VARCHAR(50) NULL,
  `gender` VARCHAR(50) NULL,
  `size` VARCHAR(20) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla 3: Etapas de Carrera / Distancias
CREATE TABLE IF NOT EXISTS `race_stages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `category_type` ENUM('adulto', 'nino', 'mascota') NOT NULL DEFAULT 'adulto',
  `distance` VARCHAR(20) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `description` TEXT NULL,
  `start_time` DATETIME NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed inicial de Etapas de Carrera
INSERT IGNORE INTO `race_stages` (`id`, `name`, `slug`, `category_type`, `distance`, `price`, `description`, `is_active`) VALUES
(1, 'Etapa 5K - Carrera Recreativa', 'etapa-5k', 'adulto', '5K', 65000.00, 'Recorrido urbano de 5K para adultos', 1),
(2, 'Etapa 10K - Carrera Competitiva', 'etapa-10k', 'adulto', '10K', 85000.00, 'Recorrido urbano de 10K para adultos', 1),
(3, 'Etapa 15K - Desafío Ciudad', 'etapa-15k', 'adulto', '15K', 95000.00, 'Desafío de fondo 15K para adultos', 1),
(4, 'Etapa Kids - Carrera Infantil', 'etapa-kids', 'nino', '2K', 45000.00, 'Recorrido especial asistido para niños', 1),
(5, 'Etapa Pet Run - Carrera con Mascota', 'etapa-pet-run', 'mascota', '3K', 55000.00, 'Carrera recreativa 3K acompañado de tu mascota', 1);

-- Actualizar tabla de inscripciones registrations con columnas de categoría y múltiples etapas
SET @dbname = DATABASE();
SET @tablename = "registrations";

-- Columna categoria_participante
SET @columnname = "categoria_participante";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE registrations ADD COLUMN categoria_participante ENUM('adulto', 'nino', 'mascota') NOT NULL DEFAULT 'adulto' AFTER event_id;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Columna etapas_seleccionadas (JSON/TEXT con IDs de etapas)
SET @columnname = "etapas_seleccionadas";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE registrations ADD COLUMN etapas_seleccionadas TEXT NULL AFTER categoria_participante;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Columna datos de la mascota
SET @columnname = "nombre_mascota";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE registrations ADD COLUMN nombre_mascota VARCHAR(100) NULL AFTER etapas_seleccionadas, ADD COLUMN raza_mascota VARCHAR(100) NULL AFTER nombre_mascota;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Columna datos del acudiente (para niños)
SET @columnname = "acudiente_nombre";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE registrations ADD COLUMN acudiente_nombre VARCHAR(150) NULL AFTER raza_mascota, ADD COLUMN acudiente_documento VARCHAR(30) NULL AFTER acudiente_nombre;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
