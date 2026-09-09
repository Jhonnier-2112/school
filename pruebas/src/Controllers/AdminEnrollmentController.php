<?php

namespace App\Controllers;

use PDO;
use Exception;
use App\Middleware\AuthMiddleware;
use App\Models\Enrollment;
use App\Services\PdfDocumentService;
use App\Services\EmailService;
use App\Router;

class AdminEnrollmentController {
    private PDO $pdo;
    private array $config;
    private PdfDocumentService $pdfService;
    private EmailService $emailService;

    public function __construct(?PDO $pdo = null, ?array $config = null) {
        $this->config = $config ?? require __DIR__ . '/../../config/config.php';
        if ($pdo !== null) {
            $this->pdo = $pdo;
        } else {
            require_once __DIR__ . '/../../config/database.php';
            $this->pdo = getDBConnection($this->config);
        }
        $this->pdfService = new PdfDocumentService($this->pdo, $this->config);
        $this->emailService = new EmailService($this->config);
    }

    /**
     * Dashboard con métricas y estadísticas consolidadas.
     * GET /api/v1/admin/enrollments/statistics
     */
    public function getStatistics(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        // Totales por estado
        $stmt = $this->pdo->query("
            SELECT status, COUNT(*) as count 
            FROM school_enrollments 
            GROUP BY status
        ");
        $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $total = array_sum($statusCounts);
        $draft = (int)($statusCounts['DRAFT'] ?? 0);
        $pending = (int)($statusCounts['PENDING_REVIEW'] ?? 0);
        $signed = (int)($statusCounts['SIGNED'] ?? 0);
        $approved = (int)($statusCounts['APPROVED'] ?? 0);
        $rejected = (int)($statusCounts['REJECTED'] ?? 0);
        $docsPending = (int)($statusCounts['DOCUMENTS_PENDING'] ?? 0);

        // Por tipo de matrícula
        $typeStmt = $this->pdo->query("
            SELECT enrollment_type, COUNT(*) as count 
            FROM school_enrollments 
            GROUP BY enrollment_type
        ");
        $byType = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // Por grado
        $gradeStmt = $this->pdo->query("
            SELECT target_grade, COUNT(*) as count 
            FROM school_enrollments 
            GROUP BY target_grade 
            ORDER BY count DESC
        ");
        $byGrade = $gradeStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        // Ingresos proyectados (sumatoria de matrículas aprobadas o firmadas)
        $moneyStmt = $this->pdo->query("
            SELECT 
                COALESCE(SUM(ec.enrollment_fee), 0) as total_enrollment_fees,
                COALESCE(SUM(ec.total_tuition), 0) as total_tuition_expected
            FROM school_enrollments e
            JOIN enrollment_economics ec ON e.id = ec.enrollment_id
            WHERE e.status IN ('APPROVED', 'SIGNED', 'PENDING_REVIEW')
        ");
        $finances = $moneyStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_enrollment_fees' => 0, 'total_tuition_expected' => 0];

        Router::json(200, true, 'Estadísticas obtenidas', [
            'summary' => [
                'total' => $total,
                'draft' => $draft,
                'pending_review' => $pending,
                'signed' => $signed,
                'approved' => $approved,
                'rejected' => $rejected,
                'documents_pending' => $docsPending
            ],
            'by_type' => $byType,
            'by_grade' => $byGrade,
            'finances' => [
                'enrollment_fees_projected' => (float)$finances['total_enrollment_fees'],
                'tuition_projected' => (float)$finances['total_tuition_expected']
            ]
        ]);
    }

    /**
     * Lista filtrable y paginada de matrículas para el panel administrativo.
     * GET /api/v1/admin/enrollments
     */
    public function listEnrollments(): void {
        AuthMiddleware::authenticate($this->config, 'admin');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $grade = trim($_GET['grade'] ?? '');
        $level = trim($_GET['level'] ?? '');
        $year = (int)($_GET['year'] ?? 2026);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = ["e.school_year = :year"];
        $params = [':year' => $year];

        if ($status !== '') {
            $where[] = "e.status = :status";
            $params[':status'] = $status;
        }

        if ($grade !== '') {
            $where[] = "e.target_grade = :grade";
            $params[':grade'] = $grade;
        }

        if ($level !== '') {
            $where[] = "e.enrollment_type = :level";
            $params[':level'] = $level;
        }

        if ($search !== '') {
            $where[] = "(
                e.code LIKE :search 
                OR s.first_name LIKE :search 
                OR s.last_name LIKE :search 
                OR s.doc_number LIKE :search 
                OR g.full_name LIKE :search 
                OR g.email LIKE :search 
                OR g.phone LIKE :search
            )";
            $params[':search'] = "%{$search}%";
        }

        $whereSql = implode(' AND ', $where);

        // Conteo total
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM school_enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN guardians g ON e.guardian_id = g.id
            WHERE {$whereSql}
        ");
        $countStmt->execute($params);
        $totalRows = (int)$countStmt->fetchColumn();

        // Consulta paginada
        $sql = "
            SELECT 
                e.id, e.code, e.enrollment_type, e.target_grade, e.school_year, e.status, e.current_step,
                e.submitted_at, e.approved_at, e.created_at, e.updated_at,
                s.first_name AS student_first_name, s.last_name AS student_last_name, 
                s.doc_type AS student_doc_type, s.doc_number AS student_doc_number,
                g.full_name AS guardian_name, g.doc_number AS guardian_doc_number,
                g.phone AS guardian_phone, g.email AS guardian_email, g.relationship AS guardian_relationship,
                ec.enrollment_fee, ec.monthly_fee, ec.total_tuition,
                (SELECT COUNT(*) FROM enrollment_documents ed WHERE ed.enrollment_id = e.id) as documents_count,
                (SELECT COUNT(*) FROM electronic_signatures es WHERE es.enrollment_id = e.id) as signatures_count
            FROM school_enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN guardians g ON e.guardian_id = g.id
            LEFT JOIN enrollment_economics ec ON e.id = ec.enrollment_id
            WHERE {$whereSql}
            ORDER BY e.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Router::json(200, true, 'Listado de matrículas obtenido', [
            'total' => $totalRows,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalRows / $limit),
            'data' => $rows
        ]);
    }

    /**
     * Consulta detalle completo para revisión administrativa.
     * GET /api/v1/admin/enrollments/{id}
     */
    public function getEnrollmentDetail(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        AuthMiddleware::authenticate($this->config, 'admin');

        $data = $this->pdfService->getEnrollmentFullData($id);
        if (!$data) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        // Obtener historial de auditoría
        $aStmt = $this->pdo->prepare("
            SELECT al.*, u.full_name as user_name, u.role as user_role 
            FROM enrollment_audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.enrollment_id = ?
            ORDER BY al.created_at DESC
        ");
        $aStmt->execute([$id]);
        $auditLogs = $aStmt->fetchAll(PDO::FETCH_ASSOC);

        $data['audit_logs'] = $auditLogs;

        Router::json(200, true, 'Detalle de matrícula obtenido', $data);
    }

    /**
     * Modifica el estado de una matrícula (Aprobar, Rechazar, Solicitar Correcciones).
     * PUT /api/v1/admin/enrollments/{id}/status
     */
    public function updateStatus(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $user = AuthMiddleware::authenticate($this->config, 'admin');
        $adminId = $user['user_id'] ?? $user['id'] ?? '';

        $data = $this->pdfService->getEnrollmentFullData($id);
        if (!$data) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];

        $newStatus = strtoupper(trim($body['status'] ?? ''));
        $observations = trim($body['observations'] ?? $body['reason'] ?? '');

        $validStatuses = [
            Enrollment::STATUS_DRAFT,
            Enrollment::STATUS_PENDING_REVIEW,
            Enrollment::STATUS_DOCUMENTS_PENDING,
            Enrollment::STATUS_SIGNATURE_PENDING,
            Enrollment::STATUS_SIGNED,
            Enrollment::STATUS_APPROVED,
            Enrollment::STATUS_REJECTED,
            Enrollment::STATUS_CANCELLED
        ];

        if (!in_array($newStatus, $validStatuses, true)) {
            Router::json(422, false, "Estado '{$newStatus}' no es válido.");
            return;
        }

        $now = date('Y-m-d H:i:s');
        $prevStatus = $data['enrollment']['status'];

        $approvedAt = ($newStatus === Enrollment::STATUS_APPROVED) ? $now : null;
        $approvedBy = ($newStatus === Enrollment::STATUS_APPROVED) ? $adminId : null;
        $rejectionReason = ($newStatus === Enrollment::STATUS_REJECTED || $newStatus === Enrollment::STATUS_DOCUMENTS_PENDING) ? $observations : null;

        $stmt = $this->pdo->prepare("
            UPDATE school_enrollments SET 
                status = ?,
                observations = ?,
                rejection_reason = ?,
                approved_at = ?,
                approved_by = ?,
                updated_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $observations, $rejectionReason, $approvedAt, $approvedBy, $now, $id]);

        Enrollment::logAudit($this->pdo, $id, 'STATUS_CHANGE', $prevStatus, $newStatus, $adminId, $observations);

        // Notificar por correo al acudiente si existe
        $guardian = $data['guardian'];
        $toEmail = $guardian['email'] ?? '';
        $toName = $guardian['full_name'] ?? 'Padre de Familia';

        if (!empty($toEmail)) {
            $this->emailService->sendEnrollmentStatusEmail(
                $toEmail,
                $toName,
                $data['enrollment']['code'],
                $newStatus,
                $observations
            );
        }

        $updated = $this->pdfService->getEnrollmentFullData($id);
        Router::json(200, true, "Estado de matrícula actualizado a '{$newStatus}'", $updated);
    }

    /**
     * Regeneración forzada de los 4 PDFs desde el panel administrativo.
     * POST /api/v1/admin/enrollments/{id}/documents/regenerate
     */
    public function regenerateDocuments(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $user = AuthMiddleware::authenticate($this->config, 'admin');
        $adminId = $user['user_id'] ?? $user['id'] ?? '';

        $data = $this->pdfService->getEnrollmentFullData($id);
        if (!$data) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        try {
            $docs = $this->pdfService->generateAllDocuments($id);
            Enrollment::logAudit($this->pdo, $id, 'ADMIN_REGENERATE_DOCUMENTS', null, null, $adminId, 'Regeneración forzada de documentos');

            Router::json(200, true, 'Documentos regenerados exitosamente', [
                'enrollment_id' => $id,
                'documents' => array_values($docs)
            ]);
        } catch (Exception $e) {
            Router::json(500, false, 'Error al regenerar documentos: ' . $e->getMessage());
        }
    }
}
