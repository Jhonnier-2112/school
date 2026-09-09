<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;
use Exception;

class PdfDocumentService {
    private PDO $pdo;
    private array $config;
    private string $uploadDir;
    private string $templatesDir;
    private string $flagBase64;
    private string $logoBase64;

    public function __construct(PDO $pdo, array $config) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->uploadDir = rtrim($config['upload_dir'] ?? (__DIR__ . '/../../uploads'), '/') . '/documents';
        $this->templatesDir = __DIR__ . '/../Templates/Documents';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }

        $flagPath = __DIR__ . '/../../assets/flag_jean_piaget.jpg';
        if (file_exists($flagPath)) {
            $this->flagBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($flagPath));
        } else {
            $this->flagBase64 = '';
        }

        $logoPath = __DIR__ . '/../../assets/logo_jean_piaget.png';
        if (file_exists($logoPath)) {
            $this->logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        } else {
            $this->logoBase64 = '';
        }
    }

    /**
     * Generates all 4 enrollment documents (Formato, Contrato, Pagaré, Carta de Instrucciones).
     */
    public function generateAllDocuments(string $enrollmentId): array {
        $data = $this->getEnrollmentFullData($enrollmentId);
        if (!$data) {
            throw new Exception("Matrícula no encontrada: {$enrollmentId}");
        }

        $docTypes = [
            'FORMATO_MATRICULA' => [
                'title' => 'Formato de Matrícula 2026',
                'template' => 'formato_matricula.html',
                'file_prefix' => 'formato_matricula'
            ],
            'CONTRATO_2026' => [
                'title' => 'Contrato de Servicios Educativos 2026',
                'template' => 'contrato_servicios_2026.html',
                'file_prefix' => 'contrato_2026'
            ],
            'PAGARE_2026' => [
                'title' => 'Pagaré 2026',
                'template' => 'pagare_2026.html',
                'file_prefix' => 'pagare_2026'
            ],
            'CARTA_INSTRUCCIONES_2026' => [
                'title' => 'Carta de Instrucciones Pagaré 2026',
                'template' => 'carta_instrucciones_2026.html',
                'file_prefix' => 'carta_instrucciones_2026'
            ]
        ];

        $generated = [];
        foreach ($docTypes as $type => $info) {
            $doc = $this->generateSingleDocument($data, $type, $info);
            $generated[$type] = $doc;
        }

        return $generated;
    }

    /**
     * Generates a single document PDF and persists record in database.
     */
    public function generateSingleDocument(array $data, string $docType, array $info): array {
        $templatePath = $this->templatesDir . '/' . $info['template'];
        if (!file_exists($templatePath)) {
            throw new Exception("Plantilla no encontrada: {$info['template']}");
        }

        $templateContent = file_get_contents($templatePath);
        $replacements = $this->buildReplacements($data);
        $html = strtr($templateContent, $replacements);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        $code = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['enrollment']['code'] ?? 'MAT');
        $fileName = "{$info['file_prefix']}_{$code}_v" . time() . ".pdf";
        $filePath = $this->uploadDir . '/' . $fileName;

        file_put_contents($filePath, $pdfOutput);

        $fileHash = hash('sha256', $pdfOutput);
        $baseUrl = rtrim($this->config['base_url'] ?? '', '/');
        $fileUrl = $baseUrl . '/uploads/documents/' . $fileName;

        $enrollmentId = $data['enrollment']['id'];
        $stmt = $this->pdo->prepare("SELECT id, version FROM enrollment_documents WHERE enrollment_id = ? AND document_type = ?");
        $stmt->execute([$enrollmentId, $docType]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        $docId = $existing['id'] ?? $this->uuid();
        $version = ($existing['version'] ?? 0) + 1;
        $isSigned = !empty($data['signature']) ? 1 : 0;
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $update = $this->pdo->prepare("
                UPDATE enrollment_documents 
                SET title = ?, file_path = ?, file_url = ?, file_hash = ?, version = ?, is_signed = ?, generated_at = ?
                WHERE id = ?
            ");
            $update->execute([$info['title'], $filePath, $fileUrl, $fileHash, $version, $isSigned, $now, $docId]);
        } else {
            $insert = $this->pdo->prepare("
                INSERT INTO enrollment_documents (id, enrollment_id, document_type, title, file_path, file_url, file_hash, version, is_signed, generated_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([$docId, $enrollmentId, $docType, $info['title'], $filePath, $fileUrl, $fileHash, $version, $isSigned, $now, $now]);
        }

        // Record document version
        $versionId = $this->uuid();
        $vStmt = $this->pdo->prepare("
            INSERT INTO document_versions (id, document_id, version_number, file_path, change_summary, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $vStmt->execute([
            $versionId,
            $docId,
            $version,
            $filePath,
            $isSigned ? 'Generado con firma digital' : 'Generación inicial de borrador',
            $now
        ]);

        return [
            'id' => $docId,
            'document_type' => $docType,
            'title' => $info['title'],
            'file_name' => $fileName,
            'file_url' => $fileUrl,
            'file_hash' => $fileHash,
            'version' => $version,
            'is_signed' => (bool)$isSigned,
            'generated_at' => $now
        ];
    }

    /**
     * Builds replacement dictionary for template placeholders.
     */
    private function buildReplacements(array $data): array {
        $e = $data['enrollment'];
        $s = $data['student'];
        $f = $data['father'] ?? [];
        $m = $data['mother'] ?? [];
        $g = $data['guardian'];
        $a = $data['academic'] ?? [];
        $ec = $data['economics'] ?? [];
        $sig = $data['signature'] ?? null;

        $enrollmentFee = (float)($ec['enrollment_fee'] ?? 180000);
        $monthlyFee = (float)($ec['monthly_fee'] ?? 150000);
        $installments = (int)($ec['installments_count'] ?? 10);
        $totalPension = $monthlyFee * $installments;
        $totalTuition = (float)($ec['total_tuition'] ?? ($enrollmentFee + $totalPension));

        $sigDate = $sig ? strtotime($sig['signed_at']) : time();
        $day = date('d', $sigDate);
        $months = [
            '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
            '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
            '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
        ];
        $month = $months[date('m', $sigDate)] ?? date('F', $sigDate);
        $year = date('Y', $sigDate);

        $typeLabels = [
            'PREESCOLAR' => 'Preescolar',
            'BASICA_PRIMARIA' => 'Básica Primaria',
            'BACHILLERATO_CICLOS' => 'Bachillerato por Ciclos'
        ];
        $typeLabel = $typeLabels[$e['enrollment_type'] ?? ''] ?? ($e['enrollment_type'] ?? 'Educación Formal');

        $sigHtml = '';
        if ($sig && !empty($sig['signature_data'])) {
            $sigHtml = '<img src="' . htmlspecialchars($sig['signature_data']) . '" alt="Firma Digital">';
        } else {
            $sigHtml = '<p style="font-size: 8px; color: #94a3b8; margin-top: 30px;">(Pendiente de firma)</p>';
        }

        return [
            '{{FLAG_BASE64}}' => $this->flagBase64,
            '{{LOGO_BASE64}}' => $this->logoBase64,
            '{{ENROLLMENT_CODE}}' => htmlspecialchars($e['code'] ?? 'MAT-2026-0000'),
            '{{ENROLLMENT_TYPE}}' => htmlspecialchars($e['enrollment_type'] ?? ''),
            '{{ENROLLMENT_TYPE_LABEL}}' => htmlspecialchars($typeLabel),
            '{{TARGET_GRADE}}' => htmlspecialchars($e['target_grade'] ?? ''),
            '{{SCHOOL_YEAR}}' => htmlspecialchars((string)($e['school_year'] ?? '2026')),
            '{{REGISTRATION_DATE}}' => date('d/m/Y', strtotime($e['created_at'] ?? 'now')),

            // Student
            '{{STUDENT_NAME}}' => htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')),
            '{{STUDENT_FIRST_NAME}}' => htmlspecialchars($s['first_name'] ?? ''),
            '{{STUDENT_LAST_NAME}}' => htmlspecialchars($s['last_name'] ?? ''),
            '{{STUDENT_DOC_TYPE}}' => htmlspecialchars($s['doc_type'] ?? 'TI'),
            '{{STUDENT_DOC_NUMBER}}' => htmlspecialchars($s['doc_number'] ?? ''),
            '{{STUDENT_DOC_PLACE}}' => htmlspecialchars($s['doc_issue_place'] ?? 'Girardot'),
            '{{STUDENT_BIRTH_DATE}}' => !empty($s['birth_date']) ? date('d/m/Y', strtotime($s['birth_date'])) : '',
            '{{STUDENT_BIRTH_PLACE}}' => htmlspecialchars($s['birth_place'] ?? ''),
            '{{STUDENT_AGE}}' => htmlspecialchars((string)($s['age'] ?? '')),
            '{{STUDENT_EPS}}' => htmlspecialchars($s['eps'] ?? 'Particular'),
            '{{STUDENT_RH}}' => htmlspecialchars($s['rh'] ?? 'O+'),
            '{{STUDENT_LIVES_WITH_PARENTS}}' => htmlspecialchars($s['lives_with_parents'] ?? 'SI'),
            '{{STUDENT_LIVES_WITH}}' => htmlspecialchars($s['lives_with_whom'] ?? 'Padres'),
            '{{STUDENT_ADDRESS}}' => htmlspecialchars($s['address'] ?? ''),
            '{{STUDENT_PHONE}}' => htmlspecialchars($s['phone'] ?? ''),
            '{{STUDENT_EMAIL}}' => htmlspecialchars($s['email'] ?? ''),

            // Father
            '{{FATHER_NAME}}' => htmlspecialchars($f['full_name'] ?? 'No registra'),
            '{{FATHER_DOC_TYPE}}' => htmlspecialchars($f['doc_type'] ?? 'CC'),
            '{{FATHER_DOC_NUMBER}}' => htmlspecialchars($f['doc_number'] ?? 'N/A'),
            '{{FATHER_OCCUPATION}}' => htmlspecialchars($f['occupation'] ?? 'N/A'),
            '{{FATHER_PHONE}}' => htmlspecialchars($f['phone'] ?? 'N/A'),
            '{{FATHER_EMAIL}}' => htmlspecialchars($f['email'] ?? 'N/A'),
            '{{FATHER_ADDRESS}}' => htmlspecialchars($f['address'] ?? 'N/A'),

            // Mother
            '{{MOTHER_NAME}}' => htmlspecialchars($m['full_name'] ?? 'No registra'),
            '{{MOTHER_DOC_TYPE}}' => htmlspecialchars($m['doc_type'] ?? 'CC'),
            '{{MOTHER_DOC_NUMBER}}' => htmlspecialchars($m['doc_number'] ?? 'N/A'),
            '{{MOTHER_OCCUPATION}}' => htmlspecialchars($m['occupation'] ?? 'N/A'),
            '{{MOTHER_PHONE}}' => htmlspecialchars($m['phone'] ?? 'N/A'),
            '{{MOTHER_EMAIL}}' => htmlspecialchars($m['email'] ?? 'N/A'),
            '{{MOTHER_ADDRESS}}' => htmlspecialchars($m['address'] ?? 'N/A'),

            // Guardian
            '{{GUARDIAN_NAME}}' => htmlspecialchars($g['full_name'] ?? ''),
            '{{GUARDIAN_DOC_TYPE}}' => htmlspecialchars($g['doc_type'] ?? 'CC'),
            '{{GUARDIAN_DOC_NUMBER}}' => htmlspecialchars($g['doc_number'] ?? ''),
            '{{GUARDIAN_DOC_PLACE}}' => htmlspecialchars($g['doc_issue_place'] ?? 'Girardot'),
            '{{GUARDIAN_RELATIONSHIP}}' => htmlspecialchars($g['relationship'] ?? 'Acudiente'),
            '{{GUARDIAN_PHONE}}' => htmlspecialchars($g['phone'] ?? ''),
            '{{GUARDIAN_EMAIL}}' => htmlspecialchars($g['email'] ?? ''),
            '{{GUARDIAN_ADDRESS}}' => htmlspecialchars($g['address'] ?? ''),

            // Academic
            '{{PREVIOUS_SCHOOL}}' => htmlspecialchars($a['previous_school'] ?? 'N/A'),
            '{{PREVIOUS_GRADE}}' => htmlspecialchars($a['previous_grade'] ?? 'N/A'),
            '{{PREVIOUS_YEAR}}' => htmlspecialchars((string)($a['previous_year'] ?? date('Y') - 1)),

            // Economics
            '{{ENROLLMENT_FEE_FORMATTED}}' => number_format($enrollmentFee, 0, ',', '.'),
            '{{ENROLLMENT_FEE_WORDS}}' => self::numberToWordsSpanish($enrollmentFee),
            '{{MONTHLY_FEE_FORMATTED}}' => number_format($monthlyFee, 0, ',', '.'),
            '{{MONTHLY_FEE_WORDS}}' => self::numberToWordsSpanish($monthlyFee),
            '{{TOTAL_PENSION_FORMATTED}}' => number_format($totalPension, 0, ',', '.'),
            '{{TOTAL_PENSION_WORDS}}' => self::numberToWordsSpanish($totalPension),
            '{{TOTAL_FEE_FORMATTED}}' => number_format($totalTuition, 0, ',', '.'),
            '{{TOTAL_FEE_WORDS}}' => self::numberToWordsSpanish($totalTuition),

            // Date & Signature
            '{{SIGNATURE_DAY}}' => $day,
            '{{SIGNATURE_MONTH}}' => $month,
            '{{SIGNATURE_YEAR}}' => $year,
            '{{SIGNATURE_TIMESTAMP}}' => $sig ? date('d/m/Y H:i:s', strtotime($sig['signed_at'])) : date('d/m/Y H:i:s'),
            '{{GUARDIAN_SIGNATURE_HTML}}' => $sigHtml,
            '{{SIGNER_IP}}' => htmlspecialchars($sig['ip_address'] ?? '127.0.0.1'),
            '{{VERIFICATION_HASH}}' => htmlspecialchars($sig['hash_verification'] ?? hash('sha256', ($e['id'] ?? '') . time())),
        ];
    }

    /**
     * Loads complete enrollment details for document compilation.
     */
    public function getEnrollmentFullData(string $enrollmentId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM school_enrollments WHERE id = ?");
        $stmt->execute([$enrollmentId]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$enrollment) return null;

        // Student
        $sStmt = $this->pdo->prepare("SELECT * FROM students WHERE id = ?");
        $sStmt->execute([$enrollment['student_id']]);
        $student = $sStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Father
        $father = null;
        if (!empty($enrollment['father_id'])) {
            $fStmt = $this->pdo->prepare("SELECT * FROM parents WHERE id = ?");
            $fStmt->execute([$enrollment['father_id']]);
            $father = $fStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // Mother
        $mother = null;
        if (!empty($enrollment['mother_id'])) {
            $mStmt = $this->pdo->prepare("SELECT * FROM parents WHERE id = ?");
            $mStmt->execute([$enrollment['mother_id']]);
            $mother = $mStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // Guardian
        $gStmt = $this->pdo->prepare("SELECT * FROM guardians WHERE id = ?");
        $gStmt->execute([$enrollment['guardian_id']]);
        $guardian = $gStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Academic
        $aStmt = $this->pdo->prepare("SELECT * FROM enrollment_academic_history WHERE enrollment_id = ?");
        $aStmt->execute([$enrollmentId]);
        $academic = $aStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Economics
        $eStmt = $this->pdo->prepare("SELECT * FROM enrollment_economics WHERE enrollment_id = ?");
        $eStmt->execute([$enrollmentId]);
        $economics = $eStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Signature
        $sigStmt = $this->pdo->prepare("SELECT * FROM electronic_signatures WHERE enrollment_id = ? ORDER BY signed_at DESC LIMIT 1");
        $sigStmt->execute([$enrollmentId]);
        $signature = $sigStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Existing documents
        $dStmt = $this->pdo->prepare("SELECT * FROM enrollment_documents WHERE enrollment_id = ? ORDER BY created_at ASC");
        $dStmt->execute([$enrollmentId]);
        $documents = $dStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'enrollment' => $enrollment,
            'student' => $student,
            'father' => $father,
            'mother' => $mother,
            'guardian' => $guardian,
            'academic' => $academic,
            'economics' => $economics,
            'signature' => $signature,
            'documents' => $documents
        ];
    }

    /**
     * Converts a numeric value into Spanish currency text in words.
     */
    public static function numberToWordsSpanish(float $number): string {
        $integerPart = (int)$number;

        if ($integerPart == 0) {
            return "CERO PESOS M/CTE";
        }

        $units = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $teens = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $tens = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $hundreds = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $convertGroup = function($n) use ($units, $teens, $tens, $hundreds) {
            $output = '';
            if ($n == 100) return 'CIEN';

            $c = (int)($n / 100);
            $rem = $n % 100;
            if ($c > 0) {
                $output .= $hundreds[$c] . ' ';
            }

            if ($rem >= 10 && $rem < 20) {
                $output .= $teens[$rem - 10] . ' ';
            } elseif ($rem == 20) {
                $output .= 'VEINTE ';
            } elseif ($rem > 20 && $rem < 30) {
                $output .= 'VEINTI' . $units[$rem - 20] . ' ';
            } elseif ($rem >= 30) {
                $t = (int)($rem / 10);
                $u = $rem % 10;
                $output .= $tens[$t] . ($u > 0 ? ' Y ' . $units[$u] : '') . ' ';
            } elseif ($rem > 0) {
                $output .= $units[$rem] . ' ';
            }

            return trim($output);
        };

        $millions = (int)($integerPart / 1000000);
        $remMillions = $integerPart % 1000000;
        $thousands = (int)($remMillions / 1000);
        $remThousands = $remMillions % 1000;

        $text = '';
        if ($millions == 1) {
            $text .= 'UN MILLÓN ';
        } elseif ($millions > 1) {
            $text .= $convertGroup($millions) . ' MILLONES ';
        }

        if ($thousands == 1) {
            $text .= 'MIL ';
        } elseif ($thousands > 1) {
            $text .= $convertGroup($thousands) . ' MIL ';
        }

        if ($remThousands > 0) {
            $text .= $convertGroup($remThousands) . ' ';
        }

        return trim($text) . ' PESOS M/CTE';
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
