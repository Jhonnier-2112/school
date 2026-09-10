<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Router;
use App\Services\DatabaseSeeder;
use App\Services\EmailService;
use App\Services\StorageService;

class AdminController {
    private \PDO $db;
    private array $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/config.php';
        require_once __DIR__ . '/../../config/database.php';
        $this->db = getDBConnection($this->config);
        try {
            DatabaseSeeder::ensureColumnsExist($this->db);
        } catch (\Throwable $e) {}
    }

    /**
     * POST /api/v1/admin/students/invite
     * Crea el estudiante sin contraseña y envía email de activación.
     */
    public function inviteStudent(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        $input    = Router::getJsonInput();
        $fullName = trim($input['full_name'] ?? '');
        $email    = strtolower(trim($input['email'] ?? ''));
        $phone    = trim($input['phone'] ?? '');

        if (empty($fullName) || empty($email)) {
            Router::json(400, false, 'El nombre completo y el correo son obligatorios');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Router::json(400, false, 'El correo electrónico no es válido');
        }

        // Verificar que no exista
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Router::json(409, false, "El correo '$email' ya está registrado");
        }

        // Crear usuario sin contraseña (cuenta inactiva hasta que la active)
        $userId          = DatabaseSeeder::uuid();
        $activationToken = bin2hex(random_bytes(32));
        $tokenExpiry     = date('Y-m-d H:i:s', strtotime('+' . $this->config['activation_token_expiry_hours'] . ' hours'));
        $now             = date('Y-m-d H:i:s');

        $this->db->prepare("
            INSERT INTO users
                (id, full_name, email, phone, password_hash, role, is_active,
                 activation_token, activation_token_expires_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, '', 'student', 0, ?, ?, ?, ?)
        ")->execute([$userId, $fullName, $email, $phone, $activationToken, $tokenExpiry, $now, $now]);

        // Auto-inscribir en el curso principal (estado: pending_contract)
        $stmtCourse = $this->db->query("SELECT id FROM courses WHERE is_active = 1 LIMIT 1");
        $courseId   = $stmtCourse->fetchColumn();
        if ($courseId) {
            $this->db->prepare("
                INSERT INTO enrollments (id, user_id, course_id, status, enrolled_at, created_at, updated_at)
                VALUES (?, ?, ?, 'pending_contract', ?, ?, ?)
            ")->execute([DatabaseSeeder::uuid(), $userId, $courseId, $now, $now, $now]);
        }

        // Enviar correo de activación
        $emailService = new EmailService($this->config);
        $sent = $emailService->sendActivationEmail(
            $email,
            $fullName,
            $activationToken,
            $this->config['base_url']
        );

        $activationLink = rtrim($this->config['base_url'], '/') . '/activar-cuenta?token=' . urlencode($activationToken);

        Router::json(201, true, $sent
            ? "Estudiante registrado. Se envió el correo de activación a $email"
            : "Estudiante registrado. (Nota: configura las credenciales SMTP en .env para el envío automático).",
            [
                'user_id'         => $userId,
                'full_name'       => $fullName,
                'email'           => $email,
                'email_sent'      => $sent,
                'token_expiry'    => $tokenExpiry,
                'activation_link' => $activationLink,
            ]
        );
    }

    public function getDashboard(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        // Total de estudiantes registrados
        $totalStudents = (int)$this->db
            ->query("SELECT COUNT(*) FROM users WHERE role = 'student'")
            ->fetchColumn();

        // Total de ingresos recibidos (solo pagos activos)
        $totalRevenue = (float)$this->db
            ->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE is_active = 1")
            ->fetchColumn();

        // Pagos pendientes: precio_curso × estudiantes activos − ingresos recibidos
        $coursePrice = (float)$this->config['course_price'];
        $activeEnrollments = (int)$this->db
            ->query("SELECT COUNT(*) FROM enrollments WHERE status = 'active'")
            ->fetchColumn();
        $pendingPayments = max(0.0, ($coursePrice * $activeEnrollments) - $totalRevenue);

        // Simulacros completados (sesiones enviadas)
        $completedExams = (int)$this->db
            ->query("SELECT COUNT(*) FROM student_exams WHERE status = 'submitted'")
            ->fetchColumn();

        // Documentos pendientes de revisión (extra info)
        $pendingDocs = (int)$this->db
            ->query("SELECT COUNT(*) FROM identification_documents WHERE status = 'pending'")
            ->fetchColumn();

        Router::json(200, true, 'Métricas del dashboard', [
            'total_students'   => $totalStudents,
            'total_revenue'    => $totalRevenue,
            'pending_payments' => $pendingPayments,
            'completed_exams'  => $completedExams,
            'pending_documents'=> $pendingDocs,
        ]);
    }

    public function listStudents(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        $search = trim($_GET['search'] ?? '');
        $role   = trim($_GET['role'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $limit  = max(1, (int)($_GET['limit'] ?? 50));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $where = [];
        $params = [];

        if ($role !== '' && $role !== 'all') {
            $where[] = "role = ?";
            $params[] = $role;
        }

        if ($status === 'active') {
            $where[] = "is_active = 1";
        } elseif ($status === 'inactive' || $status === 'disabled') {
            $where[] = "is_active = 0";
        }

        if ($search !== '') {
            $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $s = "%$search%";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $countSql = "SELECT COUNT(*) FROM users $whereSql";
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $sql = "SELECT id, full_name, email, phone, role, is_active, created_at, updated_at FROM users $whereSql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll();

        Router::json(200, true, 'Usuarios obtenidos', [
            'students' => $students,
            'users'    => $students,
            'total'    => $total,
            'limit'    => $limit,
            'offset'   => $offset
        ]);
    }

    public function getStudentDetail(array $params): void {
        $this->getUserDetail($params);
    }

    public function getUserDetail(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $userId = $params['id'] ?? '';

        $user = null;
        try {
            $stmt = $this->db->prepare("SELECT id, full_name, email, phone, role, is_active, created_at, updated_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } catch (\Throwable $e) {
            try {
                $stmt = $this->db->prepare("SELECT id, full_name, email, phone, role, 1 as is_active, created_at, updated_at FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            } catch (\Throwable $e2) {}
        }

        if (!$user) {
            Router::json(404, false, 'Usuario no encontrado');
        }

        // Get course summary
        $enrollment = null;
        try {
            $stmtEnroll = $this->db->prepare("
                SELECT e.*, c.name as course_name, c.total_price 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                WHERE e.user_id = ? 
                ORDER BY e.created_at DESC LIMIT 1
            ");
            $stmtEnroll->execute([$userId]);
            $enrollment = $stmtEnroll->fetch();
        } catch (\Throwable $e) {}

        $coursePrice = $enrollment ? (float)$enrollment['total_price'] : (float)$this->config['course_price'];

        $payments = [];
        try {
            $stmtPay = $this->db->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC");
            $stmtPay->execute([$userId]);
            $payments = $stmtPay->fetchAll() ?: [];
        } catch (\Throwable $e) {}

        $totalPaid = 0.0;
        foreach ($payments as $p) {
            if ((int)($p['is_active'] ?? 1) === 1) {
                $totalPaid += (float)$p['amount'];
            }
        }
        $remaining = max(0.0, $coursePrice - $totalPaid);
        $percentage = $coursePrice > 0 ? round(($totalPaid / $coursePrice) * 100, 2) : 0.0;

        // Get identification document
        $document = null;
        try {
            $stmtDoc = $this->db->prepare("SELECT * FROM identification_documents WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmtDoc->execute([$userId]);
            $document = $stmtDoc->fetch();
        } catch (\Throwable $e) {}

        // Get digital matricula if exists
        $schoolMatricula = null;
        try {
            $stmtMat = $this->db->prepare("SELECT id, code, target_grade, enrollment_type, status, created_at FROM school_enrollments WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmtMat->execute([$userId]);
            $schoolMatricula = $stmtMat->fetch();
        } catch (\Throwable $e) {
            $schoolMatricula = null;
        }

        Router::json(200, true, 'Detalle del usuario obtenido', [
            'user'             => $user,
            'student'          => $user,
            'payment_summary'  => [
                'course_price'     => $coursePrice,
                'total_paid'       => $totalPaid,
                'remaining_amount' => $remaining,
                'percentage_paid'  => $percentage,
                'payments'         => $payments
            ],
            'document'         => $document ?: null,
            'school_matricula' => $schoolMatricula ?: null
        ]);
    }

    public function updateUser(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $userId = $params['id'] ?? '';
        $input  = Router::getJsonInput();

        $fullName = trim($input['full_name'] ?? '');
        $email    = strtolower(trim($input['email'] ?? ''));
        $phone    = trim($input['phone'] ?? '');
        $role     = trim($input['role'] ?? '');
        $isActive = isset($input['is_active']) ? (int)(bool)$input['is_active'] : null;
        $password = trim($input['password'] ?? '');

        if (empty($fullName) || empty($email)) {
            Router::json(400, false, 'El nombre completo y el correo electrónico son obligatorios');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Router::json(400, false, 'El formato de correo electrónico no es válido');
        }

        // Verificar que el usuario exista
        $stmt = $this->db->prepare("SELECT id, role, is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();
        if (!$existing) {
            Router::json(404, false, 'Usuario no encontrado');
        }

        // Verificar email duplicado en otro usuario
        $stmtDup = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtDup->execute([$email, $userId]);
        if ($stmtDup->fetch()) {
            Router::json(409, false, "El correo '$email' ya pertenece a otro usuario registrado");
        }

        $now = date('Y-m-d H:i:s');
        $fields = [
            'full_name = ?',
            'email = ?',
            'phone = ?',
            'updated_at = ?'
        ];
        $values = [$fullName, $email, $phone, $now];

        if (in_array($role, ['student', 'admin'], true)) {
            $fields[] = 'role = ?';
            $values[] = $role;
        }

        if ($isActive !== null) {
            $fields[] = 'is_active = ?';
            $values[] = $isActive;
        }

        if (!empty($password)) {
            if (strlen($password) < 6) {
                Router::json(400, false, 'La nueva contraseña debe tener mínimo 6 caracteres');
            }
            $fields[] = 'password_hash = ?';
            $values[] = password_hash($password, PASSWORD_BCRYPT);
        }

        $values[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->prepare($sql)->execute($values);

        // Retornar usuario actualizado
        $stmtUpdated = $this->db->prepare("SELECT id, full_name, email, phone, role, is_active, updated_at FROM users WHERE id = ?");
        $stmtUpdated->execute([$userId]);
        $updatedUser = $stmtUpdated->fetch();

        Router::json(200, true, 'Usuario actualizado exitosamente', $updatedUser);
    }

    public function disableUser(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $userId = $params['id'] ?? '';

        $stmt = $this->db->prepare("SELECT id, full_name, email, is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Router::json(404, false, 'Usuario no encontrado');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->prepare("UPDATE users SET is_active = 0, updated_at = ? WHERE id = ?")
                 ->execute([$now, $userId]);

        Router::json(200, true, 'Usuario deshabilitado exitosamente (soft delete)', [
            'id'        => $userId,
            'is_active' => 0
        ]);
    }

    public function toggleUserStatus(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $userId = $params['id'] ?? '';
        $input  = Router::getJsonInput();

        $stmt = $this->db->prepare("SELECT id, is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Router::json(404, false, 'Usuario no encontrado');
        }

        $newStatus = isset($input['is_active']) ? (int)(bool)$input['is_active'] : ((int)$user['is_active'] === 1 ? 0 : 1);
        $now = date('Y-m-d H:i:s');

        $this->db->prepare("UPDATE users SET is_active = ?, updated_at = ? WHERE id = ?")
                 ->execute([$newStatus, $now, $userId]);

        Router::json(200, true, $newStatus === 1 ? 'Usuario habilitado exitosamente' : 'Usuario deshabilitado exitosamente', [
            'id'        => $userId,
            'is_active' => $newStatus
        ]);
    }

    public function registerPayment(): void {
        $admin = AuthMiddleware::authenticate($this->config, 'admin');
        $input = Router::getJsonInput();

        $userId = $input['user_id'] ?? '';
        $amount = (float)($input['amount'] ?? 0);
        $method = $input['payment_method'] ?? 'transfer';
        $receipt = $input['receipt_number'] ?? '';
        $notes = $input['notes'] ?? '';

        if (empty($userId) || $amount <= 0) {
            Router::json(400, false, 'user_id y amount mayor a 0 son obligatorios');
        }

        // Get enrollment
        $stmt = $this->db->prepare("SELECT id FROM enrollments WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $enrollmentId = $stmt->fetchColumn();

        if (!$enrollmentId) {
            $enrollmentId = DatabaseSeeder::uuid();
            $stmtCourse = $this->db->query("SELECT id FROM courses LIMIT 1");
            $courseId = $stmtCourse->fetchColumn();
            $now = date('Y-m-d H:i:s');
            $this->db->prepare("INSERT INTO enrollments (id, user_id, course_id, status, enrolled_at, created_at, updated_at) VALUES (?, ?, ?, 'active', ?, ?, ?)")
                     ->execute([$enrollmentId, $userId, $courseId, $now, $now, $now]);
        }

        $paymentId = DatabaseSeeder::uuid();
        $now = date('Y-m-d H:i:s');

        $stmtInsert = $this->db->prepare("
            INSERT INTO payments (id, user_id, enrollment_id, amount, payment_date, receipt_number, payment_method, notes, registered_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$paymentId, $userId, $enrollmentId, $amount, $now, $receipt, $method, $notes, $admin['user_id'], $now, $now]);

        Router::json(201, true, 'Abono registrado exitosamente', [
            'id'             => $paymentId,
            'user_id'        => $userId,
            'amount'         => $amount,
            'receipt_number' => $receipt,
            'payment_method' => $method,
            'notes'          => $notes,
            'payment_date'   => $now
        ]);
    }

    public function listPayments(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $where = [];
        $params = [];

        $hasIsActive = true;
        try {
            $cols = $this->db->query('DESCRIBE payments')->fetchAll(\PDO::FETCH_COLUMN);
            $hasIsActive = in_array('is_active', $cols);
        } catch (\Throwable $e) {}

        if ($hasIsActive) {
            if ($status === 'active') {
                $where[] = "p.is_active = 1";
            } elseif ($status === 'disabled' || $status === 'cancelled' || $status === 'inactive') {
                $where[] = "p.is_active = 0";
            }
        }

        if ($search !== '') {
            $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR p.receipt_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name as student_name, u.email as student_email
            FROM payments p
            JOIN users u ON p.user_id = u.id
            $whereSql
            ORDER BY p.payment_date DESC
            LIMIT ? OFFSET ?
        ");
        foreach ($params as $i => $val) {
            $stmt->bindValue($i + 1, $val);
        }
        $stmt->bindValue(count($params) + 1, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $payments = $stmt->fetchAll();

        Router::json(200, true, 'Lista de abonos', $payments);
    }

    public function getPaymentDetail(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $paymentId = $params['id'] ?? '';

        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name as student_name, u.email as student_email, u.phone as student_phone,
                   reg.full_name as registered_by_name
            FROM payments p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN users reg ON p.registered_by = reg.id
            WHERE p.id = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            Router::json(404, false, 'Abono no encontrado');
        }

        Router::json(200, true, 'Detalle de abono obtenido', $payment);
    }

    public function updatePayment(array $params): void {
        $admin = AuthMiddleware::authenticate($this->config, 'admin');
        $paymentId = $params['id'] ?? '';
        $input = Router::getJsonInput();

        $stmt = $this->db->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            Router::json(404, false, 'Abono no encontrado');
        }

        $amount   = isset($input['amount']) ? (float)$input['amount'] : (float)$existing['amount'];
        $date     = !empty($input['payment_date']) ? $input['payment_date'] : $existing['payment_date'];
        $method   = !empty($input['payment_method']) ? $input['payment_method'] : $existing['payment_method'];
        $receipt  = isset($input['receipt_number']) ? trim($input['receipt_number']) : $existing['receipt_number'];
        $notes    = isset($input['notes']) ? trim($input['notes']) : $existing['notes'];
        $isActive = isset($input['is_active']) ? (int)(bool)$input['is_active'] : (int)($existing['is_active'] ?? 1);

        if ($amount <= 0) {
            Router::json(400, false, 'El monto del abono debe ser mayor a 0');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->prepare("
            UPDATE payments 
            SET amount = ?, payment_date = ?, payment_method = ?, receipt_number = ?, notes = ?, is_active = ?, updated_at = ?
            WHERE id = ?
        ")->execute([$amount, $date, $method, $receipt, $notes, $isActive, $now, $paymentId]);

        $stmtUpdated = $this->db->prepare("
            SELECT p.*, u.full_name as student_name, u.email as student_email
            FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmtUpdated->execute([$paymentId]);
        $updated = $stmtUpdated->fetch();

        Router::json(200, true, 'Abono actualizado exitosamente', $updated);
    }

    public function disablePayment(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $paymentId = $params['id'] ?? '';

        $stmt = $this->db->prepare("SELECT id FROM payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        if (!$stmt->fetch()) {
            Router::json(404, false, 'Abono no encontrado');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->prepare("UPDATE payments SET is_active = 0, updated_at = ? WHERE id = ?")
                 ->execute([$now, $paymentId]);

        Router::json(200, true, 'Abono deshabilitado/anulado exitosamente (soft delete)', [
            'id'        => $paymentId,
            'is_active' => 0
        ]);
    }

    public function togglePaymentStatus(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $paymentId = $params['id'] ?? '';
        $input = Router::getJsonInput();

        $stmt = $this->db->prepare("SELECT id, is_active FROM payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            Router::json(404, false, 'Abono no encontrado');
        }

        $current = (int)($existing['is_active'] ?? 1);
        $newStatus = isset($input['is_active']) ? (int)(bool)$input['is_active'] : ($current === 1 ? 0 : 1);
        $now = date('Y-m-d H:i:s');

        $this->db->prepare("UPDATE payments SET is_active = ?, updated_at = ? WHERE id = ?")
                 ->execute([$newStatus, $now, $paymentId]);

        Router::json(200, true, $newStatus === 1 ? 'Abono reactivado exitosamente' : 'Abono anulado/deshabilitado exitosamente', [
            'id'        => $paymentId,
            'is_active' => $newStatus
        ]);
    }

    public function listPendingDocuments(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        $limit  = max(1, (int)($_GET['limit'] ?? 20));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $total = (int)$this->db->query("SELECT COUNT(*) FROM identification_documents WHERE status = 'pending'")->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name as student_name, u.email as student_email, u.phone as student_phone 
            FROM identification_documents d 
            JOIN users u ON d.user_id = u.id 
            WHERE d.status = 'pending' 
            ORDER BY d.created_at ASC 
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute();
        $docs = $stmt->fetchAll();

        Router::json(200, true, 'Documentos pendientes', [
            'documents' => $docs,
            'total'     => $total,
            'limit'     => $limit,
            'offset'    => $offset
        ]);
    }

    public function reviewDocument(array $params): void {
        $admin = AuthMiddleware::authenticate($this->config, 'admin');
        $docId = $params['id'] ?? '';
        $input = Router::getJsonInput();

        $status = $input['status'] ?? '';
        $reason = trim($input['rejection_reason'] ?? '');

        if (!in_array($status, ['approved', 'rejected'])) {
            Router::json(400, false, 'El estado debe ser "approved" o "rejected"');
        }

        if ($status === 'rejected' && empty($reason)) {
            Router::json(400, false, 'Debe especificar el motivo del rechazo');
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            UPDATE identification_documents 
            SET status = ?, rejection_reason = ?, reviewed_by = ?, reviewed_at = ?, updated_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $reason, $admin['user_id'], $now, $now, $docId]);

        Router::json(200, true, 'Documento revisado exitosamente', [
            'id'               => $docId,
            'status'           => $status,
            'rejection_reason' => $reason,
            'reviewed_at'      => $now
        ]);
    }

    public function listSubjects(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        $stmt = $this->db->query("SELECT * FROM subjects ORDER BY name ASC");
        Router::json(200, true, 'Materias ICFES', $stmt->fetchAll());
    }

    public function createQuestion(): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $input = Router::getJsonInput();

        $subjectId   = $input['subject_id'] ?? '';
        $statement   = trim($input['statement'] ?? '');
        $type        = $input['question_type'] ?? 'multiple_choice';
        $imageUrl    = $input['image_url'] ?? null;
        $explanation = trim($input['explanation'] ?? '');
        $scoreType   = in_array($input['score_type'] ?? '', ['points', 'percentage']) ? $input['score_type'] : 'points';
        $weight      = max(0.01, (float)($input['score_weight'] ?? 1.0));
        $timeLimit   = max(0, (int)($input['time_limit_seconds'] ?? 0));
        $difficulty  = in_array($input['difficulty'] ?? '', ['easy', 'medium', 'hard']) ? $input['difficulty'] : 'medium';

        if (empty($subjectId) || empty($statement)) {
            Router::json(400, false, 'La materia y el enunciado de la pregunta son obligatorios.');
        }

        $optA = null;
        $optB = null;
        $optC = null;
        $optD = null;
        $correct = null;
        $correctText = null;

        if ($type === 'multiple_choice') {
            $optA = trim($input['option_a'] ?? '');
            $optB = trim($input['option_b'] ?? '');
            $optC = trim($input['option_c'] ?? '');
            $optD = trim($input['option_d'] ?? '');
            $correct = strtoupper(trim($input['correct_option'] ?? ''));

            if (empty($optA) || empty($optB) || empty($optC) || empty($optD) || !in_array($correct, ['A', 'B', 'C', 'D'])) {
                Router::json(400, false, 'Para selección múltiple, las 4 opciones y la opción correcta (A, B, C, D) son requeridas.');
            }
        } elseif ($type === 'true_false') {
            $optA = 'Verdadero';
            $optB = 'Falso';
            $correct = strtoupper(trim($input['correct_option'] ?? ''));
            if (!in_array($correct, ['A', 'B'])) {
                Router::json(400, false, 'Para preguntas de Verdadero/Falso debe indicar si la respuesta correcta es Verdadero (A) o Falso (B).');
            }
        } elseif ($type === 'open_text') {
            $correctText = trim($input['correct_answer_text'] ?? '');
            if (empty($correctText)) {
                Router::json(400, false, 'Para preguntas de respuesta escrita debe especificar la respuesta correcta o palabras clave esperadas.');
            }
        } else {
            Router::json(400, false, 'Tipo de pregunta inválido.');
        }

        $qId = DatabaseSeeder::uuid();
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO questions (
                id, subject_id, statement, question_type, image_url, 
                option_a, option_b, option_c, option_d, correct_option, correct_answer_text,
                explanation, score_type, score_weight, time_limit_seconds, difficulty,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $qId, $subjectId, $statement, $type, $imageUrl,
            $optA, $optB, $optC, $optD, $correct, $correctText,
            $explanation, $scoreType, $weight, $timeLimit, $difficulty,
            $now, $now
        ]);

        Router::json(201, true, 'Pregunta agregada al banco con éxito', [
            'id'             => $qId,
            'subject_id'     => $subjectId,
            'statement'      => $statement,
            'question_type'  => $type,
            'score_type'     => $scoreType,
            'score_weight'   => $weight,
            'time_limit_sec' => $timeLimit,
            'difficulty'     => $difficulty
        ]);
    }

    public function getQuestion(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $id = $params['id'] ?? '';

        $stmt = $this->db->prepare("
            SELECT q.*, s.name as subject_name, s.code as subject_code 
            FROM questions q 
            JOIN subjects s ON q.subject_id = s.id 
            WHERE q.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $question = $stmt->fetch();

        if (!$question) {
            Router::json(404, false, 'Pregunta no encontrada');
        }

        Router::json(200, true, 'Detalle de pregunta', $question);
    }

    public function updateQuestion(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $id = $params['id'] ?? '';
        $input = Router::getJsonInput();

        $subjectId   = $input['subject_id'] ?? '';
        $statement   = trim($input['statement'] ?? '');
        $type        = $input['question_type'] ?? 'multiple_choice';
        $imageUrl    = $input['image_url'] ?? null;
        $explanation = trim($input['explanation'] ?? '');
        $scoreType   = in_array($input['score_type'] ?? '', ['points', 'percentage']) ? $input['score_type'] : 'points';
        $weight      = max(0.01, (float)($input['score_weight'] ?? 1.0));
        $timeLimit   = max(0, (int)($input['time_limit_seconds'] ?? 0));
        $difficulty  = in_array($input['difficulty'] ?? '', ['easy', 'medium', 'hard']) ? $input['difficulty'] : 'medium';

        if (empty($subjectId) || empty($statement)) {
            Router::json(400, false, 'La materia y el enunciado son obligatorios.');
        }

        $optA = null;
        $optB = null;
        $optC = null;
        $optD = null;
        $correct = null;
        $correctText = null;

        if ($type === 'multiple_choice') {
            $optA = trim($input['option_a'] ?? '');
            $optB = trim($input['option_b'] ?? '');
            $optC = trim($input['option_c'] ?? '');
            $optD = trim($input['option_d'] ?? '');
            $correct = strtoupper(trim($input['correct_option'] ?? ''));

            if (empty($optA) || empty($optB) || empty($optC) || empty($optD) || !in_array($correct, ['A', 'B', 'C', 'D'])) {
                Router::json(400, false, 'Para selección múltiple, las 4 opciones y la opción correcta (A, B, C, D) son requeridas.');
            }
        } elseif ($type === 'true_false') {
            $optA = 'Verdadero';
            $optB = 'Falso';
            $correct = strtoupper(trim($input['correct_option'] ?? ''));
            if (!in_array($correct, ['A', 'B'])) {
                Router::json(400, false, 'Para preguntas de Verdadero/Falso debe indicar la opción correcta (A o B).');
            }
        } elseif ($type === 'open_text') {
            $correctText = trim($input['correct_answer_text'] ?? '');
            if (empty($correctText)) {
                Router::json(400, false, 'Para preguntas de respuesta escrita debe especificar la respuesta correcta esperada.');
            }
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            UPDATE questions SET 
                subject_id = ?, statement = ?, question_type = ?, image_url = ?, 
                option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, correct_answer_text = ?,
                explanation = ?, score_type = ?, score_weight = ?, time_limit_seconds = ?, difficulty = ?,
                updated_at = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $subjectId, $statement, $type, $imageUrl,
            $optA, $optB, $optC, $optD, $correct, $correctText,
            $explanation, $scoreType, $weight, $timeLimit, $difficulty,
            $now, $id
        ]);

        Router::json(200, true, 'Pregunta actualizada con éxito', ['id' => $id]);
    }

    public function deleteQuestion(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $id = $params['id'] ?? '';

        $this->db->prepare("DELETE FROM exam_questions WHERE question_id = ?")->execute([$id]);
        $stmt = $this->db->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$id]);

        Router::json(200, true, 'Pregunta eliminada con éxito');
    }

    public function listQuestions(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        $subjectId   = $_GET['subject_id'] ?? '';
        $type        = $_GET['type'] ?? '';
        $difficulty  = $_GET['difficulty'] ?? '';
        $search      = trim($_GET['search'] ?? '');
        $limit       = max(1, (int)($_GET['limit'] ?? 100));
        $offset      = max(0, (int)($_GET['offset'] ?? 0));

        $sql = "SELECT q.*, s.name as subject_name, s.code as subject_code FROM questions q JOIN subjects s ON q.subject_id = s.id WHERE 1=1";
        $params = [];

        if (!empty($subjectId)) {
            $sql .= " AND q.subject_id = ?";
            $params[] = $subjectId;
        }
        if (!empty($type)) {
            $sql .= " AND q.question_type = ?";
            $params[] = $type;
        }
        if (!empty($difficulty)) {
            $sql .= " AND q.difficulty = ?";
            $params[] = $difficulty;
        }
        if (!empty($search)) {
            $sql .= " AND (q.statement LIKE ? OR q.explanation LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $countSql = preg_replace('/SELECT q\.\*, s\.name as subject_name, s\.code as subject_code/', 'SELECT COUNT(*)', $sql, 1);
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $sql .= " ORDER BY q.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $questions = $stmt->fetchAll();

        Router::json(200, true, 'Banco de preguntas', [
            'questions' => $questions,
            'total'     => $total,
            'limit'     => $limit,
            'offset'    => $offset
        ]);
    }

    public function uploadQuestionImage(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        if (!isset($_FILES['image'])) {
            Router::json(400, false, 'Debe enviar un archivo bajo el campo "image"');
        }

        try {
            $url = StorageService::upload($_FILES['image'], 'questions', $this->config);
            Router::json(201, true, 'Imagen subida con éxito', ['image_url' => $url]);
        } catch (\Exception $e) {
            Router::json(400, false, $e->getMessage());
        }
    }

    public function createExam(): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $input = Router::getJsonInput();

        $title          = trim($input['title'] ?? '');
        $description    = trim($input['description'] ?? '');
        $duration       = (int)($input['duration_minutes'] ?? 60);
        $scoringMode    = in_array($input['scoring_mode'] ?? '', ['points', 'percentage']) ? $input['scoring_mode'] : 'points';
        $timerMode      = in_array($input['timer_mode'] ?? '', ['exam', 'per_question', 'both']) ? $input['timer_mode'] : 'exam';
        $timePerQ       = max(10, (int)($input['time_per_question_seconds'] ?? 60));
        $questionIds    = $input['question_ids'] ?? [];
        $isPublished    = (bool)($input['is_published'] ?? false);

        if (empty($title) || empty($questionIds)) {
            Router::json(400, false, 'Título y al menos una pregunta son requeridos para el simulacro.');
        }

        $examId = DatabaseSeeder::uuid();
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO exams (
                id, title, description, duration_minutes, scoring_mode, 
                timer_mode, time_per_question_seconds, is_published, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $examId, $title, $description, $duration, $scoringMode,
            $timerMode, $timePerQ, $isPublished ? 1 : 0, $now, $now
        ]);

        $stmtEq = $this->db->prepare("INSERT INTO exam_questions (id, exam_id, question_id, order_index) VALUES (?, ?, ?, ?)");
        $order = 1;
        foreach ($questionIds as $qId) {
            $stmtEq->execute([DatabaseSeeder::uuid(), $examId, $qId, $order++]);
        }

        Router::json(201, true, 'Simulacro creado exitosamente', [
            'id'               => $examId,
            'title'            => $title,
            'duration_minutes' => $duration,
            'scoring_mode'     => $scoringMode,
            'timer_mode'       => $timerMode,
            'questions_count'  => count($questionIds),
            'is_published'     => $isPublished
        ]);
    }

    public function getExamDetail(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $id = $params['id'] ?? '';

        $stmt = $this->db->prepare("SELECT * FROM exams WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $exam = $stmt->fetch();

        if (!$exam) {
            Router::json(404, false, 'Simulacro no encontrado');
        }

        $stmtQ = $this->db->prepare("
            SELECT q.*, s.name as subject_name, eq.order_index 
            FROM exam_questions eq 
            JOIN questions q ON eq.question_id = q.id 
            JOIN subjects s ON q.subject_id = s.id 
            WHERE eq.exam_id = ? 
            ORDER BY eq.order_index ASC
        ");
        $stmtQ->execute([$id]);
        $exam['questions'] = $stmtQ->fetchAll();
        $exam['question_ids'] = array_column($exam['questions'], 'id');

        Router::json(200, true, 'Detalle del simulacro', $exam);
    }

    public function updateExam(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $id = $params['id'] ?? '';
        $input = Router::getJsonInput();

        $title          = trim($input['title'] ?? '');
        $description    = trim($input['description'] ?? '');
        $duration       = (int)($input['duration_minutes'] ?? 60);
        $scoringMode    = in_array($input['scoring_mode'] ?? '', ['points', 'percentage']) ? $input['scoring_mode'] : 'points';
        $timerMode      = in_array($input['timer_mode'] ?? '', ['exam', 'per_question', 'both']) ? $input['timer_mode'] : 'exam';
        $timePerQ       = max(10, (int)($input['time_per_question_seconds'] ?? 60));
        $questionIds    = $input['question_ids'] ?? [];
        $isPublished    = isset($input['is_published']) ? ($input['is_published'] ? 1 : 0) : null;

        if (empty($title)) {
            Router::json(400, false, 'El título del simulacro es requerido.');
        }

        $now = date('Y-m-d H:i:s');
        if ($isPublished !== null) {
            $stmt = $this->db->prepare("
                UPDATE exams SET 
                    title = ?, description = ?, duration_minutes = ?, scoring_mode = ?, 
                    timer_mode = ?, time_per_question_seconds = ?, is_published = ?, updated_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $duration, $scoringMode, $timerMode, $timePerQ, $isPublished, $now, $id]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE exams SET 
                    title = ?, description = ?, duration_minutes = ?, scoring_mode = ?, 
                    timer_mode = ?, time_per_question_seconds = ?, updated_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $duration, $scoringMode, $timerMode, $timePerQ, $now, $id]);
        }

        if (is_array($questionIds) && count($questionIds) > 0) {
            $this->db->prepare("DELETE FROM exam_questions WHERE exam_id = ?")->execute([$id]);
            $stmtEq = $this->db->prepare("INSERT INTO exam_questions (id, exam_id, question_id, order_index) VALUES (?, ?, ?, ?)");
            $order = 1;
            foreach ($questionIds as $qId) {
                $stmtEq->execute([DatabaseSeeder::uuid(), $id, $qId, $order++]);
            }
        }

        Router::json(200, true, 'Simulacro actualizado con éxito');
    }

    public function deleteExam(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $id = $params['id'] ?? '';

        $this->db->prepare("DELETE FROM exam_questions WHERE exam_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM exams WHERE id = ?")->execute([$id]);

        Router::json(200, true, 'Simulacro eliminado con éxito');
    }

    public function listAllExams(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        $limit  = max(1, (int)($_GET['limit'] ?? 50));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $total = (int)$this->db->query("SELECT COUNT(*) FROM exams")->fetchColumn();

        $stmt = $this->db->query("SELECT * FROM exams ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        $exams = $stmt->fetchAll();

        foreach ($exams as &$exam) {
            $stmtQ = $this->db->prepare("SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?");
            $stmtQ->execute([$exam['id']]);
            $exam['questions_count'] = (int)$stmtQ->fetchColumn();
        }

        Router::json(200, true, 'Listado de simulacros', [
            'exams'  => $exams,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset
        ]);
    }

    public function publishExam(array $params): void {
        AuthMiddleware::authenticate($this->config, 'admin');
        $examId = $params['id'] ?? '';
        $input = Router::getJsonInput();

        $isPublished = (bool)($input['is_published'] ?? false);
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("UPDATE exams SET is_published = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$isPublished ? 1 : 0, $now, $examId]);

        Router::json(200, true, 'Estado del simulacro actualizado con éxito', [
            'id'           => $examId,
            'is_published' => $isPublished
        ]);
    }

    // ==========================================
    // ESTADÍSTICAS MENSUALES PARA GRÁFICAS
    // ==========================================

    public function getPaymentsByMonth(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        // Últimos 8 meses de ingresos agrupados
        $stmt = $this->db->query("
            SELECT
                DATE_FORMAT(payment_date, '%Y-%m') AS month_key,
                DATE_FORMAT(payment_date, '%b')    AS month_label,
                COALESCE(SUM(amount), 0)           AS total
            FROM payments
            WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 8 MONTH)
            GROUP BY month_key, month_label
            ORDER BY month_key ASC
            LIMIT 8
        ");
        $rows = $stmt ? $stmt->fetchAll() : [];

        // Normalizar a floats
        $data = array_map(fn($r) => [
            'month' => $r['month_label'],
            'total' => (float)$r['total'],
        ], $rows);

        Router::json(200, true, 'Pagos por mes', ['months' => $data]);
    }

    public function getStudentsByMonth(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        // Últimos 8 meses de registros de estudiantes
        $stmt = $this->db->query("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                DATE_FORMAT(created_at, '%b')    AS month_label,
                COUNT(*)                          AS total
            FROM users
            WHERE role = 'student'
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 8 MONTH)
            GROUP BY month_key, month_label
            ORDER BY month_key ASC
            LIMIT 8
        ");
        $rows = $stmt ? $stmt->fetchAll() : [];

        $data = array_map(fn($r) => [
            'month' => $r['month_label'],
            'total' => (int)$r['total'],
        ], $rows);

        Router::json(200, true, 'Estudiantes por mes', ['months' => $data]);
    }
}

