-- Agregar columnas para color y tallas disponibles en la tabla de productos
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `colors` VARCHAR(255) NULL AFTER `type`,
ADD COLUMN IF NOT EXISTS `sizes` VARCHAR(255) NULL AFTER `colors`;
