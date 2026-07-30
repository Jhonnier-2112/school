-- 1. Insertar el Administrador si no existe
INSERT INTO `users` (
    `nombres`, `apellidos`, `tipo_documento`, `numero_documento`, 
    `email`, `password`, `telefono`, `direccion`, `municipio`, `departamento`, 
    `role`, `role_id`, `status`, `created_at`
) VALUES (
    'Admin', 'FemTribe', 'CC', '9999999999', 
    'admin@gmail.com', '$2y$10$J3hfPZp7Tva64cJ/AK0nxeb0TQ/lJrMzWkrW9FyDEzuSQd2klfmhO', '3000000000', 'Oficina FemTribe', 'Cali', 'Valle del Cauca', 
    'admin', 'a1b2c3d4-0002-0002-0002-000000000002', 1, NOW()
) ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `role_id` = VALUES(`role_id`), `role` = VALUES(`role`);

-- 2. Limpiar y re-insertar productos random con imágenes en img/products
-- Borrar productos existentes para hacer la carga limpia
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `category_product`;
TRUNCATE TABLE `products`;
SET FOREIGN_KEY_CHECKS = 1;

-- Insertar productos con imágenes apuntando a img/products/...
INSERT INTO `products` (`id`, `sku`, `name`, `slug`, `description`, `category`, `category_id`, `gender`, `type`, `price`, `stock`, `image`, `is_new`, `is_offer`, `is_active`) VALUES
(1, 'CO-001', 'Camiseta Oficial Carrera 2026', 'camiseta_oficial_carrera_2026', 'Camiseta oficial de la carrera Corre con FemTribe 2026. Transpirable, dry-fit y excelente ajuste ergonómico.', 'Carrera Oficial', 3, 'mujer', 'camisetas', 55000.00, 200, 'img/products/camiseta_oficial_carrera.png', 1, 0, 1),
(2, 'CF-002', 'Camiseta FemTribe Training Negra', 'camiseta_femtribe_training_negra', 'Camiseta de entrenamiento oficial de la comunidad FemTribe, color negro clásico, ultra ligera.', 'Textil', 1, 'mujer', 'camisetas', 60000.00, 100, 'img/products/camiseta_femtribe_training_negra.png', 0, 0, 1),
(3, 'EL-003', 'Esqueleto Limit Run Verde', 'esqueleto_limit_run_verde', 'Esqueleto deportivo verde fosforescente, edición Limit Run 2025. Súper transpirable para distancias largas.', 'Textil', 1, 'mujer', 'esqueletos', 45000.00, 150, 'img/products/esqueleto_limit_run_verde.png', 1, 1, 1),
(4, 'BF-004', 'Soft Flask Flex 500ml', 'soft_flask_flex_500ml', 'Botella flexible de hidratación rápida para chaleco de running. Libre de BPA y fácil de lavar.', 'Accesorios', 2, 'unisex', 'botella_plegable', 35000.00, 300, 'img/products/soft_flask_flex_500ml.png', 0, 0, 1),
(5, 'CO-005', 'Dorsal Oficial Carrera 2026', 'dorsal_oficial_carrera_2026', 'Número de competencia con chip de cronometraje integrado y personalizado para la carrera.', 'Carrera Oficial', 3, 'unisex', 'accesorios', 15000.00, 600, 'img/products/dorsal_carrera.png', 1, 0, 1),
(6, 'CF-006', 'Visera de Running FemTribe', 'visera_running_femtribe', 'Visera deportiva ajustable, absorbe el sudor y protege de los rayos UV durante las carreras diurnas.', 'Accesorios', 2, 'unisex', 'accesorios', 25000.00, 80, 'img/products/visera_running.png', 0, 1, 1),
(7, 'EL-007', 'Esqueleto FemTribe Power Rosa', 'esqueleto_femtribe_power_rosa', 'Esqueleto deportivo rosado confeccionado con poliéster reciclado, ajuste cómodo y fresco.', 'Textil', 1, 'mujer', 'esqueletos', 48000.00, 90, 'img/products/esqueleto_femtribe_power_rosa.png', 0, 0, 1),
(8, 'BF-008', 'Cinturón de Hidratación Run', 'cinturon_hidratacion_run', 'Cinturón elástico porta Soft Flask y objetos personales, ajustable y sin rebotes al correr.', 'Accesorios', 2, 'unisex', 'accesorios', 42000.00, 120, 'img/products/cinturon_hidratacion.png', 1, 0, 1);

-- Vincular productos en la tabla pivote category_product
INSERT INTO `category_product` (`category_id`, `product_id`) VALUES
(3, 1),
(1, 2),
(1, 3),
(2, 4),
(3, 5),
(2, 6),
(1, 7),
(2, 8);
