<?php

namespace App\Controllers;

use App\Auth\JWT;
use App\Middleware\AuthMiddleware;
use App\Router;
use App\Services\DatabaseSeeder;

class AuthController {
    private \PDO $db;
    private array $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/config.php';
        require_once __DIR__ . '/../../config/database.php';
        $this->db = getDBConnection($this->config);
    }

    public function register(): void {
        $input = Router::getJsonInput();

        $fullName = trim($input['full_name'] ?? '');
        $email    = strtolower(trim($input['email'] ?? ''));
        $phone    = trim($input['phone'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
            Router::json(400, false, 'Todos los campos son obligatorios: full_name, email, phone, password');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Router::json(400, false, 'El correo electrónico no es válido');
        }

        if (strlen($password) < 6) {
            Router::json(400, false, 'La contraseña debe tener al menos 6 caracteres');
        }

        // Check if user already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Router::json(400, false, 'El correo electrónico ya se encuentra registrado');
        }

        $userId = DatabaseSeeder::uuid();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO users (id, full_name, email, phone, password_hash, role, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'student', 1, ?, ?)
        ");
        $stmt->execute([$userId, $fullName, $email, $phone, $hash, $now, $now]);

        // Auto-enroll in default course
        $stmtCourse = $this->db->query("SELECT id FROM courses WHERE is_active = 1 LIMIT 1");
        $courseId = $stmtCourse->fetchColumn();
        if ($courseId) {
            $enrollmentId = DatabaseSeeder::uuid();
            $stmtEnroll = $this->db->prepare("
                INSERT INTO enrollments (id, user_id, course_id, status, enrolled_at, created_at, updated_at)
                VALUES (?, ?, ?, 'pending_contract', ?, ?, ?)
            ");
            $stmtEnroll->execute([$enrollmentId, $userId, $courseId, $now, $now, $now]);
        }

        $token = JWT::generate([
            'user_id' => $userId,
            'email'   => $email,
            'role'    => 'student'
        ], $this->config['jwt_secret'], $this->config['jwt_expiration_hours']);

        Router::json(201, true, 'Registro exitoso', [
            'token' => $token,
            'user'  => [
                'id'         => $userId,
                'full_name'  => $fullName,
                'email'      => $email,
                'phone'      => $phone,
                'role'       => 'student',
                'is_active'  => true,
                'created_at' => $now
            ]
        ]);
    }

    public function login(): void {
        $input = Router::getJsonInput();

        $email    = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            Router::json(400, false, 'Debe ingresar correo y contraseña');
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Router::json(401, false, 'Credenciales inválidas');
        }

        if (!$user['is_active']) {
            Router::json(403, false, 'Su cuenta está inactiva. Contacte al administrador.');
        }

        $token = JWT::generate([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role']
        ], $this->config['jwt_secret'], $this->config['jwt_expiration_hours']);

        unset($user['password_hash']);

        Router::json(200, true, 'Inicio de sesión exitoso', [
            'token' => $token,
            'user'  => $user
        ]);
    }

    public function me(): void {
        $auth = AuthMiddleware::authenticate($this->config);
        $stmt = $this->db->prepare("SELECT id, full_name, email, phone, role, is_active, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$auth['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            Router::json(404, false, 'Usuario no encontrado');
        }

        Router::json(200, true, 'Perfil obtenido', $user);
    }

    /**
     * GET /api/v1/auth/activate?token=xxx
     * Verifica si el token de activación es válido (para mostrar el formulario de contraseña en el frontend).
     */
    public function activateAccount(): void {
        $token = trim($_GET['token'] ?? '');

        if (empty($token)) {
            Router::json(400, false, 'Token de activación requerido');
        }

        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            SELECT id, full_name, email FROM users
            WHERE activation_token = ? AND activation_token_expires_at > ? AND is_active = 0
            LIMIT 1
        ");
        $stmt->execute([$token, $now]);
        $user = $stmt->fetch();

        if (!$user) {
            Router::json(400, false, 'El enlace de activación es inválido o ya expiró. Solicita uno nuevo al administrador.');
        }

        Router::json(200, true, 'Token válido', [
            'user_id'   => $user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
        ]);
    }

    /**
     * POST /api/v1/auth/set-password
     * Establece la contraseña del estudiante y activa su cuenta.
     * Body: { token, password, password_confirmation }
     */
    public function setPassword(): void {
        $input    = Router::getJsonInput();
        $token    = trim($input['token'] ?? '');
        $password = $input['password'] ?? '';
        $confirm  = $input['password_confirmation'] ?? '';

        if (empty($token) || empty($password)) {
            Router::json(400, false, 'El token y la contraseña son obligatorios');
        }

        if (strlen($password) < 8) {
            Router::json(400, false, 'La contraseña debe tener al menos 8 caracteres');
        }

        if ($password !== $confirm) {
            Router::json(400, false, 'Las contraseñas no coinciden');
        }

        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, role FROM users
            WHERE activation_token = ? AND activation_token_expires_at > ? AND is_active = 0
            LIMIT 1
        ");
        $stmt->execute([$token, $now]);
        $user = $stmt->fetch();

        if (!$user) {
            Router::json(400, false, 'El enlace de activación es inválido o ya expiró');
        }

        // Activar cuenta y guardar contraseña
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->db->prepare("
            UPDATE users
            SET password_hash = ?, is_active = 1,
                activation_token = NULL, activation_token_expires_at = NULL,
                updated_at = ?
            WHERE id = ?
        ")->execute([$hash, $now, $user['id']]);

        // Generar JWT para auto-login
        $jwtToken = JWT::generate([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ], $this->config['jwt_secret'], $this->config['jwt_expiration_hours']);

        Router::json(200, true, '¡Cuenta activada! Ya puedes acceder a la plataforma.', [
            'token' => $jwtToken,
            'user'  => [
                'id'        => $user['id'],
                'full_name' => $user['full_name'],
                'email'     => $user['email'],
                'role'      => $user['role'],
            ],
        ]);
    }
}
