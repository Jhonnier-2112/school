<?php
namespace App\Services;

use App\Config\Database;
use App\Models\Role;
use PDO;
use PDOException;

class TokenAuthService {
    private $secret;
    private $tokenExpiration;
    private $refreshTokenExpiration;

    public function __construct() {
        if (!defined('JWT_SECRET')) {
            require_once __DIR__ . '/../config/config.php';
        }
        $this->secret = JWT_SECRET;
        $this->tokenExpiration = defined('TOKEN_EXPIRATION') ? TOKEN_EXPIRATION : 3600; // 1 Hora (3600s)
        $this->refreshTokenExpiration = defined('REFRESH_TOKEN_EXPIRATION') ? REFRESH_TOKEN_EXPIRATION : 3600; // 1 Hora (3600s)
    }

    /**
     * Codificación Base64Url
     */
    private function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodificación Base64Url
     */
    private function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    /**
     * Genera un Access Token firmado con HMAC-SHA256 y caducidad de 1 Hora (3600 segundos)
     */
    public function generateAccessToken(array $userData): string {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);

        // Nombre completo del usuario
        $nombre = trim(($userData['nombres'] ?? '') . ' ' . ($userData['apellidos'] ?? ''));

        // Resolver nombre legible del rol a partir de role_id (UUID) o del ENUM legacy
        $roleId   = $userData['role_id'] ?? null;
        $roleName = $userData['role']    ?? 'runner';  // fallback al ENUM si no hay UUID aún
        if ($roleId) {
            $roleName = Role::isAdmin($roleId) ? 'administrador' : 'cliente';
        }

        $payload = json_encode([
            'user_id'   => $userData['id'],
            'nombre'    => $nombre,              // nombre completo
            'email'     => $userData['email'],   // correo
            'rol'       => $roleName,            // slug legible del rol
            'role_id'   => $roleId,              // UUID del rol (null si aún no migrado)
            // campos legacy mantenidos por compatibilidad
            'nombres'   => $userData['nombres']   ?? '',
            'apellidos' => $userData['apellidos'] ?? '',
            'role'      => $userData['role']      ?? 'runner',
            'iat'       => time(),
            'exp'       => time() + $this->tokenExpiration // 1 Hora
        ]);

        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    /**
     * Genera y almacena en base de datos un Refresh Token con caducidad de 1 Hora (3600 segundos)
     */
    public function generateRefreshToken(int $userId): string {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        try {
            $database = new Database();
            $db = $database->getConnection();

            $sql = "INSERT INTO user_tokens (user_id, token_type, token_hash, expires_at, is_revoked, created_at)
                    VALUES (:user_id, 'refresh_token', :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR), 0, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':token_hash' => $tokenHash
            ]);

            return $rawToken;
        } catch (PDOException $e) {
            error_log("TokenAuthService::generateRefreshToken Error: " . $e->getMessage());
            return $rawToken;
        }
    }

    /**
     * Genera un par completo de Access Token y Refresh Token (ambos con vigencia de 1 hora)
     * y los guarda en cookies HTTP-Only de seguridad.
     */
    public function issueTokenPair(array $userData): array {
        $accessToken = $this->generateAccessToken($userData);
        $refreshToken = $this->generateRefreshToken($userData['id']);

        $cookieOptions = [
            'expires' => time() + $this->tokenExpiration, // 1 hora
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        setcookie('access_token', $accessToken, $cookieOptions);
        setcookie('refresh_token', $refreshToken, array_merge($cookieOptions, ['expires' => time() + $this->refreshTokenExpiration]));

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->tokenExpiration,
            'token_type' => 'Bearer'
        ];
    }

    /**
     * Valida la firma y expiración de un Access Token
     */
    public function validateAccessToken(string $jwt): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        list($header, $payload, $signature) = $parts;

        $validSignature = $this->base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, $this->secret, true));

        if (!hash_equals($validSignature, $signature)) {
            return null; // Firma inválida
        }

        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);
        if (!$decodedPayload || empty($decodedPayload['exp']) || $decodedPayload['exp'] < time()) {
            return null; // Token expirado
        }

        return $decodedPayload;
    }

    /**
     * Valida un Refresh Token en la base de datos
     */
    public function validateRefreshToken(string $rawToken): ?array {
        $tokenHash = hash('sha256', $rawToken);
        try {
            $database = new Database();
            $db = $database->getConnection();

            $sql = "SELECT id, user_id, expires_at FROM user_tokens 
                    WHERE token_hash = :hash AND token_type = 'refresh_token' AND is_revoked = 0 AND expires_at > NOW() LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':hash' => $tokenHash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Renueva el par de tokens utilizando un Refresh Token válido
     */
    public function refreshTokens(string $rawRefreshToken, array $userModelData): ?array {
        $validTokenRecord = $this->validateRefreshToken($rawRefreshToken);
        if (!$validTokenRecord) {
            return null;
        }

        // Revocar el refresh token anterior (prevención de reuso)
        $this->revokeRefreshToken($rawRefreshToken);

        // Emitir nuevo par de tokens
        return $this->issueTokenPair($userModelData);
    }

    /**
     * Revoca un Refresh Token específico
     */
    public function revokeRefreshToken(string $rawToken): bool {
        $tokenHash = hash('sha256', $rawToken);
        try {
            $database = new Database();
            $db = $database->getConnection();

            $stmt = $db->prepare("UPDATE user_tokens SET is_revoked = 1 WHERE token_hash = :hash");
            return $stmt->execute([':hash' => $tokenHash]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Elimina las cookies de autenticación al cerrar sesión
     */
    public function clearTokenCookies() {
        setcookie('access_token', '', time() - 3600, '/');
        setcookie('refresh_token', '', time() - 3600, '/');
    }
}
