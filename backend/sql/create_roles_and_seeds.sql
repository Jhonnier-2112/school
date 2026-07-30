-- ============================================================
-- Migración: Tabla de Roles con UUID + Semillas + FK en users
-- ============================================================

-- 1. Crear tabla de roles con UUID como PK
CREATE TABLE IF NOT EXISTS `roles` (
  `id`          CHAR(36)     NOT NULL DEFAULT (UUID()),
  `name`        VARCHAR(60)  NOT NULL UNIQUE,
  `slug`        VARCHAR(60)  NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Semillas: rol cliente y rol administrador
INSERT IGNORE INTO `roles` (`id`, `name`, `slug`, `description`, `is_active`) VALUES
  ('a1b2c3d4-0001-0001-0001-000000000001', 'Cliente',       'cliente',       'Usuario registrado con acceso a compras, inscripciones y perfil', 1),
  ('a1b2c3d4-0002-0002-0002-000000000002', 'Administrador', 'administrador', 'Acceso total al panel de administración y gestión de la plataforma', 1);

-- 3. Agregar columna role_id (UUID FK) en la tabla users si no existe
SET @dbname = DATABASE();
SET @tablename = "users";
SET @columnname = "role_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE users ADD COLUMN role_id CHAR(36) NULL DEFAULT NULL AFTER role;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 4. Poblar role_id en users existentes basándose en el campo ENUM role
UPDATE `users`
SET `role_id` = 'a1b2c3d4-0002-0002-0002-000000000002'
WHERE `role` = 'admin' AND `role_id` IS NULL;

UPDATE `users`
SET `role_id` = 'a1b2c3d4-0001-0001-0001-000000000001'
WHERE `role` != 'admin' AND `role_id` IS NULL;

-- 5. Agregar FK entre users.role_id y roles.id si no existe
SET @constraint_name = "fk_users_role_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND CONSTRAINT_NAME = @constraint_name
  ) > 0,
  "SELECT 1",
  "ALTER TABLE users ADD CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL ON UPDATE CASCADE;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
