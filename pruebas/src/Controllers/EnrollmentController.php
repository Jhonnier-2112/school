<?php

namespace App\Controllers;

use PDO;
use Exception;
use App\Middleware\AuthMiddleware;
use App\Models\Enrollment;
use App\Services\PdfDocumentService;
use App\Services\EmailService;
use App\Router;

class EnrollmentController {
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
     * Devuelve los tipos de matrícula, grados dinámicos y costos sugeridos.
     * GET /api/v1/enrollments/config/grades
     */
    public function getGradesConfig(): void {
        Router::json(200, true, 'Configuración de matrícula', [
            'school' => [
                'name' => 'Institución Educativa Jean Piaget School',
                'nit' => '39582498-2',
                'city' => 'Girardot, Cundinamarca',
                'rector' => 'Yenih Carolina Gómez Hernández',
                'resolutions' => 'Resolución No. 644 de 2015 / No. 1187 de 2019',
                'school_year' => 2026,
                'default_fees' => [
                    'enrollment_fee' => 180000.00,
                    'monthly_fee' => 150000.00,
                    'installments_count' => 10
                ]
            ],
            'levels' => Enrollment::LEVELS
        ]);
    }

    /**
     * Lista las matrículas del usuario autenticado.
     * GET /api/v1/enrollments/my
     */
    public function getMyEnrollments(): void {
        $user = AuthMiddleware::authenticate($this->config);
        $userId = $user['user_id'] ?? $user['id'] ?? '';

        $stmt = $this->pdo->prepare("
            SELECT e.*, 
                   s.first_name AS student_first_name, s.last_name AS student_last_name, s.doc_number AS student_doc,
                   g.full_name AS guardian_name, g.phone AS guardian_phone
            FROM school_enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN guardians g ON e.guardian_id = g.id
            WHERE e.user_id = ?
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Router::json(200, true, 'Matrículas del usuario obtenidas', $rows);
    }

    /**
     * Obtiene el borrador activo o inicializa uno nuevo.
     * POST /api/v1/enrollments
     */
    public function createOrGetDraft(): void {
        $user = AuthMiddleware::authenticate($this->config);
        $userId = $user['user_id'] ?? $user['id'] ?? '';
        $body = $this->getJsonBody();

        // Si se pasa flag force_new = true, crea una nueva siempre.
        if (empty($body['force_new'])) {
            $stmt = $this->pdo->prepare("
                SELECT id FROM school_enrollments 
                WHERE user_id = ? AND status = 'DRAFT' 
                ORDER BY updated_at DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $draftId = $stmt->fetchColumn();

            if ($draftId) {
                $full = $this->pdfService->getEnrollmentFullData($draftId);
                Router::json(200, true, 'Borrador existente recuperado', $full);
                return;
            }
        }

        // Crear nueva matrícula en borrador
        $enrollmentId = $this->uuid();
        $studentId = $this->uuid();
        $guardianId = $this->uuid();
        $code = Enrollment::generateCode($this->pdo, 2026);
        $now = date('Y-m-d H:i:s');

        // Valores iniciales por defecto
        $level = $body['enrollment_type'] ?? 'BASICA_PRIMARIA';
        $grade = $body['target_grade'] ?? 'Primero';

        if (!Enrollment::isValidLevelAndGrade($level, $grade)) {
            $level = 'BASICA_PRIMARIA';
            $grade = 'Primero';
        }

        $this->pdo->beginTransaction();
        try {
            // Estudiante placeholder
            $sStmt = $this->pdo->prepare("
                INSERT INTO students (id, first_name, last_name, birth_date, birth_place, age, doc_type, doc_number, doc_issue_place, eps, rh, lives_with_parents, lives_with_whom, address, phone, email, created_at, updated_at)
                VALUES (?, '', '', '2015-01-01', 'Girardot', 10, 'TI', ?, 'Girardot', 'Particular', 'O+', 'SI', 'Padres', '', '', ?, ?, ?)
            ");
            $tempDoc = 'TEMP-' . time() . '-' . mt_rand(100, 999);
            $sStmt->execute([$studentId, $tempDoc, $user['email'] ?? '', $now, $now]);

            // Acudiente placeholder
            $gStmt = $this->pdo->prepare("
                INSERT INTO guardians (id, full_name, doc_type, doc_number, doc_issue_place, relationship, phone, email, address, occupation, is_parent, created_at, updated_at)
                VALUES (?, ?, 'CC', '', 'Girardot', 'Padre', '', ?, '', '', 'PADRE', ?, ?)
            ");
            $gStmt->execute([$guardianId, $user['full_name'] ?? 'Acudiente', $user['email'] ?? '', $now, $now]);

            // Matrícula
            $eStmt = $this->pdo->prepare("
                INSERT INTO school_enrollments 
                (id, user_id, student_id, guardian_id, code, enrollment_type, target_grade, school_year, status, current_step, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 2026, 'DRAFT', 1, ?, ?)
            ");
            $eStmt->execute([$enrollmentId, $userId, $studentId, $guardianId, $code, $level, $grade, $now, $now]);

            // Economía inicial
            $ecStmt = $this->pdo->prepare("
                INSERT INTO enrollment_economics (id, enrollment_id, enrollment_fee, monthly_fee, installments_count, total_tuition, payment_method, created_at, updated_at)
                VALUES (?, ?, 180000.00, 150000.00, 10, 1680000.00, 'Mensual', ?, ?)
            ");
            $ecStmt->execute([$this->uuid(), $enrollmentId, $now, $now]);

            // Historial académico inicial
            $acStmt = $this->pdo->prepare("
                INSERT INTO enrollment_academic_history (id, enrollment_id, previous_school, previous_grade, previous_year, created_at, updated_at)
                VALUES (?, ?, 'Institución Educativa Anterior', 'Grado Anterior', 2025, ?, ?)
            ");
            $acStmt->execute([$this->uuid(), $enrollmentId, $now, $now]);

            Enrollment::logAudit($this->pdo, $enrollmentId, 'CREATE_DRAFT', null, 'DRAFT', $userId, 'Borrador de matrícula creado');

            $this->pdo->commit();

            $full = $this->pdfService->getEnrollmentFullData($enrollmentId);
            Router::json(201, true, 'Borrador de matrícula creado exitosamente', $full);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            Router::json(500, false, 'Error al inicializar matrícula: ' . $e->getMessage());
        }
    }

    /**
     * Consulta el detalle completo de una matrícula.
     * GET /api/v1/enrollments/{id}
     */
    public function getEnrollment(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $user = AuthMiddleware::authenticate($this->config);
        $userId = $user['user_id'] ?? $user['id'] ?? '';
        $role = $user['role'] ?? '';

        $data = $this->pdfService->getEnrollmentFullData($id);
        if (!$data) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        // Si no es admin, validar propiedad
        if ($role !== 'admin' && ($data['enrollment']['user_id'] ?? '') !== $userId) {
            Router::json(403, false, 'No tiene permisos para acceder a esta matrícula');
            return;
        }

        Router::json(200, true, 'Detalle de matrícula obtenido', $data);
    }

    /**
     * Guarda el avance de un paso en el wizard de matrícula.
     * PUT /api/v1/enrollments/{id}/step
     */
    public function saveStep(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $user = AuthMiddleware::authenticate($this->config);
        $userId = $user['user_id'] ?? $user['id'] ?? '';
        $role = $user['role'] ?? '';

        $data = $this->pdfService->getEnrollmentFullData($id);
        if (!$data) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        if ($role !== 'admin' && ($data['enrollment']['user_id'] ?? '') !== $userId) {
            Router::json(403, false, 'No tiene permisos para modificar esta matrícula');
            return;
        }

        $body = $this->getJsonBody();
        $step = (int)($body['step'] ?? 1);
        $now = date('Y-m-d H:i:s');

        $this->pdo->beginTransaction();
        try {
            $enrollment = $data['enrollment'];

            // 1. Datos del Estudiante
            if (!empty($body['student'])) {
                $st = $body['student'];
                $sId = $enrollment['student_id'];

                // Validar si el documento cambió y ya pertenece a OTRO estudiante
                if (!empty($st['doc_number'])) {
                    $checkStmt = $this->pdo->prepare("SELECT id FROM students WHERE doc_number = ? AND id != ?");
                    $checkStmt->execute([trim($st['doc_number']), $sId]);
                    $conflict = $checkStmt->fetchColumn();
                    if ($conflict) {
                        $this->pdo->rollBack();
                        Router::json(422, false, "El número de documento {$st['doc_number']} ya se encuentra registrado para otro estudiante.");
                        return;
                    }
                }

                $stmt = $this->pdo->prepare("
                    UPDATE students SET 
                        first_name = ?, last_name = ?, birth_date = ?, birth_place = ?, age = ?,
                        doc_type = ?, doc_number = ?, doc_issue_place = ?, eps = ?, rh = ?,
                        lives_with_parents = ?, lives_with_whom = ?, address = ?, phone = ?, email = ?,
                        updated_at = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    trim($st['first_name'] ?? ''),
                    trim($st['last_last'] ?? $st['last_name'] ?? ''),
                    $st['birth_date'] ?? '2015-01-01',
                    $st['birth_place'] ?? '',
                    (int)($st['age'] ?? 0),
                    $st['doc_type'] ?? 'TI',
                    trim($st['doc_number'] ?? ''),
                    $st['doc_issue_place'] ?? '',
                    $st['eps'] ?? '',
                    $st['rh'] ?? 'O+',
                    $st['lives_with_parents'] ?? 'SI',
                    $st['lives_with_whom'] ?? 'Padres',
                    $st['address'] ?? '',
                    $st['phone'] ?? '',
                    $st['email'] ?? '',
                    $now,
                    $sId
                ]);
            }

            // 2. Datos del Padre
            if (isset($body['father'])) {
                $fa = $body['father'];
                $fatherId = $enrollment['father_id'];

                if (!empty($fa['full_name'])) {
                    if (!$fatherId) {
                        $fatherId = $this->uuid();
                        $ins = $this->pdo->prepare("
                            INSERT INTO parents (id, parent_type, full_name, doc_type, doc_number, doc_issue_place, occupation, phone, email, address, created_at, updated_at)
                            VALUES (?, 'PADRE', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $ins->execute([
                            $fatherId,
                            trim($fa['full_name']),
                            $fa['doc_type'] ?? 'CC',
                            trim($fa['doc_number'] ?? ''),
                            $fa['doc_issue_place'] ?? 'Girardot',
                            $fa['occupation'] ?? '',
                            $fa['phone'] ?? '',
                            $fa['email'] ?? '',
                            $fa['address'] ?? '',
                            $now, $now
                        ]);
                        $this->pdo->prepare("UPDATE school_enrollments SET father_id = ? WHERE id = ?")->execute([$fatherId, $id]);
                    } else {
                        $upd = $this->pdo->prepare("
                            UPDATE parents SET full_name = ?, doc_type = ?, doc_number = ?, doc_issue_place = ?, occupation = ?, phone = ?, email = ?, address = ?, updated_at = ?
                            WHERE id = ?
                        ");
                        $upd->execute([
                            trim($fa['full_name']),
                            $fa['doc_type'] ?? 'CC',
                            trim($fa['doc_number'] ?? ''),
                            $fa['doc_issue_place'] ?? 'Girardot',
                            $fa['occupation'] ?? '',
                            $fa['phone'] ?? '',
                            $fa['email'] ?? '',
                            $fa['address'] ?? '',
                            $now,
                            $fatherId
                        ]);
                    }
                }
            }

            // 3. Datos de la Madre
            if (isset($body['mother'])) {
                $mo = $body['mother'];
                $motherId = $enrollment['mother_id'];

                if (!empty($mo['full_name'])) {
                    if (!$motherId) {
                        $motherId = $this->uuid();
                        $ins = $this->pdo->prepare("
                            INSERT INTO parents (id, parent_type, full_name, doc_type, doc_number, doc_issue_place, occupation, phone, email, address, created_at, updated_at)
                            VALUES (?, 'MADRE', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $ins->execute([
                            $motherId,
                            trim($mo['full_name']),
                            $mo['doc_type'] ?? 'CC',
                            trim($mo['doc_number'] ?? ''),
                            $mo['doc_issue_place'] ?? 'Girardot',
                            $mo['occupation'] ?? '',
                            $mo['phone'] ?? '',
                            $mo['email'] ?? '',
                            $mo['address'] ?? '',
                            $now, $now
                        ]);
                        $this->pdo->prepare("UPDATE school_enrollments SET mother_id = ? WHERE id = ?")->execute([$motherId, $id]);
                    } else {
                        $upd = $this->pdo->prepare("
                            UPDATE parents SET full_name = ?, doc_type = ?, doc_number = ?, doc_issue_place = ?, occupation = ?, phone = ?, email = ?, address = ?, updated_at = ?
                            WHERE id = ?
                        ");
                        $upd->execute([
                            trim($mo['full_name']),
                            $mo['doc_type'] ?? 'CC',
                            trim($mo['doc_number'] ?? ''),
                            $mo['doc_issue_place'] ?? 'Girardot',
                            $mo['occupation'] ?? '',
                            $mo['phone'] ?? '',
                            $mo['email'] ?? '',
                            $mo['address'] ?? '',
                            $now,
                            $motherId
                        ]);
                    }
                }
            }

            // 4. Datos del Acudiente
            if (!empty($body['guardian'])) {
                $gu = $body['guardian'];
                $gId = $enrollment['guardian_id'];

                $upd = $this->pdo->prepare("
                    UPDATE guardians SET 
                        full_name = ?, doc_type = ?, doc_number = ?, doc_issue_place = ?,
                        relationship = ?, phone = ?, email = ?, address = ?, occupation = ?, is_parent = ?,
                        updated_at = ?
                    WHERE id = ?
                ");
                $upd->execute([
                    trim($gu['full_name'] ?? ''),
                    $gu['doc_type'] ?? 'CC',
                    trim($gu['doc_number'] ?? ''),
                    $gu['doc_issue_place'] ?? 'Girardot',
                    $gu['relationship'] ?? 'Padre',
                    $gu['phone'] ?? '',
                    $gu['email'] ?? '',
                    $gu['address'] ?? '',
                    $gu['occupation'] ?? '',
                    $gu['is_parent'] ?? 'OTRO',
                    $now,
                    $gId
                ]);
            }

            // 5. Nivel, Grado e Información Académica
            if (!empty($body['enrollment_type']) || !empty($body['target_grade'])) {
                $newType = $body['enrollment_type'] ?? $enrollment['enrollment_type'];
                $newGrade = $body['target_grade'] ?? $enrollment['target_grade'];

                if (!Enrollment::isValidLevelAndGrade($newType, $newGrade)) {
                    $this->pdo->rollBack();
                    Router::json(422, false, "El grado '{$newGrade}' no es válido para el tipo de matrícula '{$newType}'.");
                    return;
                }

                $this->pdo->prepare("UPDATE school_enrollments SET enrollment_type = ?, target_grade = ?, updated_at = ? WHERE id = ?")
                    ->execute([$newType, $newGrade, $now, $id]);
            }

            if (!empty($body['academic'])) {
                $ac = $body['academic'];
                $this->pdo->prepare("
                    UPDATE enrollment_academic_history SET 
                        previous_school = ?, previous_grade = ?, previous_year = ?, academic_notes = ?, updated_at = ?
                    WHERE enrollment_id = ?
                ")->execute([
                    $ac['previous_school'] ?? '',
                    $ac['previous_grade'] ?? '',
                    (int)($ac['previous_year'] ?? 2025),
                    $ac['academic_notes'] ?? null,
                    $now,
                    $id
                ]);
            }

            // 6. Información Económica
            if (!empty($body['economics'])) {
                $ec = $body['economics'];
                $enFee = (float)($ec['enrollment_fee'] ?? 180000.00);
                $monFee = (float)($ec['monthly_fee'] ?? 150000.00);
                $instCount = (int)($ec['installments_count'] ?? 10);
                $total = $enFee + ($monFee * $instCount);

                $this->pdo->prepare("
                    UPDATE enrollment_economics SET 
                        enrollment_fee = ?, monthly_fee = ?, installments_count = ?, total_tuition = ?, payment_method = ?, notes = ?, updated_at = ?
                    WHERE enrollment_id = ?
                ")->execute([$enFee, $monFee, $instCount, $total, $ec['payment_method'] ?? 'Mensual', $ec['notes'] ?? null, $now, $id]);
            }

            // 7. Términos y Autorizaciones
            if (isset($body['terms_accepted']) || isset($body['data_processing_accepted'])) {
                $terms = !empty($body['terms_accepted']) ? 1 : 0;
                $dataProc = !empty($body['data_processing_accepted']) ? 1 : 0;
                $this->pdo->prepare("UPDATE school_enrollments SET terms_accepted = ?, data_processing_accepted = ?, updated_at = ? WHERE id = ?")
                    ->execute([$terms, $dataProc, $now, $id]);
            }

            // Actualizar paso actual
            $nextStep = max($enrollment['current_step'], $step + 1);
            $this->pdo->prepare("UPDATE school_enrollments SET current_step = ?, updated_at = ? WHERE id = ?")
                ->execute([$nextStep, $now, $id]);

            Enrollment::logAudit($this->pdo, $id, 'SAVE_STEP', null, null, $userId, "Paso {$step} guardado");

            $this->pdo->commit();

            $updated = $this->pdfService->getEnrollmentFullData($id);
            Router::json(200, true, "Paso {$step} guardado correctamente", $updated);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            Router::json(500, false, 'Error al guardar información: ' . $e->getMessage());
        }
    }

    /**
     * Genera los documentos PDF oficiales de la matrícula.
     * POST /api/v1/enrollments/{id}/documents/generate
     */
    public function generateDocuments(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $user = AuthMiddleware::authenticate($this->config);
        $userId = $user['user_id'] ?? $user['id'] ?? '';
        $role = $user['role'] ?? '';

        $data = $this->pdfService->getEnrollmentFullData($id);
        if (!$data) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        if ($role !== 'admin' && ($data['enrollment']['user_id'] ?? '') !== $userId) {
            Router::json(403, false, 'No tiene permisos para generar documentos de esta matrícula');
            return;
        }

        try {
            $docs = $this->pdfService->generateAllDocuments($id);
            Enrollment::logAudit($this->pdo, $id, 'GENERATE_DOCUMENTS', null, null, $userId, 'Documentos PDF generados');

            Router::json(200, true, 'Documentos generados exitosamente', [
                'enrollment_id' => $id,
                'documents' => array_values($docs)
            ]);
        } catch (Exception $e) {
            Router::json(500, false, 'Error al generar documentos: ' . $e->getMessage());
        }
    }

    /**
     * Lista los documentos de la matrícula.
     * GET /api/v1/enrollments/{id}/documents
     */
    public function getDocuments(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $user = AuthMiddleware::authenticate($this->config);
        $userId = $user['user_id'] ?? $user['id'] ?? '';
        $role = $user['role'] ?? '';

        $data = $this->pdfService->getEnrollmentFullData($id);
        if (!$data) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        if ($role !== 'admin' && ($data['enrollment']['user_id'] ?? '') !== $userId) {
            Router::json(403, false, 'Acceso no autorizado');
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM enrollment_documents WHERE enrollment_id = ? ORDER BY created_at ASC");
        $stmt->execute([$id]);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Router::json(200, true, 'Documentos de la matrícula obtenidos', $docs);
    }

    /**
     * Firma electrónica de los documentos.
     * POST /api/v1/enrollments/{id}/sign
     */
    public function sign(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $user = AuthMiddleware::authenticate($this->config);
        $userId = $user['user_id'] ?? $user['id'] ?? '';
        $role = $user['role'] ?? '';

        $data = $this->pdfService->getEnrollmentFullData($id);
        if (!$data) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        if ($role !== 'admin' && ($data['enrollment']['user_id'] ?? '') !== $userId) {
            Router::json(403, false, 'No tiene permisos para firmar esta matrícula');
            return;
        }

        $body = $this->getJsonBody();
        $signatureData = $body['signature_data'] ?? '';

        if (empty($signatureData) || !str_starts_with($signatureData, 'data:image/')) {
            Router::json(422, false, 'Debe proporcionar una firma válida en formato gráfico (trazo o rúbrica)');
            return;
        }

        $guardian = $data['guardian'];
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
        $now = date('Y-m-d H:i:s');
        $hashVerification = hash('sha256', $id . $signatureData . $ip . $now);

        $this->pdo->beginTransaction();
        try {
            // Registrar firma electrónica
            $sigId = $this->uuid();
            $sigStmt = $this->pdo->prepare("
                INSERT INTO electronic_signatures 
                (id, enrollment_id, signer_user_id, signer_name, signer_doc_type, signer_doc_number, signer_role, signature_data, ip_address, user_agent, hash_verification, signed_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'Acudiente / Representante', ?, ?, ?, ?, ?, ?)
            ");
            $sigStmt->execute([
                $sigId,
                $id,
                $userId,
                $guardian['full_name'] ?? ($user['full_name'] ?? 'Acudiente'),
                $guardian['doc_type'] ?? 'CC',
                $guardian['doc_number'] ?? '',
                $signatureData,
                $ip,
                $userAgent,
                $hashVerification,
                $now,
                $now
            ]);

            // Actualizar estado de matrícula a SIGNED
            $prevStatus = $data['enrollment']['status'];
            $this->pdo->prepare("UPDATE school_enrollments SET status = 'SIGNED', current_step = 10, updated_at = ? WHERE id = ?")
                ->execute([$now, $id]);

            Enrollment::logAudit($this->pdo, $id, 'SIGN_DOCUMENTS', $prevStatus, 'SIGNED', $userId, "Firma electrónica registrada con hash: {$hashVerification}");

            $this->pdo->commit();

            // Regenerar los 4 PDFs con la firma incorporada
            $docs = $this->pdfService->generateAllDocuments($id);

            Router::json(200, true, 'Documentos firmados electrónicamente con éxito bajo la Ley 527 de 1999', [
                'enrollment_id' => $id,
                'status' => 'SIGNED',
                'hash_verification' => $hashVerification,
                'signed_at' => $now,
                'documents' => array_values($docs)
            ]);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            Router::json(500, false, 'Error al procesar firma: ' . $e->getMessage());
        }
    }

    /**
     * Envío final de la matrícula para revisión administrativa.
     * POST /api/v1/enrollments/{id}/submit
     */
    public function submit(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $user = AuthMiddleware::authenticate($this->config);
        $userId = $user['user_id'] ?? $user['id'] ?? '';
        $role = $user['role'] ?? '';

        $data = $this->pdfService->getEnrollmentFullData($id);
        if (!$data) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        if ($role !== 'admin' && ($data['enrollment']['user_id'] ?? '') !== $userId) {
            Router::json(403, false, 'Acceso denegado');
            return;
        }

        // Verificar que esté firmada
        if (empty($data['signature'])) {
            Router::json(422, false, 'La matrícula debe estar firmada electrónicamente antes de ser enviada.');
            return;
        }

        $now = date('Y-m-d H:i:s');
        $prevStatus = $data['enrollment']['status'];

        $this->pdo->prepare("UPDATE school_enrollments SET status = 'PENDING_REVIEW', submitted_at = ?, updated_at = ? WHERE id = ?")
            ->execute([$now, $now, $id]);

        Enrollment::logAudit($this->pdo, $id, 'SUBMIT_ENROLLMENT', $prevStatus, 'PENDING_REVIEW', $userId, 'Matrícula enviada para revisión administrativa');

        // Enviar correo de confirmación al acudiente
        $guardian = $data['guardian'];
        $student = $data['student'];
        $enrollment = $data['enrollment'];

        $toEmail = $guardian['email'] ?? ($user['email'] ?? '');
        $toName = $guardian['full_name'] ?? ($user['full_name'] ?? 'Padre de Familia');
        $studentName = ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '');

        if (!empty($toEmail)) {
            $this->emailService->sendEnrollmentSubmittedEmail(
                $toEmail,
                $toName,
                $enrollment['code'],
                $studentName,
                $enrollment['target_grade']
            );
        }

        Router::json(200, true, 'Matrícula enviada exitosamente para revisión administrativa.', [
            'enrollment_id' => $id,
            'code' => $enrollment['code'],
            'status' => 'PENDING_REVIEW',
            'submitted_at' => $now
        ]);
    }

    /**
     * Consulta rápida de estado.
     * GET /api/v1/enrollments/{id}/status
     */
    public function getStatus(array|string $params): void {
        $id = is_array($params) ? ($params['id'] ?? '') : $params;
        $stmt = $this->pdo->prepare("
            SELECT e.id, e.code, e.status, e.enrollment_type, e.target_grade, e.school_year, e.submitted_at, e.approved_at, e.rejection_reason,
                   s.first_name, s.last_name, s.doc_number
            FROM school_enrollments e
            JOIN students s ON e.student_id = s.id
            WHERE e.id = ? OR e.code = ?
        ");
        $stmt->execute([$id, $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            Router::json(404, false, 'Matrícula no encontrada');
            return;
        }

        Router::json(200, true, 'Estado de matrícula', $row);
    }

    private function getJsonBody(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function uuid(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
