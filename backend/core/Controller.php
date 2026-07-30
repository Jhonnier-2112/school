<?php
namespace App\Core;

use App\Services\TokenAuthService;

class Controller {
    /**
     * Renderiza una vista pasando un conjunto de datos opcionales
     */
    protected function view(string $viewPath, array $data = []) {
        extract($data);
        $file = __DIR__ . '/../views/' . $viewPath . '.php';
        if (file_exists($file)) {
            require $file;
        } else {
            http_response_code(404);
            echo "Vista no encontrada: " . htmlspecialchars($viewPath);
        }
    }

    /**
     * Devuelve una respuesta JSON
     */
    protected function json(array $data, int $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirecciona a una URL
     */
    protected function redirect(string $url) {
        header("Location: $url");
        exit;
    }

    /**
     * Devuelve los datos del usuario autenticado vía sesión o Token JWT / Refresh Token (1 hora)
     */
    protected function currentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Si hay sesión activa
        if (!empty($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'nombres' => $_SESSION['user_nombres'] ?? '',
                'apellidos' => $_SESSION['user_apellidos'] ?? '',
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['user_role'] ?? 'runner',
                'numero_documento' => $_SESSION['user_documento'] ?? ''
            ];
        }

        // 2. Si hay Token JWT o Cookie access_token de 1 hora
        $token = null;
        if (!empty($_COOKIE['access_token'])) {
            $token = $_COOKIE['access_token'];
        } else {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            if (str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            }
        }

        if ($token) {
            $tokenService = new TokenAuthService();
            $decodedPayload = $tokenService->validateAccessToken($token);
            if ($decodedPayload) {
                $_SESSION['user_id'] = $decodedPayload['user_id'];
                $_SESSION['user_nombres'] = $decodedPayload['nombres'];
                $_SESSION['user_apellidos'] = $decodedPayload['apellidos'];
                $_SESSION['user_email'] = $decodedPayload['email'];
                $_SESSION['user_role'] = $decodedPayload['role'];

                return [
                    'id' => $decodedPayload['user_id'],
                    'nombres' => $decodedPayload['nombres'],
                    'apellidos' => $decodedPayload['apellidos'],
                    'email' => $decodedPayload['email'],
                    'role' => $decodedPayload['role'],
                    'numero_documento' => ''
                ];
            }
        }

        return null;
    }

    /**
     * Verifica si hay un usuario autenticado
     */
    protected function isLoggedIn(): bool {
        return $this->currentUser() !== null;
    }

    /**
     * Exige autenticación de usuario
     */
    protected function requireAuth() {
        if (!$this->isLoggedIn()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                $this->json(['success' => false, 'message' => 'Debe iniciar sesión para realizar esta acción.'], 401);
            } else {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
                $this->redirect('/login');
            }
        }
    }

    /**
     * Exige rol de administrador
     */
    protected function requireAdmin() {
        $this->requireAuth();
        $user = $this->currentUser();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo "Acceso denegado. Se requieren permisos de administrador.";
            exit;
        }
    }
}
