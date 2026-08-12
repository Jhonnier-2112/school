<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Services\TokenAuthService;

class AuthController extends Controller {

    /**
     * Muestra el formulario de inicio de sesión
     */
    public function showLogin() {
        if ($this->isLoggedIn()) {
            $this->redirect('/perfil');
        }
        $googleService = new GoogleAuthService();
        $googleAuthUrl = $googleService->getAuthUrl();

        $this->view('auth/login', ['googleAuthUrl' => $googleAuthUrl]);
    }

    /**
     * Procesar inicio de sesión
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
        }

        $loginInput = $_POST['login_input'] ?? '';
        $password = $_POST['password'] ?? '';
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (empty($loginInput) || empty($password)) {
            $msg = 'Por favor ingresa tu correo/documento y contraseña.';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $msg], 400);
            }
            $this->view('auth/login', ['error' => $msg, 'loginInput' => $loginInput]);
            return;
        }

        $userModel = new User();
        $user = $userModel->authenticate($loginInput, $password);

        if ($user) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombres'] = $user['nombres'];
            $_SESSION['user_apellidos'] = $user['apellidos'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_documento'] = $user['numero_documento'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_role_id'] = $user['role_id'] ?? null;

            // Registrar log de auditoría
            \App\Services\AuditLogService::log('USER_LOGIN', 'Inicio de sesión exitoso por credenciales tradicionales para ' . $user['email'], null, (int)$user['id']);

            // Generar Access Token y Refresh Token con vencimiento de 1 Hora (3600s)
            $tokenService = new TokenAuthService();
            $tokenData = $tokenService->issueTokenPair($user);

            $redirectTo = $_SESSION['redirect_after_login'] ?? '/perfil';
            unset($_SESSION['redirect_after_login']);

            if ($isAjax) {
                $this->json([
                    'success' => true, 
                    'message' => '¡Bienvenido de nuevo!', 
                    'redirect' => $redirectTo,
                    'tokens' => $tokenData
                ]);
            }
            $this->redirect($redirectTo);
        } else {
            // Registrar log de auditoría
            \App\Services\AuditLogService::log('USER_LOGIN_FAILED', 'Intento fallido de inicio de sesión para el identificador: ' . $loginInput);

            $msg = 'Credenciales incorrectas. Verifica tu correo/documento y contraseña.';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $msg], 401);
            }
            $this->view('auth/login', ['error' => $msg, 'loginInput' => $loginInput]);
        }
    }

    /**
     * Muestra el formulario de registro de usuario
     */
    public function showRegister() {
        if ($this->isLoggedIn()) {
            $this->redirect('/perfil');
        }
        $googleService = new GoogleAuthService();
        $googleAuthUrl = $googleService->getAuthUrl();

        $this->view('auth/register', ['googleAuthUrl' => $googleAuthUrl]);
    }

    /**
     * Procesa el registro de un nuevo usuario
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/registro');
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $data = [
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'tipo_documento' => $_POST['tipo_documento'] ?? 'CC',
            'numero_documento' => trim($_POST['numero_documento'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'municipio' => trim($_POST['municipio'] ?? 'Cali'),
            'departamento' => trim($_POST['departamento'] ?? 'Valle del Cauca'),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'genero' => $_POST['genero'] ?? null,
            'eps' => trim($_POST['eps'] ?? ''),
            'grupo_sanguineo' => $_POST['grupo_sanguineo'] ?? '',
            'rh' => $_POST['rh'] ?? '',
            'role' => 'runner'
        ];

        $userModel = new User();
        $errors = $userModel->validateRegistrationData($data);

        // Verificar si email ya existe
        if (!empty($data['email']) && $userModel->findByEmail($data['email'])) {
            $errors[] = 'El correo electrónico ya está registrado en la plataforma.';
        }

        // Verificar si documento ya existe
        if (!empty($data['numero_documento']) && $userModel->findByDocument($data['numero_documento'])) {
            $errors[] = 'El número de documento ya tiene una cuenta asociada.';
        }

        if (!empty($errors)) {
            if ($isAjax) {
                $this->json(['success' => false, 'errors' => $errors, 'message' => implode('<br>', $errors)], 400);
            }
            $this->view('auth/register', ['errors' => $errors, 'data' => $data]);
            return;
        }

        $userId = User::create($data);

        if ($userId) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_nombres'] = $data['nombres'];
            $_SESSION['user_apellidos'] = $data['apellidos'];
            $_SESSION['user_email'] = $data['email'];
            $_SESSION['user_documento'] = $data['numero_documento'];
            $_SESSION['user_role'] = 'runner';
            $_SESSION['user_role_id'] = \App\Models\Role::CLIENTE_ID;

            // Registrar log de auditoría
            \App\Services\AuditLogService::log('USER_REGISTER', 'Nuevo usuario registrado en la plataforma: ' . $data['email'], ['nombres' => $data['nombres'], 'apellidos' => $data['apellidos']], (int)$userId);

            // Generar Access Token y Refresh Token de 1 Hora (3600s)
            $tokenService = new TokenAuthService();
            $userRecord = array_merge(['id' => $userId], $data);
            $tokenData = $tokenService->issueTokenPair($userRecord);

            $redirectTo = $_SESSION['redirect_after_login'] ?? '/perfil';
            unset($_SESSION['redirect_after_login']);

            if ($isAjax) {
                $this->json([
                    'success' => true, 
                    'message' => '¡Registro exitoso! Bienvenido a FemTribe Runner.', 
                    'redirect' => $redirectTo,
                    'tokens' => $tokenData
                ]);
            }
            $this->redirect($redirectTo);
        } else {
            $msg = 'Ocurrió un error al crear la cuenta. Por favor intenta de nuevo.';
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $msg], 500);
            }
            $this->view('auth/register', ['errors' => [$msg], 'data' => $data]);
        }
    }

    /**
     * Redirecciona al flujo de autenticación oficial de Google OAuth 2.0
     */
    public function redirectToGoogle() {
        $googleService = new GoogleAuthService();
        $authUrl = $googleService->getAuthUrl();

        if ($authUrl === '#') {
            http_response_code(500);
            echo "Google OAuth 2.0 no está configurado aún en el archivo .env";
            return;
        }

        $this->redirect($authUrl);
    }

