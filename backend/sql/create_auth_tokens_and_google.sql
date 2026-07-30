-- Agregar columnas de Google OAuth en la tabla users si no existen
SET @dbname = DATABASE();
SET @tablename = "users";

-- Columna google_id
SET @columnname = "google_id";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL UNIQUE AFTER email;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Columna avatar
SET @columnname = "avatar";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER google_id;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Tabla de Tokens de Autenticación (Access Tokens y Refresh Tokens de 1 Hora)
CREATE TABLE IF NOT EXISTS `user_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_type` ENUM('access_token', 'refresh_token') NOT NULL DEFAULT 'refresh_token',
  `token_hash` VARCHAR(255) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_user_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
