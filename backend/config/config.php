<?php
// Función nativa para cargar archivo .env
function loadEnv($path) {
    if (!file_exists($path)) return false;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Cargar variables de entorno desde .env
loadEnv(__DIR__ . '/../.env');

// Configuración general del proyecto
if (!defined('APP_NAME')) define('APP_NAME', getenv('APP_NAME') ?: 'FemTribe Runner');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');

// Detectar entorno automáticamente
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (bool)preg_match('/^(localhost|127\.0\.0\.1)(:\\d+)?$/', $host);
$envApp = getenv('APP_ENV') ?: ($isLocal ? 'development' : 'production');
$isProduction = ($envApp === 'production');

// Configuración de entorno
if (!defined('APP_ENV')) define('APP_ENV', $envApp);
if (!defined('APP_DEBUG')) define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: ($isLocal ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN));
if (!defined('BASE_URL')) define('BASE_URL', getenv('APP_URL') ?: ($isProduction ? 'https://' . $host : 'http://' . ($host ?: 'localhost:8000')));

// Configuración de base de datos
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: ($isProduction ? 'localhost' : '127.0.0.1'));
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: ($isProduction ? 'u266057107_femtribe_bd' : 'runner_db'));
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: ($isProduction ? 'u266057107_femtribe' : 'root'));
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($isProduction ? 'Correconfemtribe25' : ''));

// Configuración de zona horaria
date_default_timezone_set(getenv('TIMEZONE') ?: 'America/Bogota');

// WhatsApp Business (configurable)
if (!defined('WHATSAPP_BUSINESS_NUMBER')) {
    define('WHATSAPP_BUSINESS_NUMBER', getenv('WHATSAPP_BUSINESS_NUMBER') ?: '573104771933');
}
if (!defined('WHATSAPP_MESSAGE_TEMPLATE')) {
    define('WHATSAPP_MESSAGE_TEMPLATE', getenv('WHATSAPP_MESSAGE_TEMPLATE') ?: 'Hola, me interesa %s (SKU %s). ¿Disponible?');
}

// Credenciales y Configuración de API Bancolombia / Wompi
if (!defined('BANCOLOMBIA_WOMPI_ENV')) define('BANCOLOMBIA_WOMPI_ENV', getenv('BANCOLOMBIA_WOMPI_ENV') ?: 'sandbox');
if (!defined('BANCOLOMBIA_WOMPI_PUBLIC_KEY')) define('BANCOLOMBIA_WOMPI_PUBLIC_KEY', getenv('BANCOLOMBIA_WOMPI_PUBLIC_KEY') ?: 'pub_test_QW1234567890abcdef');
if (!defined('BANCOLOMBIA_WOMPI_PRIVATE_KEY')) define('BANCOLOMBIA_WOMPI_PRIVATE_KEY', getenv('BANCOLOMBIA_WOMPI_PRIVATE_KEY') ?: 'prv_test_ZX0987654321fedcba');
if (!defined('BANCOLOMBIA_WOMPI_INTEGRITY_SECRET')) define('BANCOLOMBIA_WOMPI_INTEGRITY_SECRET', getenv('BANCOLOMBIA_WOMPI_INTEGRITY_SECRET') ?: 'test_integrity_SecretKey123');
if (!defined('BANCOLOMBIA_WOMPI_EVENTS_SECRET')) define('BANCOLOMBIA_WOMPI_EVENTS_SECRET', getenv('BANCOLOMBIA_WOMPI_EVENTS_SECRET') ?: 'test_events_SecretKey123');

// Configuración de Tokens y Seguridad (Caducidad 1 Hora = 3600 Segundos)
if (!defined('JWT_SECRET')) define('JWT_SECRET', getenv('JWT_SECRET') ?: 'SuperSecretKeyFemTribe2026Token60Min');
if (!defined('TOKEN_EXPIRATION')) define('TOKEN_EXPIRATION', (int)(getenv('TOKEN_EXPIRATION') ?: 3600)); // 1 Hora
if (!defined('REFRESH_TOKEN_EXPIRATION')) define('REFRESH_TOKEN_EXPIRATION', (int)(getenv('REFRESH_TOKEN_EXPIRATION') ?: 3600)); // 1 Hora

// Configuración de Google OAuth 2.0
if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
if (!defined('GOOGLE_REDIRECT_URI')) define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: (BASE_URL . '/auth/google/callback'));

// Configuración de errores
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}