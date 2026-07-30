-- Migración para crear la tabla de usuarios
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `tipo_documento` VARCHAR(20) NOT NULL DEFAULT 'CC',
  `numero_documento` VARCHAR(30) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `telefono` VARCHAR(30) NOT NULL,
  `direccion` VARCHAR(255) NOT NULL,
  `municipio` VARCHAR(100) NOT NULL DEFAULT 'Cali',
  `departamento` VARCHAR(100) NOT NULL DEFAULT 'Valle del Cauca',
  `fecha_nacimiento` DATE NULL,
  `genero` VARCHAR(20) NULL,
  `eps` VARCHAR(100) NULL,
  `grupo_sanguineo` VARCHAR(5) NULL,
  `rh` VARCHAR(5) NULL,
  `role` ENUM('runner', 'admin') NOT NULL DEFAULT 'runner',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna user_id en la tabla registrations si no existe
SET @dbname = DATABASE();
SET @tablename = "registrations";
SET @columnname = "user_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE registrations ADD COLUMN user_id INT NULL AFTER id;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
