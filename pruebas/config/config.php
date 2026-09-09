<?php

// Load .env if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if (!array_key_exists($k, $_SERVER) && !array_key_exists($k, $_ENV)) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
                $_SERVER[$k] = $v;
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $val = getenv($key);
        if ($val === false) {
            return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return $val;
    }
}

return [
    'env' => env('ENV', 'produccion'),
    'base_url' => rtrim(env('BASE_URL', 'https://pruebas.femtribe.com.co'), '/'),
    'jwt_secret' => env('JWT_SECRET', 'super-secret-jwt-key-for-icfes-platform-2026'),
    'jwt_expiration_hours' => (int)env('JWT_EXPIRATION_HOURS', 72),
    'course_price' => (float)env('COURSE_PRICE', 700000),
    'initial_admin_email' => env('INITIAL_ADMIN_EMAIL', 'admin@icfes.com'),
    'initial_admin_password' => env('INITIAL_ADMIN_PASSWORD', 'Admin123456!'),
    'upload_dir' => __DIR__ . '/../uploads',
    
    // Database (Hostinger MySQL)
    'db' => [
        'host'    => env('DB_HOST', 'localhost'),
        'port'    => env('DB_PORT', '3306'),
        'name'    => env('DB_NAME', 'u266057107_icfes_user'),
        'user'    => env('DB_USER', 'u266057107_icfes_user'),
        'pass'    => env('DB_PASS', 'R^VR3$y6#'),
        'charset' => 'utf8mb4',
    ],

    // Email SMTP (Hostinger)
    'mail' => [
        'host'         => env('MAIL_HOST', 'smtp.hostinger.com'),
        'port'         => (int)env('MAIL_PORT', 465),
        'encryption'   => env('MAIL_ENCRYPTION', 'ssl'),
        'username'     => env('MAIL_USERNAME', 'noreply@femtribe.com.co'),
        'password'     => env('MAIL_PASSWORD', ''),
        'from_name'    => env('MAIL_FROM_NAME', 'Plataforma ICFES FemTribe'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@femtribe.com.co'),
    ],

    'activation_token_expiry_hours' => (int)env('ACTIVATION_TOKEN_EXPIRY_HOURS', 48),
];
