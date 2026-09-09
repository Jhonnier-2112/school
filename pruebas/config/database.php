<?php

if (file_exists(__DIR__ . '/../src/Services/DatabaseSeeder.php')) {
    require_once __DIR__ . '/../src/Services/DatabaseSeeder.php';
}
use App\Services\DatabaseSeeder;

$config = require __DIR__ . '/config.php';

if (!function_exists('getDBConnection')) {
    function getDBConnection(array $config): PDO {
        static $pdo = null;
        if ($pdo !== null) {
            return $pdo;
        }

        $db = $config['db'];
        $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];

        try {
            $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
            
            // Auto-migrate tables and seed initial data if needed
            DatabaseSeeder::run($pdo, $config);

            return $pdo;
        } catch (PDOException $e) {
            // Local development fallback (MAMP port 8889)
            if (($db['host'] === 'localhost' || $db['host'] === '127.0.0.1')) {
                try {
                    $localDsn = "mysql:host=127.0.0.1;port=8889;dbname={$db['name']};charset={$db['charset']}";
                    $pdo = new PDO($localDsn, 'root', 'root', $options);
                    DatabaseSeeder::run($pdo, $config);
                    return $pdo;
                } catch (PDOException $e2) {
                    // Fall through to report original error
                }
            }

            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Error al conectar con la base de datos MySQL: ' . $e->getMessage(),
                'error'   => [
                    'code' => $e->getCode(),
                    'suggestion' => 'Verifica el usuario, contraseña y permisos de base de datos en hPanel de Hostinger o MAMP local.'
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
    }
}