    /**
     * Recibe la respuesta de Google OAuth 2.0 y autentica/registra al usuario
     */
    public function handleGoogleCallback() {
        $code = $_GET['code'] ?? null;
        if (!$code) {
            $this->redirect('/login');
        }

        $googleService = new GoogleAuthService();
        $googleUser = $googleService->getGoogleUser($code);

        if (!$googleUser || empty($googleUser['email'])) {
            $this->view('auth/login', ['error' => 'No se pudo obtener el perfil de Google. Intenta nuevamente.']);
            return;
        }

        $userModel = new User();
        $user = $userModel->findOrCreateFromGoogle($googleUser);

        if ($user) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombres'] = $user['nombres'];
            $_SESSION['user_apellidos'] = $user['apellidos'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_documento'] = $user['numero_documento'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_role_id'] = $user['role_id'] ?? null;

            // Registrar log de auditoría
            \App\Services\AuditLogService::log('USER_LOGIN_GOOGLE', 'Inicio de sesión exitoso vía Google OAuth para ' . $user['email'], null, (int)$user['id']);

            // Emitir Access Token y Refresh Token de 1 Hora (3600s)
            $tokenService = new TokenAuthService();
            $tokenService->issueTokenPair($user);

            $redirectTo = $_SESSION['redirect_after_login'] ?? '/perfil';
            unset($_SESSION['redirect_after_login']);

            $this->redirect($redirectTo);
        } else {
            $this->view('auth/login', ['error' => 'Error al crear/autenticar cuenta con Google.']);
        }
    }

    /**
     * Renovación dinámica de Access Token y Refresh Token (Endpoint de 1 Hora)
     */
    public function refreshToken() {
        $rawRefreshToken = $_COOKIE['refresh_token'] ?? $_POST['refresh_token'] ?? null;
        if (!$rawRefreshToken) {
            $this->json(['success' => false, 'message' => 'Refresh token no proporcionado.'], 401);
        }

        $tokenService = new TokenAuthService();
        $validRecord = $tokenService->validateRefreshToken($rawRefreshToken);

        if (!$validRecord) {
            $tokenService->clearTokenCookies();
            $this->json(['success' => false, 'message' => 'Refresh token vencido o inválido. Inicia sesión de nuevo.'], 401);
        }

        $userModel = new User();
        $user = $userModel->findById($validRecord['user_id']);

        if (!$user) {
            $this->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $newTokenData = $tokenService->refreshTokens($rawRefreshToken, $user);

        $this->json([
            'success' => true,
            'message' => 'Tokens renovados exitosamente por 1 hora.',
            'tokens' => $newTokenData
        ]);
    }

    /**
     * Cierra la sesión activa y revoca tokens de seguridad
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $userEmail = $_SESSION['user_email'] ?? 'desconocido';

        if ($userId) {
            \App\Services\AuditLogService::log('USER_LOGOUT', 'Cierre de sesión para ' . $userEmail, null, (int)$userId);
        }

        $tokenService = new TokenAuthService();
        if (!empty($_COOKIE['refresh_token'])) {
            $tokenService->revokeRefreshToken($_COOKIE['refresh_token']);
        }
        $tokenService->clearTokenCookies();

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        $this->redirect('/');
    }

    /**
     * Muestra el perfil del usuario autenticado
     */
    public function profile() {
        $this->requireAuth();
        $currentUser = $this->currentUser();
        
        $userModel = new User();
        $user = $userModel->findById($currentUser['id']);

        $orderModel = new \App\Models\Order();
        $orders = $orderModel->getUserOrders($currentUser['id']);

        $this->view('auth/profile', ['user' => $user, 'orders' => $orders]);
    }

    /**
     * Actualiza la información del perfil del usuario
     */
    public function updateProfile() {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/perfil');
        }

        $currentUser = $this->currentUser();
        $userModel = new User();

        $data = [
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'municipio' => trim($_POST['municipio'] ?? 'Cali'),
            'departamento' => trim($_POST['departamento'] ?? 'Valle del Cauca'),
            'eps' => trim($_POST['eps'] ?? ''),
            'grupo_sanguineo' => $_POST['grupo_sanguineo'] ?? '',
            'rh' => $_POST['rh'] ?? ''
        ];

        $success = $userModel->updateProfile($currentUser['id'], $data);
        if ($success) {
            $_SESSION['user_nombres'] = $data['nombres'];
            $_SESSION['user_apellidos'] = $data['apellidos'];
            $_SESSION['profile_message'] = 'Perfil actualizado correctamente.';
        } else {
            $_SESSION['profile_error'] = 'No se pudo actualizar el perfil.';
        }

        $this->redirect('/perfil');
    }
}
