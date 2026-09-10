<?php

// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Serve static files when running under PHP built-in server
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Simple PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    try {
        require_once __DIR__ . '/vendor/autoload.php';
    } catch (\Throwable $e) {
        error_log('[Autoload Warning] Error cargando vendor/autoload.php: ' . $e->getMessage());
    }
}

use App\Controllers\AuthController;
use App\Controllers\StudentController;
use App\Controllers\AdminController;
use App\Controllers\EnrollmentController;
use App\Controllers\AdminEnrollmentController;
use App\Middleware\CorsMiddleware;
use App\Router;

// Handle CORS
CorsMiddleware::handle();

$router = new Router();

// Healthcheck
$router->get('/api/health', function () {
    $config = require __DIR__ . '/config/config.php';
    Router::json(200, true, 'ICFES Backend PHP está operativo', [
        'status'      => 'online',
        'environment' => $config['env'],
        'swagger'     => $config['base_url'] . '/swagger/index.html'
    ]);
});

// Redirect root to front or Swagger
$router->get('/', function () {
    $frontFile = __DIR__ . '/index.html';
    if (file_exists($frontFile)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($frontFile);
        exit;
    }
    $config = require __DIR__ . '/config/config.php';
    header('Location: ' . $config['base_url'] . '/swagger/index.html');
    exit;
});

// Ruta para activar cuenta con token enviado por correo
$router->get('/activar-cuenta', function () {
    $frontFile = __DIR__ . '/index.html';
    if (file_exists($frontFile)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($frontFile);
        exit;
    }
    $config = require __DIR__ . '/config/config.php';
    header('Location: ' . $config['base_url'] . '/');
    exit;
});

// Ruta para la App Móvil / Web del Estudiante
$router->get('/app', function () {
    $appFile = __DIR__ . '/app/index.html';
    if (file_exists($appFile)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($appFile);
        exit;
    }
    header('Location: /');
    exit;
});

// ─── ENDPOINT TEMPORAL: Promover usuario a admin ───────────────────────────────
// Eliminar este bloque después de usarlo.
$router->get('/setup/promote-admin', function () {
    $config = require __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';

    // Protección por clave secreta en query param
    $key = $_GET['key'] ?? '';
    if ($key !== 'icfes-setup-2026') {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
        exit;
    }

    $email = $_GET['email'] ?? 'jhonnierdamian@gmail.com';

    try {
        $db = getDBConnection($config);

        $stmt = $db->prepare("SELECT id, full_name, email, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Usuario '$email' no encontrado"]);
            exit;
        }

        $now = date('Y-m-d H:i:s');
        $db->prepare("UPDATE users SET role = 'admin', updated_at = ? WHERE email = ?")
           ->execute([$now, $email]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Rol actualizado a 'admin' para $email",
            'user'    => array_merge($user, ['role' => 'admin'])
        ]);
    } catch (\Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
});

// Swagger JSON endpoint
$router->get('/swagger/swagger.json', function () {
    $jsonFile = __DIR__ . '/swagger/swagger.json';
    if (file_exists($jsonFile)) {
        header('Content-Type: application/json; charset=utf-8');
        readfile($jsonFile);
        exit;
    }
    Router::json(404, false, 'swagger.json no encontrado');
});

// ==========================================
// 1. AUTENTICACIÓN
// ==========================================
$router->post('/api/v1/auth/register', [AuthController::class, 'register']);
$router->post('/api/v1/auth/login', [AuthController::class, 'login']);
$router->get('/api/v1/auth/me', [AuthController::class, 'me']);
$router->get('/api/v1/auth/activate', [AuthController::class, 'activateAccount']);
$router->post('/api/v1/auth/set-password', [AuthController::class, 'setPassword']);

// ==========================================
// 2. ESTUDIANTE (App Móvil)
// ==========================================
$router->get('/api/v1/student/contract', [StudentController::class, 'getContract']);
$router->post('/api/v1/student/contract/accept', [StudentController::class, 'acceptContract']);

$router->get('/api/v1/student/course-summary', [StudentController::class, 'getCourseSummary']);
$router->get('/api/v1/student/payments', [StudentController::class, 'getPayments']);

$router->post('/api/v1/student/document', [StudentController::class, 'uploadDocument']);
$router->get('/api/v1/student/document', [StudentController::class, 'getDocument']);

$router->get('/api/v1/student/exams', [StudentController::class, 'listExams']);
$router->post('/api/v1/student/exams/{id}/start', [StudentController::class, 'startExam']);
$router->post('/api/v1/student/exams/sessions/{session_id}/submit', [StudentController::class, 'submitExam']);
$router->post('/api/v1/student/exams/{session_id}/submit', [StudentController::class, 'submitExam']);
$router->get('/api/v1/student/exams/sessions/{session_id}/results', [StudentController::class, 'getExamResults']);
$router->get('/api/v1/student/exams/{session_id}/results', [StudentController::class, 'getExamResults']);
$router->get('/api/v1/student/exams/history', [StudentController::class, 'getExamHistory']);

// ==========================================
// 3. ADMINISTRADOR (Panel Web)
// ==========================================
$router->get('/api/v1/admin/dashboard', [AdminController::class, 'getDashboard']);
$router->get('/api/v1/admin/students', [AdminController::class, 'listStudents']);
$router->get('/api/v1/admin/students/{id}', [AdminController::class, 'getStudentDetail']);

