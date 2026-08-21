<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;


class User {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Registra un nuevo usuario en la base de datos
     */
    public static function create(array $data) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Determinar role_id UUID basándose en el campo 'role' recibido
            $roleEnum = $data['role'] ?? 'runner';
            $roleId   = ($roleEnum === 'admin')
                ? Role::ADMIN_ID
                : Role::CLIENTE_ID;
            // Permitir override explícito de role_id
            if (!empty($data['role_id'])) {
                $roleId = $data['role_id'];
            }

            $sql = "INSERT INTO users (
                nombres, apellidos, tipo_documento, numero_documento,
                email, password, telefono, direccion, municipio, departamento,
                fecha_nacimiento, genero, eps, grupo_sanguineo, rh, role_id, google_id, avatar, status, created_at
            ) VALUES (
                :nombres, :apellidos, :tipo_documento, :numero_documento,
                :email, :password, :telefono, :direccion, :municipio, :departamento,
                :fecha_nacimiento, :genero, :eps, :grupo_sanguineo, :rh, :role_id, :google_id, :avatar, 1, NOW()
            )";

            $hashedPassword = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

            $insertData = [
                ':nombres'          => $data['nombres'],
                ':apellidos'        => $data['apellidos'] ?? '',
                ':tipo_documento'   => $data['tipo_documento'] ?? 'CC',
                ':numero_documento' => $data['numero_documento'] ?? ('GOOG-' . substr(uniqid(), -6)),
                ':email'            => strtolower(trim($data['email'])),
                ':password'         => $hashedPassword,
                ':telefono'         => $data['telefono'] ?? '',
                ':direccion'        => $data['direccion'] ?? '',
                ':municipio'        => $data['municipio'] ?? 'Cali',
                ':departamento'     => $data['departamento'] ?? 'Valle del Cauca',
                ':fecha_nacimiento' => !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
                ':genero'           => !empty($data['genero']) ? $data['genero'] : null,
                ':eps'              => !empty($data['eps']) ? $data['eps'] : null,
                ':grupo_sanguineo'  => !empty($data['grupo_sanguineo']) ? $data['grupo_sanguineo'] : null,
                ':rh'               => !empty($data['rh']) ? $data['rh'] : null,
                ':role_id'          => $roleId,
                ':google_id'        => $data['google_id'] ?? null,
                ':avatar'           => $data['avatar'] ?? null
            ];

            $stmt = $db->prepare($sql);
            if ($stmt->execute($insertData)) {
                return $db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("User::create() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca usuario por email
     */
    public function findByEmail(string $email) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email AND status = 1 LIMIT 1");
            $stmt->execute([':email' => strtolower(trim($email))]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Busca usuario por Google ID
     */
    public function findByGoogleId(string $googleId) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE google_id = :google_id AND status = 1 LIMIT 1");
            $stmt->execute([':google_id' => trim($googleId)]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Busca o crea un usuario autenticado con Google OAuth
     */
    public function findOrCreateFromGoogle(array $googleProfile) {
        $googleId = $googleProfile['google_id'] ?? '';
        $email = strtolower(trim($googleProfile['email'] ?? ''));

        if (empty($email)) return false;

        // 1. Buscar por Google ID
        $existing = $this->findByGoogleId($googleId);
        if ($existing) return $existing;

        // 2. Buscar por Email y vincular Google ID
        $existingEmail = $this->findByEmail($email);
        if ($existingEmail) {
            $stmt = $this->conn->prepare("UPDATE users SET google_id = :gid, avatar = :avatar WHERE id = :id");
            $stmt->execute([':gid' => $googleId, ':avatar' => $googleProfile['picture'] ?? null, ':id' => $existingEmail['id']]);
            $existingEmail['google_id'] = $googleId;
            $existingEmail['avatar'] = $googleProfile['picture'] ?? null;
            return $existingEmail;
        }

        // 3. Crear nuevo usuario desde Google
        $newData = [
            'nombres' => $googleProfile['given_name'] ?? 'Usuario',
            'apellidos' => $googleProfile['family_name'] ?? 'Google',
            'email' => $email,
            'google_id' => $googleId,
            'avatar' => $googleProfile['picture'] ?? null,
            'role' => 'runner'
        ];

        $userId = self::create($newData);
        if ($userId) {
            return $this->findById($userId);
        }

        return false;
    }

    /**
     * Busca usuario por número de documento
     */
    public function findByDocument(string $numeroDocumento) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE numero_documento = :numero_documento AND status = 1 LIMIT 1");
            $stmt->execute([':numero_documento' => trim($numeroDocumento)]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Busca usuario por ID
     */
    public function findById(int $id) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT id, nombres, apellidos, tipo_documento, numero_documento, email,
                        telefono, direccion, municipio, departamento, fecha_nacimiento,
                        genero, eps, grupo_sanguineo, rh, role, role_id, google_id, avatar, created_at
                 FROM users WHERE id = :id AND status = 1 LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Autentica usuario por email o número de documento y contraseña
     */
    public function authenticate(string $loginInput, string $password) {
        $loginInput = trim($loginInput);
        
        // Buscar por email o documento
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE (email = :input OR numero_documento = :input) AND status = 1 LIMIT 1");
        $stmt->execute([':input' => $loginInput]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return false;
    }

    /**
     * Paginación y búsqueda de usuarios con filtros para administración
     */
    public function paginateUsers(int $page = 1, int $perPage = 20, array $filters = []): array {
        $offset = max(0, ($page - 1) * $perPage);
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $s = "%{$filters['search']}%";
            $where[] = "(nombres LIKE :search OR apellidos LIKE :search OR email LIKE :search OR numero_documento LIKE :search)";
            $params[':search'] = $s;
        }

        if (!empty($filters['role'])) {
            if ($filters['role'] === 'admin') {
                $where[] = "(role = 'admin' OR role_id = 'a1b2c3d4-0002-0002-0002-000000000002')";
            } elseif ($filters['role'] === 'client') {
                $where[] = "(role = 'runner' OR role = 'cliente' OR role_id = 'a1b2c3d4-0001-0001-0001-000000000001' OR role_id IS NULL)";
            }
        }

        $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        try {
            // Total
            $countSql = "SELECT COUNT(*) as total FROM users $whereSql";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute($params);
            $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // Fetch
            $sql = "SELECT id, nombres, apellidos, tipo_documento, numero_documento, email, telefono, direccion, municipio, departamento, eps, grupo_sanguineo, rh, role, role_id, status, created_at 
                    FROM users $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'items' => $items,
                'total' => $total,
                'pages' => $perPage ? (int)ceil($total / $perPage) : 1
            ];
        } catch (PDOException $e) {
            return [
                'items' => [],
                'total' => 0,
                'pages' => 1
            ];
        }
    }

    /**
     * Obtiene el listado completo de usuarios paginado (para panel admin)
     */
    public function getAll(int $limit = 20, int $offset = 0) {
        try {
            $stmt = $this->conn->prepare("SELECT id, nombres, apellidos, tipo_documento, numero_documento, email, telefono, direccion, municipio, departamento, role, status, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Total de usuarios registrados
     */
    public function countAll(): int {
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) AS total FROM users");
            return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Actualiza la información de un usuario por parte de un administrador
     */
    public function adminUpdateUser(int $id, array $data): bool {
        try {
            $sql = "UPDATE users SET 
                nombres = :nombres,
                apellidos = :apellidos,
                tipo_documento = :tipo_documento,
                numero_documento = :numero_documento,
                email = :email,
                telefono = :telefono,
                direccion = :direccion,
                municipio = :municipio,
                departamento = :departamento,
                eps = :eps,
                grupo_sanguineo = :grupo_sanguineo,
                rh = :rh,
                role = :role,
                role_id = :role_id
            WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':tipo_documento' => $data['tipo_documento'],
                ':numero_documento' => $data['numero_documento'],
                ':email' => strtolower(trim($data['email'])),
                ':telefono' => $data['telefono'],
                ':direccion' => $data['direccion'],
                ':municipio' => $data['municipio'],
                ':departamento' => $data['departamento'],
                ':eps' => $data['eps'] ?? null,
                ':grupo_sanguineo' => $data['grupo_sanguineo'] ?? null,
                ':rh' => $data['rh'] ?? null,
                ':role' => $data['role'],
                ':role_id' => $data['role_id'],
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("User::adminUpdateUser Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza perfil de usuario
     */
    public function updateProfile(int $id, array $data): bool {
        try {
            $sql = "UPDATE users SET 
                nombres = :nombres,
                apellidos = :apellidos,
                telefono = :telefono,
                direccion = :direccion,
                municipio = :municipio,
                departamento = :departamento,
                eps = :eps,
                grupo_sanguineo = :grupo_sanguineo,
                rh = :rh
                WHERE id = :id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':telefono' => $data['telefono'],
                ':direccion' => $data['direccion'],
                ':municipio' => $data['municipio'] ?? 'Cali',
                ':departamento' => $data['departamento'] ?? 'Valle del Cauca',
                ':eps' => $data['eps'] ?? null,
                ':grupo_sanguineo' => $data['grupo_sanguineo'] ?? null,
                ':rh' => $data['rh'] ?? null,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Valida los datos recibidos del formulario de registro
     */
    public function validateRegistrationData(array $data): array {
        $errors = [];

        if (empty($data['nombres'])) {
            $errors[] = 'El nombre es obligatorio.';
        }
        if (empty($data['apellidos'])) {
            $errors[] = 'Los apellidos son obligatorios.';
        }
        if (empty($data['numero_documento'])) {
            $errors[] = 'El número de documento es obligatorio.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingrese un correo electrónico válido.';
        }
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
        }
        if (empty($data['telefono'])) {
            $errors[] = 'El número de teléfono/celular es obligatorio.';
        }
        if (empty($data['direccion'])) {
            $errors[] = 'La dirección es obligatoria para envíos y compras.';
        }

        return $errors;
    }

    /**
     * Genera un token único de restauración de contraseña con validez de 1 hora
     */
    public function createPasswordResetToken(string $email): string|false {
        try {
            $user = $this->findByEmail($email);
            if (!$user) {
                return false;
            }

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $this->conn->prepare("UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE id = :id");
            $result = $stmt->execute([
                ':token' => $token,
                ':expires' => $expires,
                ':id' => $user['id']
            ]);

            return $result ? $token : false;
        } catch (PDOException $e) {
            error_log("User::createPasswordResetToken() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica la validez de un token de restauración de contraseña
     */
    public function verifyPasswordResetToken(string $token): array|false {
        try {
            if (empty($token)) return false;

            $stmt = $this->conn->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW() AND status = 1 LIMIT 1");
            $stmt->execute([':token' => trim($token)]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log("User::verifyPasswordResetToken() Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la contraseña mediante un token válido y limpia las credenciales temporales
     */
    public function updatePasswordByToken(string $token, string $newPassword): bool {
        try {
            $user = $this->verifyPasswordResetToken($token);
            if (!$user) {
                return false;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expires = NULL WHERE id = :id");
            return $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $user['id']
            ]);
        } catch (PDOException $e) {
            error_log("User::updatePasswordByToken() Error: " . $e->getMessage());
            return false;
        }
    }
}
