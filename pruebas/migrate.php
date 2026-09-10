<?php

// Script directo de migración y sincronización de esquema para navegador o CLI
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Services/DatabaseSeeder.php';

try {
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
    
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 15,
    ]);

    // 1. Ejecutar migración base y semillero
    App\Services\DatabaseSeeder::run($pdo, $config);

    // 2. Ejecutar auto-migración de columnas requeridas
    $changes = App\Services\DatabaseSeeder::ensureColumnsExist($pdo);

    // 3. Verificación de columnas clave
    $verifiedColumns = [];
    
    $checkCols = function($table, $col) use ($pdo) {
        try {
            $cols = $pdo->query("DESCRIBE {$table}")->fetchAll(PDO::FETCH_COLUMN);
            return in_array($col, $cols);
        } catch (\Throwable $e) {
            return false;
        }
    };

    $verifiedColumns['users.is_active'] = $checkCols('users', 'is_active');
    $verifiedColumns['payments.is_active'] = $checkCols('payments', 'is_active');
    $verifiedColumns['school_enrollments.is_active'] = $checkCols('school_enrollments', 'is_active');

    // 4. Listar todas las tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'message' => '¡Migración y sincronización de columnas completada exitosamente en Hostinger!',
        'database' => $db['name'],
        'columns_migrated' => $changes,
        'verified_columns' => $verifiedColumns,
        'total_tables' => count($tables),
        'tables_created' => $tables,
        'initial_data' => [
            'admin_user' => $config['initial_admin_email'] ?? 'admin@icfes.com',
            'course'     => 'Curso de Preparación Académica ICFES Saber 11° ($700.000)',
            'matriculas_system' => 'IE Jean Piaget School 2026'
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al conectar o migrar en MySQL de Hostinger: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'config_used' => [
            'host' => $config['db']['host'],
            'port' => $config['db']['port'],
            'user' => $config['db']['user'],
            'database' => $config['db']['name']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