// Gestión completa de usuarios
$router->get('/api/v1/admin/users', [AdminController::class, 'listStudents']);
$router->get('/api/v1/admin/users/{id}', [AdminController::class, 'getUserDetail']);
$router->put('/api/v1/admin/users/{id}', [AdminController::class, 'updateUser']);
$router->delete('/api/v1/admin/users/{id}', [AdminController::class, 'disableUser']);
$router->put('/api/v1/admin/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);

// Gestión de pagos y abonos
$router->post('/api/v1/admin/payments', [AdminController::class, 'registerPayment']);
$router->get('/api/v1/admin/payments', [AdminController::class, 'listPayments']);
$router->get('/api/v1/admin/payments/{id}', [AdminController::class, 'getPaymentDetail']);
$router->put('/api/v1/admin/payments/{id}', [AdminController::class, 'updatePayment']);
$router->delete('/api/v1/admin/payments/{id}', [AdminController::class, 'disablePayment']);
$router->put('/api/v1/admin/payments/{id}/toggle-status', [AdminController::class, 'togglePaymentStatus']);

$router->get('/api/v1/admin/documents/pending', [AdminController::class, 'listPendingDocuments']);
$router->post('/api/v1/admin/documents/{id}/review', [AdminController::class, 'reviewDocument']);

// Invitación de estudiante (crea cuenta + envía email de activación)
$router->post('/api/v1/admin/students/invite', [AdminController::class, 'inviteStudent']);


$router->get('/api/v1/admin/subjects', [AdminController::class, 'listSubjects']);
$router->post('/api/v1/admin/questions', [AdminController::class, 'createQuestion']);
$router->get('/api/v1/admin/questions', [AdminController::class, 'listQuestions']);
$router->get('/api/v1/admin/questions/{id}', [AdminController::class, 'getQuestion']);
$router->put('/api/v1/admin/questions/{id}', [AdminController::class, 'updateQuestion']);
$router->delete('/api/v1/admin/questions/{id}', [AdminController::class, 'deleteQuestion']);
$router->post('/api/v1/admin/questions/upload-image', [AdminController::class, 'uploadQuestionImage']);

$router->post('/api/v1/admin/exams', [AdminController::class, 'createExam']);
$router->get('/api/v1/admin/exams', [AdminController::class, 'listAllExams']);
$router->get('/api/v1/admin/exams/{id}', [AdminController::class, 'getExamDetail']);
$router->put('/api/v1/admin/exams/{id}', [AdminController::class, 'updateExam']);
$router->delete('/api/v1/admin/exams/{id}', [AdminController::class, 'deleteExam']);
$router->put('/api/v1/admin/exams/{id}/publish', [AdminController::class, 'publishExam']);

// ==========================================
// 4. ESTADÍSTICAS MENSUALES (Gráficas)
// ==========================================
$router->get('/api/v1/admin/stats/payments-by-month', [AdminController::class, 'getPaymentsByMonth']);
$router->get('/api/v1/admin/stats/students-by-month', [AdminController::class, 'getStudentsByMonth']);

// ==========================================
// 5. MATRÍCULA DIGITAL - JEAN PIAGET SCHOOL
// ==========================================
// Ruta vista pública de matrícula
$router->get('/matricula', function () {
    $frontFile = __DIR__ . '/matricula.html';
    if (file_exists($frontFile)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($frontFile);
        exit;
    }
    Router::json(404, false, 'matricula.html no encontrado');
});

// Configuración y Grados Dinámicos
$router->get('/api/v1/enrollments/config/grades', [EnrollmentController::class, 'getGradesConfig']);

// Gestión del estudiante / acudiente
$router->get('/api/v1/enrollments/my', [EnrollmentController::class, 'getMyEnrollments']);
$router->post('/api/v1/enrollments', [EnrollmentController::class, 'createOrGetDraft']);
$router->get('/api/v1/enrollments/{id}', [EnrollmentController::class, 'getEnrollment']);
$router->put('/api/v1/enrollments/{id}/step', [EnrollmentController::class, 'saveStep']);
$router->post('/api/v1/enrollments/{id}/documents/generate', [EnrollmentController::class, 'generateDocuments']);
$router->get('/api/v1/enrollments/{id}/documents', [EnrollmentController::class, 'getDocuments']);
$router->post('/api/v1/enrollments/{id}/sign', [EnrollmentController::class, 'sign']);
$router->post('/api/v1/enrollments/{id}/submit', [EnrollmentController::class, 'submit']);
$router->get('/api/v1/enrollments/{id}/status', [EnrollmentController::class, 'getStatus']);

// Administración de matrículas
$router->get('/api/v1/admin/enrollments/statistics', [AdminEnrollmentController::class, 'getStatistics']);
$router->get('/api/v1/admin/enrollments', [AdminEnrollmentController::class, 'listEnrollments']);
$router->get('/api/v1/admin/enrollments/{id}', [AdminEnrollmentController::class, 'getEnrollmentDetail']);
$router->put('/api/v1/admin/enrollments/{id}', [AdminEnrollmentController::class, 'updateEnrollmentDetail']);
$router->delete('/api/v1/admin/enrollments/{id}', [AdminEnrollmentController::class, 'disableEnrollment']);
$router->put('/api/v1/admin/enrollments/{id}/toggle-status', [AdminEnrollmentController::class, 'toggleEnrollmentStatus']);
$router->put('/api/v1/admin/enrollments/{id}/status', [AdminEnrollmentController::class, 'updateStatus']);
$router->post('/api/v1/admin/enrollments/{id}/documents/regenerate', [AdminEnrollmentController::class, 'regenerateDocuments']);

// Dispatch request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
