-- Agregar columna de límite de cupos preventa a race_stages
ALTER TABLE `race_stages` ADD COLUMN `presale_slots_limit` INT(11) DEFAULT NULL;

-- Agregar columna de registro de etapas compradas en preventa a registrations
ALTER TABLE `registrations` ADD COLUMN `etapas_preventa` TEXT DEFAULT NULL;
