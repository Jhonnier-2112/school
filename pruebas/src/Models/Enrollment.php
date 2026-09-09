<?php

namespace App\Models;

use PDO;
use Exception;

class Enrollment {
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';
    public const STATUS_DOCUMENTS_PENDING = 'DOCUMENTS_PENDING';
    public const STATUS_SIGNATURE_PENDING = 'SIGNATURE_PENDING';
    public const STATUS_SIGNED = 'SIGNED';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const LEVELS = [
        'PREESCOLAR' => [
            'label' => 'Preescolar',
            'grades' => ['Párvulos', 'Pre-Jardín', 'Jardín', 'Transición']
        ],
        'BASICA_PRIMARIA' => [
            'label' => 'Básica Primaria',
            'grades' => ['Pre-Jardín', 'Jardín', 'Transición', 'Primero', 'Segundo', 'Tercero', 'Cuarto', 'Quinto']
        ],
        'BACHILLERATO_CICLOS' => [
            'label' => 'Bachillerato por Ciclos',
            'grades' => ['Quinto', 'Sexto', 'Séptimo', 'Octavo', 'Noveno', 'Décimo']
        ]
    ];

    public static function getGradesForLevel(string $level): array {
        return self::LEVELS[$level]['grades'] ?? [];
    }

    public static function isValidLevelAndGrade(string $level, string $grade): bool {
        if (!isset(self::LEVELS[$level])) {
            return false;
        }
        return in_array($grade, self::LEVELS[$level]['grades'], true);
    }

    /**
     * Generates a unique institutional enrollment code (e.g. MAT-2026-1042).
     */
    public static function generateCode(PDO $pdo, int $year = 2026): string {
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $num = mt_rand(1000, 9999);
            $code = "MAT-{$year}-{$num}";
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM school_enrollments WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetchColumn() == 0) {
                return $code;
            }
        }
        return "MAT-{$year}-" . time();
    }

    /**
     * Records an audit log entry for status transitions and key events.
     */
    public static function logAudit(
        PDO $pdo,
        string $enrollmentId,
        string $action,
        ?string $prevStatus,
        ?string $newStatus,
        ?string $userId = null,
        ?string $notes = null
    ): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $id = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $stmt = $pdo->prepare("
            INSERT INTO enrollment_audit_logs 
            (id, enrollment_id, user_id, action, previous_status, new_status, notes, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$id, $enrollmentId, $userId, $action, $prevStatus, $newStatus, $notes, $ip]);
    }
}
