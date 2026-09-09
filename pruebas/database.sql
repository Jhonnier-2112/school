-- ==========================================================
-- ESTRUCTURA Y SEMILLERO INICIAL DE BASE DE DATOS (ICFES)
-- Compatible con MySQL 5.7+ y MySQL 8+ (Hostinger)
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `users` (
    `id` CHAR(36) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'student',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Cursos
CREATE TABLE IF NOT EXISTS `courses` (
    `id` CHAR(36) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `total_price` DECIMAL(12,2) NOT NULL DEFAULT 700000.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Matrículas (Enrollments)
CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `course_id` CHAR(36) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending_contract',
    `enrolled_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Contratos Digitales
CREATE TABLE IF NOT EXISTS `contracts` (
    `id` CHAR(36) NOT NULL,
    `course_id` CHAR(36) NOT NULL,
    `version` VARCHAR(20) NOT NULL DEFAULT '1.0',
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla de Firmas de Contrato (Auditoría IP/Fecha)
CREATE TABLE IF NOT EXISTS `contract_signatures` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `contract_id` CHAR(36) NOT NULL,
    `ip_address` VARCHAR(64) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `signed_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_contract_id` (`contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla de Pagos y Abonos
CREATE TABLE IF NOT EXISTS `payments` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `enrollment_id` CHAR(36) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_date` DATETIME NOT NULL,
    `receipt_number` VARCHAR(64) DEFAULT NULL,
    `payment_method` VARCHAR(30) NOT NULL DEFAULT 'transfer',
    `notes` VARCHAR(255) DEFAULT NULL,
    `registered_by` CHAR(36) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_enrollment_id` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla de Documentos de Identidad (Cédula/TI)
CREATE TABLE IF NOT EXISTS `identification_documents` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `document_type` VARCHAR(20) NOT NULL DEFAULT 'CC',
    `document_number` VARCHAR(50) DEFAULT NULL,
    `file_url` VARCHAR(255) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `rejection_reason` VARCHAR(255) DEFAULT NULL,
    `reviewed_by` CHAR(36) DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabla de Materias Oficiales ICFES
CREATE TABLE IF NOT EXISTS `subjects` (
    `id` CHAR(36) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(30) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabla de Banco de Preguntas
CREATE TABLE IF NOT EXISTS `questions` (
    `id` CHAR(36) NOT NULL,
    `subject_id` CHAR(36) NOT NULL,
    `statement` TEXT NOT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `option_a` TEXT NOT NULL,
    `option_b` TEXT NOT NULL,
    `option_c` TEXT NOT NULL,
    `option_d` TEXT NOT NULL,
    `correct_option` VARCHAR(2) NOT NULL,
    `explanation` TEXT DEFAULT NULL,
    `score_weight` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tabla de Simulacros
CREATE TABLE IF NOT EXISTS `exams` (
    `id` CHAR(36) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `duration_minutes` INT NOT NULL DEFAULT 60,
    `is_published` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Tabla de Relación Examen - Preguntas
CREATE TABLE IF NOT EXISTS `exam_questions` (
    `id` CHAR(36) NOT NULL,
    `exam_id` CHAR(36) NOT NULL,
    `question_id` CHAR(36) NOT NULL,
    `order_index` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_exam_id` (`exam_id`),
    KEY `idx_question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Tabla de Sesiones de Examen de Estudiantes
CREATE TABLE IF NOT EXISTS `student_exams` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `exam_id` CHAR(36) NOT NULL,
    `started_at` DATETIME NOT NULL,
    `submitted_at` DATETIME DEFAULT NULL,
    `total_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `max_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `status` VARCHAR(20) NOT NULL DEFAULT 'in_progress',
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_exam_id` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Tabla de Respuestas de Estudiantes
CREATE TABLE IF NOT EXISTS `student_answers` (
    `id` CHAR(36) NOT NULL,
    `student_exam_id` CHAR(36) NOT NULL,
    `question_id` CHAR(36) NOT NULL,
    `selected_option` VARCHAR(2) NOT NULL,
    `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
    `score_earned` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_student_exam_id` (`student_exam_id`),
    KEY `idx_question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- DATOS INICIALES (SEMILLERO)
-- ==========================================================

-- Admin inicial: admin@icfes.com / Admin123456!
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `is_active`, `created_at`, `updated_at`)
VALUES (
    '3da31fdc-a4ff-4be4-a718-60ba7280fd7e',
    'Administrador Principal',
    'admin@icfes.com',
    '3001234567',
    '$2y$10$GRnHalrlAb57O1MTyU2ciODKAJdmzAiIU/0opwiZCKfvdYfu8ACk2',
    'admin',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE `email`=`email`;

-- Curso Base: $700.000 COP
INSERT INTO `courses` (`id`, `name`, `description`, `total_price`, `is_active`, `created_at`, `updated_at`)
VALUES (
    '2fa8d8ef-abdf-4324-8a2f-acf5e7e599b9',
    'Curso de Preparación Académica ICFES Saber 11°',
    'Programa integral con simulacros, talleres de resolución y acompañamiento docente.',
    700000.00,
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE `id`=`id`;

-- Contrato Base
INSERT INTO `contracts` (`id`, `course_id`, `version`, `title`, `content`, `is_active`, `created_at`, `updated_at`)
VALUES (
    '67719a03-ee8f-4eb6-9746-2f82096f7968',
    '2fa8d8ef-abdf-4324-8a2f-acf5e7e599b9',
    '1.0',
    'Términos y Condiciones del Servicio Educativo',
    'CONTRATO DE PRESTACIÓN DE SERVICIOS EDUCATIVOS\n\n1. OBJETO: El prestador se compromete a impartir al estudiante el programa de preparación para la prueba de Estado Saber 11° (ICFES).\n2. VALOR Y FORMA DE PAGO: El costo total del curso es de $700.000 COP, los cuales podrán cancelarse en cuotas convenidas o mediante abonos directos antes del inicio de las pruebas oficiales.\n3. COMPROMISO DEL ESTUDIANTE: El estudiante se compromete a cumplir con las actividades pedagógicas, presentar los simulacros programados y suministrar información verídica incluyendo su documento de identidad.\n4. POLÍTICA DE DOCUMENTACIÓN: El estudiante deberá cargar copia legible de su documento de identidad para verificación académica.\n5. ACEPTACIÓN: Al marcar la casilla de aceptación y continuar, el estudiante declara haber leído, comprendido y aceptado la totalidad de las cláusulas aquí descritas.',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE `id`=`id`;

-- 5 Materias ICFES
INSERT INTO `subjects` (`id`, `code`, `name`, `description`, `created_at`) VALUES
('d118955d-5306-475b-820f-ed091e47a550', 'MAT', 'Matemáticas', 'Razonamiento cuantitativo y resolución de problemas matemáticos.', NOW()),
('59cc73fb-c114-43b0-8462-7ee55fbd520d', 'LEC', 'Lectura Crítica', 'Comprensión de textos continuos y discontinuos, reflexión y evaluación crítica.', NOW()),
('33aa11bb-1111-4444-8888-111111111111', 'NAT', 'Ciencias Naturales', 'Biología, Química, Física, indagación y explicación de fenómenos.', NOW()),
('44bb22cc-2222-4444-8888-222222222222', 'SOC', 'Sociales y Ciudadanas', 'Pensamiento social, interpretación de perspectivas y competencias ciudadanas.', NOW()),
('55cc33dd-3333-4444-8888-333333333333', 'ING', 'Inglés', 'Comprensión lectora, vocabulario y estructuras gramaticales en inglés.', NOW())
ON DUPLICATE KEY UPDATE `code`=`code`;

-- Preguntas Iniciales
INSERT INTO `questions` (`id`, `subject_id`, `statement`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `explanation`, `score_weight`, `created_at`, `updated_at`) VALUES
('205b4afb-86d1-4f92-bd79-886e8f6f3ce4', 'd118955d-5306-475b-820f-ed091e47a550', 'Una empresa incrementa sus ventas un 20% en el primer trimestre y luego un 10% en el segundo trimestre sobre el valor obtenido. Si las ventas iniciales eran de $1.000.000, ¿cuál es el valor final?', '$1.300.000', '$1.320.000', '$1.250.000', '$1.400.000', 'B', '1.000.000 * 1.20 = 1.200.000. Luego 1.200.000 * 1.10 = 1.320.000.', 1.00, NOW(), NOW()),
('bd84ccc6-4356-4bcd-963f-4bcf1b202ade', '59cc73fb-c114-43b0-8462-7ee55fbd520d', 'En un ensayo argumentativo, la función principal de un contraargumento es:', 'Desviar la atención del lector hacia un tema secundario.', 'Demostrar que el autor desconoce la postura contraria.', 'Anticipar posibles objeciones para refutarlas y fortalecer la tesis.', 'Concluir el texto de manera emotiva.', 'C', 'El contraargumento anticipa objeciones de la postura opuesta para desmontarlas analíticamente.', 1.00, NOW(), NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Simulacro Diagnóstico Publicado
INSERT INTO `exams` (`id`, `title`, `description`, `duration_minutes`, `is_published`, `created_at`, `updated_at`)
VALUES (
    '0ca01e12-95c2-4e0e-bf48-33ae7c6a4449',
    'Simulacro Diagnóstico ICFES 2026 - Fase Inicial',
    'Simulacro de evaluación para medir el nivel de preparación en Matemáticas y Lectura Crítica.',
    45,
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `exam_questions` (`id`, `exam_id`, `question_id`, `order_index`) VALUES
('5ea26200-344d-4f7b-a270-020e61b97785', '0ca01e12-95c2-4e0e-bf48-33ae7c6a4449', '205b4afb-86d1-4f92-bd79-886e8f6f3ce4', 1),
('8d50b2ef-9009-4195-a843-54c6e78c90f9', '0ca01e12-95c2-4e0e-bf48-33ae7c6a4449', 'bd84ccc6-4356-4bcd-963f-4bcf1b202ade', 2)
ON DUPLICATE KEY UPDATE `id`=`id`;

SET FOREIGN_KEY_CHECKS = 1;
