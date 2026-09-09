<?php

// Script directo de migración para navegador o CLI
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Services/DatabaseSeeder.php';

try {
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
    
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10,
    ]);

    // Ejecutar migración y semillero
    App\Services\DatabaseSeeder::run($pdo, $config);

    // Listar las tablas creadas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'message' => '¡Migración completada exitosamente en MySQL de Hostinger!',
        'database' => $db['name'],
        'tables_created' => $tables,
        'initial_data' => [
            'admin_user' => 'admin@icfes.com',
            'course'     => 'Curso de Preparación Académica ICFES Saber 11° ($700.000)',
            'subjects'   => 'Matemáticas, Lectura Crítica, Ciencias Naturales, Sociales, Inglés',
            'diagnostic_exam' => 'Simulacro Diagnóstico inicial con 2 preguntas de prueba'
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
