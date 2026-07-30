<?php
namespace App\Services;

class GoogleAuthService {
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct() {
        if (!defined('GOOGLE_CLIENT_ID')) {
            require_once __DIR__ . '/../config/config.php';
        }
        $this->clientId = GOOGLE_CLIENT_ID;
        $this->clientSecret = GOOGLE_CLIENT_SECRET;
        $this->redirectUri = GOOGLE_REDIRECT_URI;
    }

    /**
     * Comprueba si las credenciales de Google OAuth 2.0 están configuradas
     */
    public function isConfigured(): bool {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Genera la URL de autorización de Google Sign-In
     */
    public function getAuthUrl(): string {
        if (!$this->isConfigured()) {
            return '#';
        }
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Intercambia el código de autorización por el perfil de usuario de Google
     */
    public function getGoogleUser(string $code): ?array {
        if (!$this->isConfigured()) return null;

        // 1. Obtener Access Token de Google
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postFields = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $tokenResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$tokenResponse) {
            error_log("GoogleAuthService token request failed: " . $tokenResponse);
            return null;
        }

        $tokenData = json_decode($tokenResponse, true);
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) return null;

        // 2. Obtener Perfil de Usuario con el Access Token
        $userinfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userinfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $userResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$userResponse) {
            return null;
        }

        $googleProfile = json_decode($userResponse, true);

        return [
            'google_id' => $googleProfile['id'] ?? null,
            'email' => $googleProfile['email'] ?? null,
            'given_name' => $googleProfile['given_name'] ?? ($googleProfile['name'] ?? 'Usuario'),
            'family_name' => $googleProfile['family_name'] ?? '',
            'picture' => $googleProfile['picture'] ?? null
        ];
    }
}
