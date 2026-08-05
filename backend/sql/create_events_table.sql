-- Tabla de Eventos Principales
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL DEFAULT 'Carrera Corre Con FemTribe',
  `slug` VARCHAR(255) NOT NULL DEFAULT 'corre-con-femtribe',
  `description` TEXT NULL,
  `location` VARCHAR(255) NULL DEFAULT 'Cali, Valle del Cauca',
  `total_slots` INT NOT NULL DEFAULT 600,
  `presale_start_date` DATETIME NULL,
  `presale_end_date` DATETIME NULL,
  `event_end_date` DATETIME NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Semilla por defecto del evento si no existe
INSERT INTO `events` (`id`, `title`, `slug`, `location`, `total_slots`, `presale_start_date`, `presale_end_date`, `event_end_date`, `is_active`)
SELECT 1, 'Carrera Corre Con FemTribe', 'corre-con-femtribe', 'Cali, Valle del Cauca', 600, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 60 DAY), 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `events` WHERE `id` = 1);

-- Actualizar ENUM de category_type en race_stages para incluir 'adicional'
ALTER TABLE `race_stages` MODIFY COLUMN `category_type` ENUM('adulto', 'nino', 'mascota', 'adicional') NOT NULL DEFAULT 'adulto';

-- Limpiar o actualizar las etapas existentes para ajustarse a 3K, 5K, 10K y Adicionales
INSERT INTO `race_stages` (`id`, `event_id`, `name`, `slug`, `category_type`, `distance`, `presale_price`, `price`, `description`, `is_active`) VALUES
(1, 1, '3K Perro y Adulto (Pet Run)', '3k-perro-adulto', 'mascota', '3K', 45000.00, 55000.00, 'Carrera recreativa 3K acompañada de tu mascota y adulto responsable', 1),
(2, 1, '3K Niño y Adulto (Infantil)', '3k-nino-adulto', 'nino', '3K', 40000.00, 50000.00, 'Carrera infantil 3K acompañada de adulto responsable', 1),
(3, 1, '5K Adulto', '5k-adulto', 'adulto', '5K', 55000.00, 65000.00, 'Recorrido urbano de 5K para adultos', 1),
(4, 1, '10K Adulto', '10k-adulto', 'adulto', '10K', 75000.00, 85000.00, 'Recorrido competitivo de 10K para adultos', 1),
(5, 1, 'Adicional 5K (Adulto 3K)', 'adicional-5k', 'adicional', '5K', 25000.00, 30000.00, 'Kilometraje adicional de 5K para adulto inscrito en 3K', 1),
(6, 1, 'Adicional 10K (Adulto 3K)', 'adicional-10k', 'adicional', '10K', 35000.00, 40000.00, 'Kilometraje adicional de 10K para adulto inscrito en 3K', 1)
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`),
  `slug` = VALUES(`slug`),
  `category_type` = VALUES(`category_type`),
  `distance` = VALUES(`distance`),
  `presale_price` = VALUES(`presale_price`),
  `price` = VALUES(`price`),
  `description` = VALUES(`description`),
  `is_active` = VALUES(`is_active`);
