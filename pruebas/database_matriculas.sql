-- ===================================================================
-- SISTEMA COMPLETO DE MATRÍCULA DIGITAL - JEAN PIAGET SCHOOL 2026
-- ===================================================================

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
