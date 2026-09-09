<?php

namespace App\Middleware;

use App\Auth\JWT;

class AuthMiddleware {
    public static function authenticate(array $config, ?string $requiredRole = null): array {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (empty($authHeader) || !preg_match('/^Bearer\s+(.*?)$/i', $authHeader, $matches)) {
            self::jsonError(401, 'Cabecera de autorización requerida (Bearer <token>)');
        }

        $token = $matches[1];
        $payload = JWT::validate($token, $config['jwt_secret']);

        if (!$payload) {
            self::jsonError(401, 'Token inválido o expirado');
        }

        if ($requiredRole !== null && ($payload['role'] ?? '') !== $requiredRole) {
            self::jsonError(403, 'Acceso denegado: se requieren permisos de ' . $requiredRole);
        }

        return $payload;
    }

    private static function jsonError(int $code, string $message): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'error'   => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
