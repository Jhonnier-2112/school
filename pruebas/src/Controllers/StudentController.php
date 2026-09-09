<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Router;
use App\Services\DatabaseSeeder;
use App\Services\StorageService;

class StudentController {
    private \PDO $db;
    private array $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/config.php';
        require_once __DIR__ . '/../../config/database.php';
        $this->db = getDBConnection($this->config);
    }

    public function getContract(): void {
        $auth = AuthMiddleware::authenticate($this->config);

        $stmt = $this->db->query("SELECT * FROM contracts WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $contract = $stmt->fetch();

        if (!$contract) {
            Router::json(404, false, 'No hay contrato activo en el sistema');
        }

        $stmtSig = $this->db->prepare("SELECT * FROM contract_signatures WHERE user_id = ? AND contract_id = ? LIMIT 1");
        $stmtSig->execute([$auth['user_id'], $contract['id']]);
        $signature = $stmtSig->fetch();

        Router::json(200, true, 'Contrato obtenido', [
            'contract'  => $contract,
            'is_signed' => (bool)$signature,
            'signed_at' => $signature['signed_at'] ?? null,
            'signature' => $signature ?: null
        ]);
    }

    public function acceptContract(): void {
        $auth = AuthMiddleware::authenticate($this->config);
        $input = Router::getJsonInput();

        $contractId = $input['contract_id'] ?? '';
        $acceptTerms = $input['accept_terms'] ?? false;

        if (empty($contractId) || !$acceptTerms) {
            Router::json(400, false, 'Debe aceptar los términos y condiciones para continuar');
        }

        // Check if already signed
        $stmt = $this->db->prepare("SELECT * FROM contract_signatures WHERE user_id = ? AND contract_id = ?");
        $stmt->execute([$auth['user_id'], $contractId]);
        $existing = $stmt->fetch();
        if ($existing) {
            Router::json(200, true, 'El contrato ya había sido firmado previamente', $existing);
        }

        $sigId = DatabaseSeeder::uuid();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO contract_signatures (id, user_id, contract_id, ip_address, user_agent, signed_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$sigId, $auth['user_id'], $contractId, $ip, $ua, $now, $now]);

        // Activate enrollment
        $stmtAct = $this->db->prepare("UPDATE enrollments SET status = 'active', updated_at = ? WHERE user_id = ?");
        $stmtAct->execute([$now, $auth['user_id']]);

        Router::json(200, true, 'Contrato aceptado exitosamente', [
            'id'          => $sigId,
            'user_id'     => $auth['user_id'],
            'contract_id' => $contractId,
            'ip_address'  => $ip,
            'user_agent'  => $ua,
            'signed_at'   => $now
        ]);
    }

    public function getCourseSummary(): void {
        $auth = AuthMiddleware::authenticate($this->config);

        // Get enrollment
        $stmt = $this->db->prepare("
            SELECT e.*, c.total_price 
            FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            WHERE e.user_id = ? 
            ORDER BY e.created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$auth['user_id']]);
        $enrollment = $stmt->fetch();

        $coursePrice = $enrollment ? (float)$enrollment['total_price'] : (float)$this->config['course_price'];
        $enrollmentDate = $enrollment ? $enrollment['enrolled_at'] : date('Y-m-d H:i:s');

        // Get payments
        $stmtPayments = $this->db->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC");
        $stmtPayments->execute([$auth['user_id']]);
        $payments = $stmtPayments->fetchAll();

        $totalPaid = 0.0;
        $lastPaymentDate = null;
        foreach ($payments as $p) {
            $totalPaid += (float)$p['amount'];
            if ($lastPaymentDate === null || $p['payment_date'] > $lastPaymentDate) {
                $lastPaymentDate = $p['payment_date'];
            }
        }

        $remaining = max(0.0, $coursePrice - $totalPaid);
        $percentage = $coursePrice > 0 ? round(($totalPaid / $coursePrice) * 100, 2) : 0.0;
        if ($percentage > 100) {
            $percentage = 100.0;
        }

        Router::json(200, true, 'Resumen financiero del curso', [
            'summary' => [
                'course_price'     => $coursePrice,
                'total_paid'       => $totalPaid,
                'remaining_amount' => $remaining,
                'percentage_paid'  => $percentage,
                'enrollment_date'  => $enrollmentDate,
                'last_payment_date'=> $lastPaymentDate,
                'payments_count'   => count($payments)
            ],
            'payments' => $payments
        ]);
    }

    public function getPayments(): void {
        $auth = AuthMiddleware::authenticate($this->config);

        $stmt = $this->db->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC");
        $stmt->execute([$auth['user_id']]);
        $payments = $stmt->fetchAll();

        Router::json(200, true, 'Historial de abonos', $payments);
    }

    public function uploadDocument(): void {
        $auth = AuthMiddleware::authenticate($this->config);

        if (!isset($_FILES['document'])) {
            Router::json(400, false, 'Debe enviar un archivo en el campo "document"');
        }

        $docType = $_POST['document_type'] ?? 'CC';
        $docNumber = $_POST['document_number'] ?? '';

        try {
            $fileUrl = StorageService::upload($_FILES['document'], 'documents', $this->config);

            $docId = DatabaseSeeder::uuid();
            $now = date('Y-m-d H:i:s');

            $stmt = $this->db->prepare("
                INSERT INTO identification_documents (id, user_id, document_type, document_number, file_url, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
            ");
            $stmt->execute([$docId, $auth['user_id'], $docType, $docNumber, $fileUrl, $now, $now]);

            Router::json(201, true, 'Documento cargado exitosamente en revisión', [
                'id'              => $docId,
                'user_id'         => $auth['user_id'],
                'document_type'   => $docType,
                'document_number' => $docNumber,
                'file_url'        => $fileUrl,
                'status'          => 'pending',
                'created_at'      => $now
            ]);
        } catch (\Exception $e) {
            Router::json(400, false, $e->getMessage());
        }
    }

    public function getDocument(): void {
        $auth = AuthMiddleware::authenticate($this->config);

        $stmt = $this->db->prepare("SELECT * FROM identification_documents WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$auth['user_id']]);
        $doc = $stmt->fetch();

        Router::json(200, true, 'Estado de documento', $doc ?: null);
    }

    public function listExams(): void {
        AuthMiddleware::authenticate($this->config);

        $stmt = $this->db->query("SELECT * FROM exams WHERE is_published = 1 ORDER BY created_at DESC");
        $exams = $stmt->fetchAll();

        foreach ($exams as &$exam) {
            $stmtQ = $this->db->prepare("SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?");
            $stmtQ->execute([$exam['id']]);
            $exam['questions_count'] = (int)$stmtQ->fetchColumn();
        }

        Router::json(200, true, 'Simulacros disponibles', $exams);
    }

    public function startExam(array $params): void {
        $auth = AuthMiddleware::authenticate($this->config);
        $examId = $params['id'] ?? '';

        $stmt = $this->db->prepare("SELECT * FROM exams WHERE id = ? AND is_published = 1");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();

        if (!$exam) {
            Router::json(404, false, 'Simulacro no encontrado o no publicado');
        }

        // Get questions without revealing correct answer
        $stmtQ = $this->db->prepare("
            SELECT q.id, q.statement, q.question_type, q.image_url, 
                   q.option_a, q.option_b, q.option_c, q.option_d, 
                   q.score_type, q.score_weight, q.time_limit_seconds, q.difficulty,
                   s.id as subject_id, s.name as subject_name, s.code as subject_code,
                   eq.order_index
            FROM exam_questions eq
            JOIN questions q ON eq.question_id = q.id
            JOIN subjects s ON q.subject_id = s.id
            WHERE eq.exam_id = ?
            ORDER BY eq.order_index ASC
        ");
        $stmtQ->execute([$examId]);
        $questions = $stmtQ->fetchAll();

        $maxScore = 0.0;
        foreach ($questions as $q) {
            $maxScore += (float)($q['score_weight'] > 0 ? $q['score_weight'] : 1.0);
        }

        $sessionId = DatabaseSeeder::uuid();
        $now = date('Y-m-d H:i:s');

        $stmtSession = $this->db->prepare("
            INSERT INTO student_exams (id, user_id, exam_id, started_at, total_score, max_score, status, created_at)
            VALUES (?, ?, ?, ?, 0.00, ?, 'in_progress', ?)
        ");
        $stmtSession->execute([$sessionId, $auth['user_id'], $examId, $now, $maxScore, $now]);

        Router::json(201, true, 'Simulacro iniciado', [
            'session' => [
                'id'         => $sessionId,
                'user_id'    => $auth['user_id'],
                'exam_id'    => $examId,
                'started_at' => $now,
                'max_score'  => $maxScore,
                'status'     => 'in_progress'
            ],
            'exam' => $exam,
            'questions' => $questions
        ]);
    }

    public function submitExam(array $params): void {
        $auth = AuthMiddleware::authenticate($this->config);
        $sessionId = $params['session_id'] ?? '';
        $input = Router::getJsonInput();

        $stmt = $this->db->prepare("SELECT * FROM student_exams WHERE id = ? LIMIT 1");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if (!$session) {
            Router::json(404, false, 'Sesión de simulacro no encontrada');
        }

        if ($session['user_id'] !== $auth['user_id']) {
            Router::json(403, false, 'No tiene autorización para enviar este examen');
        }

        if ($session['status'] === 'submitted') {
            $this->getExamResults($params);
            return;
        }

        $rawAnswers = $input['answers'] ?? [];
        $answers = [];
        if (is_array($rawAnswers)) {
            foreach ($rawAnswers as $key => $val) {
                if (is_array($val) && isset($val['question_id'])) {
                    $answers[] = $val;
                } else {
                    $qId = is_string($key) && strlen($key) > 5 ? $key : ($val['question_id'] ?? '');
                    $selected = is_string($val) && strlen($val) <= 2 ? $val : ($val['selected_option'] ?? '');
                    $answerText = is_string($val) ? $val : ($val['answer_text'] ?? $val['selected_option'] ?? '');
                    $answers[] = [
                        'question_id'     => $qId,
                        'selected_option' => $selected,
                        'answer_text'     => $answerText
                    ];
                }
            }
        }

        // Fetch exam questions with correct answers
        $stmtQ = $this->db->prepare("
            SELECT q.*, s.name as subject_name, s.id as subject_id
            FROM exam_questions eq
            JOIN questions q ON eq.question_id = q.id
            JOIN subjects s ON q.subject_id = s.id
            WHERE eq.exam_id = ?
        ");
        $stmtQ->execute([$session['exam_id']]);
        $questionsList = $stmtQ->fetchAll();

        $questionsMap = [];
        foreach ($questionsList as $q) {
            $questionsMap[$q['id']] = $q;
        }

        $now = date('Y-m-d H:i:s');
        $totalScore = 0.0;

        $stmtAns = $this->db->prepare("
            INSERT INTO student_answers (id, student_exam_id, question_id, selected_option, answer_text, is_correct, score_earned, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($answers as $ans) {
            $qId = $ans['question_id'] ?? '';

            if (!isset($questionsMap[$qId])) {
                continue;
            }

            $q = $questionsMap[$qId];
            $qType = $q['question_type'] ?? 'multiple_choice';
            $selected = strtoupper(trim($ans['selected_option'] ?? ''));
            $answerText = trim($ans['answer_text'] ?? $ans['selected_option'] ?? '');
            $isCorrect = false;

            if ($qType === 'open_text') {
                $expected = trim($q['correct_answer_text'] ?? '');
                $acceptable = array_filter(array_map('trim', preg_split('/[,|]/', $expected)));
                $userNorm = mb_strtolower($answerText, 'UTF-8');
                foreach ($acceptable as $acc) {
                    if ($userNorm === mb_strtolower($acc, 'UTF-8')) {
                        $isCorrect = true;
                        break;
                    }
                }
            } elseif ($qType === 'true_false') {
                $isCorrect = (!empty($selected) && $selected === strtoupper(trim($q['correct_option'] ?? '')));
            } else {
                // multiple_choice
                $isCorrect = (!empty($selected) && $selected === strtoupper(trim($q['correct_option'] ?? '')));
            }

            $weight = (float)($q['score_weight'] > 0 ? $q['score_weight'] : 1.0);
            $scoreEarned = $isCorrect ? $weight : 0.0;
            $totalScore += $scoreEarned;

            $stmtAns->execute([
                DatabaseSeeder::uuid(),
                $sessionId,
                $qId,
                $selected ?: null,
                $answerText ?: null,
                $isCorrect ? 1 : 0,
                $scoreEarned,
                $now
            ]);
        }

        // Update student_exams
        $stmtUp = $this->db->prepare("
            UPDATE student_exams 
            SET submitted_at = ?, total_score = ?, status = 'submitted' 
            WHERE id = ?
        ");
        $stmtUp->execute([$now, $totalScore, $sessionId]);

        $this->getExamResults($params);
    }

    public function getExamResults(array $params): void {
        $auth = AuthMiddleware::authenticate($this->config);
        $sessionId = $params['session_id'] ?? '';

        $stmt = $this->db->prepare("
            SELECT se.*, e.title as exam_title 
            FROM student_exams se 
            JOIN exams e ON se.exam_id = e.id 
            WHERE se.id = ? 
            LIMIT 1
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if (!$session) {
            Router::json(404, false, 'Resultado no encontrado');
        }

        if ($session['user_id'] !== $auth['user_id'] && ($auth['role'] ?? '') !== 'admin') {
            Router::json(403, false, 'No autorizado');
        }

        // Get answers with subjects
        $stmtAns = $this->db->prepare("
            SELECT sa.*, q.statement, q.correct_option, q.explanation, s.id as subject_id, s.name as subject_name 
            FROM student_answers sa 
            JOIN questions q ON sa.question_id = q.id 
            JOIN subjects s ON q.subject_id = s.id 
            WHERE sa.student_exam_id = ?
        ");
        $stmtAns->execute([$sessionId]);
        $answers = $stmtAns->fetchAll();

        $totalCorrect = 0;
        $totalWrong = 0;
        $subjectStats = [];

        foreach ($answers as $ans) {
            if ($ans['is_correct']) {
                $totalCorrect++;
            } else {
                $totalWrong++;
            }

            $sId = $ans['subject_id'];
            if (!isset($subjectStats[$sId])) {
                $subjectStats[$sId] = [
                    'subject_id'    => $sId,
                    'subject_name'  => $ans['subject_name'],
                    'total_count'   => 0,
                    'correct_count' => 0,
                    'wrong_count'   => 0,
                    'score'         => 0.0,
                    'percentage'    => 0.0
                ];
            }

            $subjectStats[$sId]['total_count']++;
            if ($ans['is_correct']) {
                $subjectStats[$sId]['correct_count']++;
                $subjectStats[$sId]['score'] += (float)$ans['score_earned'];
            } else {
                $subjectStats[$sId]['wrong_count']++;
            }
        }

        foreach ($subjectStats as &$stat) {
            if ($stat['total_count'] > 0) {
                $stat['percentage'] = round(($stat['correct_count'] / $stat['total_count']) * 100, 2);
            }
        }

        $totalQuestions = $totalCorrect + $totalWrong;
        $globalPercentage = 0.0;
        $maxScore = (float)$session['max_score'];
        if ($maxScore > 0) {
            $globalPercentage = round(((float)$session['total_score'] / $maxScore) * 100, 2);
        }

        Router::json(200, true, 'Resultados del simulacro', [
            'student_exam_id'     => $sessionId,
            'exam_title'          => $session['exam_title'],
            'started_at'          => $session['started_at'],
            'submitted_at'        => $session['submitted_at'],
            'total_questions'     => $totalQuestions,
            'total_correct'       => $totalCorrect,
            'total_wrong'         => $totalWrong,
            'total_score'         => (float)$session['total_score'],
            'max_score'           => $maxScore,
            'global_percentage'   => $globalPercentage,
            'subject_performance'=> array_values($subjectStats)
        ]);
    }

    public function getExamHistory(): void {
        $auth = AuthMiddleware::authenticate($this->config);

        $stmt = $this->db->prepare("
            SELECT se.*, e.title as exam_title 
            FROM student_exams se 
            JOIN exams e ON se.exam_id = e.id 
            WHERE se.user_id = ? 
            ORDER BY se.created_at DESC
        ");
        $stmt->execute([$auth['user_id']]);
        $history = $stmt->fetchAll();

        Router::json(200, true, 'Historial de simulacros', $history);
    }
}
