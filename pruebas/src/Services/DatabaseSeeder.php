<?php

namespace App\Services;

use PDO;

class DatabaseSeeder {
    public static function run(PDO $pdo, array $config): void {
        // Create tables if they do not exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id CHAR(36) PRIMARY KEY,
                full_name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(30),
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'student',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS courses (
                id CHAR(36) PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                description TEXT,
                total_price DECIMAL(12,2) NOT NULL DEFAULT 700000.00,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS enrollments (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                course_id CHAR(36) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending_contract',
                enrolled_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_course_id (course_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS contracts (
                id CHAR(36) PRIMARY KEY,
                course_id CHAR(36) NOT NULL,
                version VARCHAR(20) NOT NULL DEFAULT '1.0',
                title VARCHAR(200) NOT NULL,
                content TEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_course_id (course_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS contract_signatures (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                contract_id CHAR(36) NOT NULL,
                ip_address VARCHAR(64),
                user_agent VARCHAR(255),
                signed_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_contract_id (contract_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS payments (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                enrollment_id CHAR(36) NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                payment_date DATETIME NOT NULL,
                receipt_number VARCHAR(64),
                payment_method VARCHAR(30) NOT NULL DEFAULT 'transfer',
                notes VARCHAR(255),
                registered_by CHAR(36),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_enrollment_id (enrollment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS identification_documents (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                document_type VARCHAR(20) NOT NULL DEFAULT 'CC',
                document_number VARCHAR(50),
                file_url VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                rejection_reason VARCHAR(255),
                reviewed_by CHAR(36),
                reviewed_at DATETIME,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS subjects (
                id CHAR(36) PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                code VARCHAR(30) NOT NULL UNIQUE,
                description VARCHAR(255),
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS questions (
                id CHAR(36) PRIMARY KEY,
                subject_id CHAR(36) NOT NULL,
                statement TEXT NOT NULL,
                question_type VARCHAR(30) NOT NULL DEFAULT 'multiple_choice',
                image_url VARCHAR(255),
                option_a TEXT,
                option_b TEXT,
                option_c TEXT,
                option_d TEXT,
                correct_option VARCHAR(2),
                correct_answer_text TEXT,
                explanation TEXT,
                score_type VARCHAR(20) NOT NULL DEFAULT 'points',
                score_weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                time_limit_seconds INT NOT NULL DEFAULT 0,
                difficulty VARCHAR(20) NOT NULL DEFAULT 'medium',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_subject_id (subject_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS exams (
                id CHAR(36) PRIMARY KEY,
                title VARCHAR(150) NOT NULL,
                description TEXT,
                duration_minutes INT NOT NULL DEFAULT 60,
                scoring_mode VARCHAR(20) NOT NULL DEFAULT 'points',
                timer_mode VARCHAR(20) NOT NULL DEFAULT 'exam',
                time_per_question_seconds INT NOT NULL DEFAULT 60,
                is_published TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS exam_questions (
                id CHAR(36) PRIMARY KEY,
                exam_id CHAR(36) NOT NULL,
                question_id CHAR(36) NOT NULL,
                order_index INT NOT NULL DEFAULT 0,
                INDEX idx_exam_id (exam_id),
                INDEX idx_question_id (question_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS student_exams (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                exam_id CHAR(36) NOT NULL,
                started_at DATETIME NOT NULL,
                submitted_at DATETIME,
                total_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                max_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(20) NOT NULL DEFAULT 'in_progress',
                created_at DATETIME NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_exam_id (exam_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS student_answers (
                id CHAR(36) PRIMARY KEY,
                student_exam_id CHAR(36) NOT NULL,
                question_id CHAR(36) NOT NULL,
                selected_option VARCHAR(2),
                answer_text TEXT,
                is_correct TINYINT(1) NOT NULL DEFAULT 0,
                score_earned DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                created_at DATETIME NOT NULL,
                INDEX idx_student_exam_id (student_exam_id),
                INDEX idx_question_id (question_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS students (
                id CHAR(36) PRIMARY KEY,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                birth_date DATE NOT NULL,
                birth_place VARCHAR(150) NOT NULL,
                age INT NOT NULL,
                doc_type VARCHAR(20) NOT NULL DEFAULT 'TI',
                doc_number VARCHAR(50) NOT NULL UNIQUE,
                doc_issue_place VARCHAR(150) NOT NULL,
                eps VARCHAR(100) NOT NULL,
                rh VARCHAR(10) NOT NULL,
                lives_with_parents VARCHAR(10) NOT NULL DEFAULT 'SI',
                lives_with_whom VARCHAR(150) DEFAULT NULL,
                address VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                email VARCHAR(150) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_student_doc (doc_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS parents (
                id CHAR(36) PRIMARY KEY,
                parent_type ENUM('PADRE', 'MADRE') NOT NULL,
                full_name VARCHAR(150) NOT NULL,
                doc_type VARCHAR(20) NOT NULL DEFAULT 'CC',
                doc_number VARCHAR(50) NOT NULL,
                doc_issue_place VARCHAR(150) DEFAULT NULL,
                occupation VARCHAR(150) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                email VARCHAR(150) NOT NULL,
                address VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_parent_doc (doc_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS guardians (
                id CHAR(36) PRIMARY KEY,
                full_name VARCHAR(150) NOT NULL,
                doc_type VARCHAR(20) NOT NULL DEFAULT 'CC',
                doc_number VARCHAR(50) NOT NULL,
                doc_issue_place VARCHAR(150) DEFAULT NULL,
                relationship VARCHAR(50) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                email VARCHAR(150) NOT NULL,
                address VARCHAR(255) NOT NULL,
                occupation VARCHAR(150) DEFAULT NULL,
                is_parent ENUM('PADRE', 'MADRE', 'OTRO') DEFAULT 'OTRO',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_guardian_doc (doc_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS school_enrollments (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                student_id CHAR(36) NOT NULL,
                father_id CHAR(36) DEFAULT NULL,
                mother_id CHAR(36) DEFAULT NULL,
                guardian_id CHAR(36) NOT NULL,
                code VARCHAR(30) UNIQUE NOT NULL,
                enrollment_type VARCHAR(50) NOT NULL,
                target_grade VARCHAR(50) NOT NULL,
                school_year INT NOT NULL DEFAULT 2026,
                status ENUM('DRAFT', 'PENDING_REVIEW', 'DOCUMENTS_PENDING', 'SIGNATURE_PENDING', 'SIGNED', 'APPROVED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
                current_step INT NOT NULL DEFAULT 1,
                terms_accepted TINYINT(1) NOT NULL DEFAULT 0,
                data_processing_accepted TINYINT(1) NOT NULL DEFAULT 0,
                observations TEXT DEFAULT NULL,
                rejection_reason TEXT DEFAULT NULL,
                submitted_at DATETIME DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                approved_by CHAR(36) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_enroll_user (user_id),
                INDEX idx_enroll_student (student_id),
                INDEX idx_enroll_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS enrollment_academic_history (
                id CHAR(36) PRIMARY KEY,
                enrollment_id CHAR(36) NOT NULL,
                previous_school VARCHAR(200) NOT NULL,
                previous_grade VARCHAR(50) NOT NULL,
                previous_year INT NOT NULL,
                academic_notes TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_academic_enrollment (enrollment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS enrollment_economics (
                id CHAR(36) PRIMARY KEY,
                enrollment_id CHAR(36) NOT NULL,
                enrollment_fee DECIMAL(12,2) NOT NULL DEFAULT 180000.00,
                monthly_fee DECIMAL(12,2) NOT NULL DEFAULT 150000.00,
                installments_count INT NOT NULL DEFAULT 10,
                total_tuition DECIMAL(12,2) NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'Mensual',
                notes TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_economics_enrollment (enrollment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS enrollment_documents (
                id CHAR(36) PRIMARY KEY,
                enrollment_id CHAR(36) NOT NULL,
                document_type VARCHAR(50) NOT NULL,
                title VARCHAR(200) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                file_url VARCHAR(255) NOT NULL,
                file_hash VARCHAR(64) NOT NULL,
                version INT NOT NULL DEFAULT 1,
                is_signed TINYINT(1) NOT NULL DEFAULT 0,
                generated_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_doc_enrollment (enrollment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS electronic_signatures (
                id CHAR(36) PRIMARY KEY,
                enrollment_id CHAR(36) NOT NULL,
                signer_user_id CHAR(36) NOT NULL,
                signer_name VARCHAR(150) NOT NULL,
                signer_doc_type VARCHAR(20) NOT NULL,
                signer_doc_number VARCHAR(50) NOT NULL,
                signer_role VARCHAR(50) NOT NULL,
                signature_data LONGTEXT NOT NULL,
                ip_address VARCHAR(64) NOT NULL,
                user_agent TEXT NOT NULL,
                device_info VARCHAR(255) DEFAULT NULL,
                hash_verification VARCHAR(64) NOT NULL,
                signed_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_sig_enrollment (enrollment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS document_versions (
                id CHAR(36) PRIMARY KEY,
                document_id CHAR(36) NOT NULL,
                version_number INT NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                change_summary VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_doc_versions (document_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS enrollment_audit_logs (
                id CHAR(36) PRIMARY KEY,
                enrollment_id CHAR(36) NOT NULL,
                user_id CHAR(36) DEFAULT NULL,
                action VARCHAR(100) NOT NULL,
                previous_status VARCHAR(50) DEFAULT NULL,
                new_status VARCHAR(50) DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                ip_address VARCHAR(64) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_audit_enrollment (enrollment_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Auto-migraciones para columnas añadidas
        try {
            $colsQ = $pdo->query('DESCRIBE questions')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('question_type', $colsQ)) {
                $pdo->exec("ALTER TABLE questions ADD COLUMN question_type VARCHAR(30) NOT NULL DEFAULT 'multiple_choice' AFTER statement");
            }
            if (!in_array('score_type', $colsQ)) {
                $pdo->exec("ALTER TABLE questions ADD COLUMN score_type VARCHAR(20) NOT NULL DEFAULT 'points' AFTER explanation");
            }
            if (!in_array('time_limit_seconds', $colsQ)) {
                $pdo->exec("ALTER TABLE questions ADD COLUMN time_limit_seconds INT NOT NULL DEFAULT 0 AFTER score_weight");
            }
            if (!in_array('correct_answer_text', $colsQ)) {
                $pdo->exec("ALTER TABLE questions ADD COLUMN correct_answer_text TEXT DEFAULT NULL AFTER correct_option");
            }
            if (!in_array('difficulty', $colsQ)) {
                $pdo->exec("ALTER TABLE questions ADD COLUMN difficulty VARCHAR(20) NOT NULL DEFAULT 'medium' AFTER time_limit_seconds");
            }

            $colsE = $pdo->query('DESCRIBE exams')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('scoring_mode', $colsE)) {
                $pdo->exec("ALTER TABLE exams ADD COLUMN scoring_mode VARCHAR(20) NOT NULL DEFAULT 'points' AFTER duration_minutes");
            }
            if (!in_array('timer_mode', $colsE)) {
                $pdo->exec("ALTER TABLE exams ADD COLUMN timer_mode VARCHAR(20) NOT NULL DEFAULT 'exam' AFTER scoring_mode");
            }
            if (!in_array('time_per_question_seconds', $colsE)) {
                $pdo->exec("ALTER TABLE exams ADD COLUMN time_per_question_seconds INT NOT NULL DEFAULT 60 AFTER timer_mode");
            }

            $colsA = $pdo->query('DESCRIBE student_answers')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('answer_text', $colsA)) {
                $pdo->exec("ALTER TABLE student_answers ADD COLUMN answer_text TEXT DEFAULT NULL AFTER selected_option");
            }

            // Asegurar que las opciones de preguntas puedan ser nulas para tipo verdadero/falso y respuesta abierta
            $pdo->exec("ALTER TABLE questions MODIFY option_a TEXT NULL, MODIFY option_b TEXT NULL, MODIFY option_c TEXT NULL, MODIFY option_d TEXT NULL, MODIFY correct_option VARCHAR(2) NULL");
        } catch (\Exception $e) {
            // Si ya existen las columnas o no es necesario, continuar
        }

        $now = date('Y-m-d H:i:s');

        // 1. Seed / Sync Admin User
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$config['initial_admin_email']]);
        $existingAdmin = $stmt->fetch();

        if (!$existingAdmin) {
            $adminId = self::uuid();
            $hash = password_hash($config['initial_admin_password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (id, full_name, email, phone, password_hash, role, is_active, created_at, updated_at)
                VALUES (?, 'Administrador Principal', ?, '3001234567', ?, 'admin', 1, ?, ?)
            ");
            $stmt->execute([$adminId, $config['initial_admin_email'], $hash, $now, $now]);
        } else {
            // Asegurar que la contraseña coincida con INITIAL_ADMIN_PASSWORD de config/.env
            if (!password_verify($config['initial_admin_password'], $existingAdmin['password_hash'])) {
                $newHash = password_hash($config['initial_admin_password'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'admin', is_active = 1, updated_at = ? WHERE id = ?");
                $stmt->execute([$newHash, $now, $existingAdmin['id']]);
            }
        }

        // 2. Seed Default Course
        $stmt = $pdo->query("SELECT id FROM courses LIMIT 1");
        $courseId = $stmt->fetchColumn();
        if (!$courseId) {
            $courseId = self::uuid();
            $stmt = $pdo->prepare("
                INSERT INTO courses (id, name, description, total_price, is_active, created_at, updated_at)
                VALUES (?, 'Curso de Preparación Académica ICFES Saber 11°', 'Programa integral con simulacros y acompañamiento docente.', ?, 1, ?, ?)
            ");
            $stmt->execute([$courseId, $config['course_price'], $now, $now]);
        }

        // 3. Seed Default Contract
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE course_id = ?");
        $stmt->execute([$courseId]);
        if ($stmt->fetchColumn() == 0) {
            $contractId = self::uuid();
            $content = "CONTRATO DE PRESTACIÓN DE SERVICIOS EDUCATIVOS\n\n1. OBJETO: El prestador se compromete a impartir al estudiante el programa de preparación para la prueba de Estado Saber 11° (ICFES).\n2. VALOR Y FORMA DE PAGO: El costo total del curso es de $700.000 COP, los cuales podrán cancelarse en cuotas convenidas o mediante abonos directos antes del inicio de las pruebas oficiales.\n3. COMPROMISO DEL ESTUDIANTE: El estudiante se compromete a cumplir con las actividades pedagógicas, presentar los simulacros programados y suministrar información verídica incluyendo su documento de identidad.\n4. POLÍTICA DE DOCUMENTACIÓN: El estudiante deberá cargar copia legible de su documento de identidad para verificación académica.\n5. ACEPTACIÓN: Al marcar la casilla de aceptación y continuar, el estudiante declara haber leído, comprendido y aceptado la totalidad de las cláusulas aquí descritas.";
            $stmt = $pdo->prepare("
                INSERT INTO contracts (id, course_id, version, title, content, is_active, created_at, updated_at)
                VALUES (?, ?, '1.0', 'Términos y Condiciones del Servicio Educativo', ?, 1, ?, ?)
            ");
            $stmt->execute([$contractId, $courseId, $content, $now, $now]);
        }

        // 4. Seed Official ICFES Subjects
        $subjects = [
            ['MAT', 'Matemáticas', 'Razonamiento cuantitativo y resolución de problemas matemáticos.'],
            ['LEC', 'Lectura Crítica', 'Comprensión de textos continuos y discontinuos, reflexión y evaluación crítica.'],
            ['NAT', 'Ciencias Naturales', 'Biología, Química, Física, indagación y explicación de fenómenos.'],
            ['SOC', 'Sociales y Ciudadanas', 'Pensamiento social, interpretación de perspectivas y competencias ciudadanas.'],
            ['ING', 'Inglés', 'Comprensión lectora, vocabulario y estructuras gramaticales en inglés.']
        ];

        foreach ($subjects as $s) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE code = ?");
            $stmt->execute([$s[0]]);
            if ($stmt->fetchColumn() == 0) {
                $subId = self::uuid();
                $stmt = $pdo->prepare("INSERT INTO subjects (id, code, name, description, created_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$subId, $s[0], $s[1], $s[2], $now]);
            }
        }

        // 5. Seed Initial Questions and Diagnostic Exam
        $stmt = $pdo->query("SELECT COUNT(*) FROM questions");
        if ($stmt->fetchColumn() == 0) {
            $stmtMat = $pdo->prepare("SELECT id FROM subjects WHERE code = 'MAT'");
            $stmtMat->execute();
            $matId = $stmtMat->fetchColumn();

            $stmtLec = $pdo->prepare("SELECT id FROM subjects WHERE code = 'LEC'");
            $stmtLec->execute();
            $lecId = $stmtLec->fetchColumn();

            $q1Id = self::uuid();
            $q2Id = self::uuid();

            $stmt = $pdo->prepare("
                INSERT INTO questions (id, subject_id, statement, option_a, option_b, option_c, option_d, correct_option, explanation, score_weight, created_at, updated_at)
                VALUES (?, ?, 'Una empresa incrementa sus ventas un 20% en el primer trimestre y luego un 10% en el segundo trimestre sobre el valor obtenido. Si las ventas iniciales eran de $1.000.000, ¿cuál es el valor final?', '$1.300.000', '$1.320.000', '$1.250.000', '$1.400.000', 'B', '1.000.000 * 1.20 = 1.200.000. Luego 1.200.000 * 1.10 = 1.320.000.', 1.00, ?, ?)
            ");
            $stmt->execute([$q1Id, $matId, $now, $now]);

            $stmt = $pdo->prepare("
                INSERT INTO questions (id, subject_id, statement, option_a, option_b, option_c, option_d, correct_option, explanation, score_weight, created_at, updated_at)
                VALUES (?, ?, 'En un ensayo argumentativo, la función principal de un contraargumento es:', 'Desviar la atención del lector hacia un tema secundario.', 'Demostrar que el autor desconoce la postura contraria.', 'Anticipar posibles objeciones para refutarlas y fortalecer la tesis.', 'Concluir el texto de manera emotiva.', 'C', 'El contraargumento anticipa objeciones para desmontarlas y dar solidez a la postura defendida.', 1.00, ?, ?)
            ");
            $stmt->execute([$q2Id, $lecId, $now, $now]);

            // Diagnostic Exam
            $examId = self::uuid();
            $stmt = $pdo->prepare("
                INSERT INTO exams (id, title, description, duration_minutes, is_published, created_at, updated_at)
                VALUES (?, 'Simulacro Diagnóstico ICFES 2026 - Fase Inicial', 'Simulacro de evaluación para medir el nivel de preparación en Matemáticas y Lectura Crítica.', 45, 1, ?, ?)
            ");
            $stmt->execute([$examId, $now, $now]);

            $stmt = $pdo->prepare("INSERT INTO exam_questions (id, exam_id, question_id, order_index) VALUES (?, ?, ?, ?)");
            $stmt->execute([self::uuid(), $examId, $q1Id, 1]);
            $stmt->execute([self::uuid(), $examId, $q2Id, 2]);
        }
    }

    public static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
