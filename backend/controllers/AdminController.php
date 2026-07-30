<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;
use PDOException;

class AdminController {
    private function execSqlFile(string $path, PDO $db): array {
        $results = [];
        if (!is_file($path)) {
            return [[false, "Archivo no encontrado: $path"]];
        }
        $sql = file_get_contents($path);
        // Quitar comentarios y normalizar
        $lines = explode("\n", $sql);
        $clean = [];
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '--')) continue;
            $clean[] = $line;
        }
        $sql = implode("\n", $clean);
        // Separar por punto y coma en sentencias individuales
        $stmts = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($stmts as $stmtSql) {
            try {
                if ($stmtSql === '') continue;
                $stmt = $db->prepare($stmtSql);
                $ok = $stmt->execute();
                $results[] = [$ok, $ok ? 'OK' : 'Fallo'];
            } catch (PDOException $e) {
                $results[] = [false, $e->getMessage()];
            }
        }
        return $results;
    }

    // Ejecuta migraciones y seed de productos
    public function runDb() {
        $database = new Database();
        $db = $database->getConnection();

        $base = __DIR__ . '/../../sql/';
        $tasks = [
            'create_users_table.sql',
            'create_auth_tokens_and_google.sql',
            'create_roles_and_seeds.sql',          // tabla roles (UUID) + semillas cliente/admin + FK en users
            'create_ecommerce_and_payments.sql',
            'create_access_logs_cart_and_stages.sql',
            'add_type_column.sql',
            'migrate_official_product.sql',
            'seed_products.sql',
            'update_product_descriptions.sql',
            'add_soft_flask.sql',
        ];

        $output = [];
        foreach ($tasks as $file) {
            $path = $base . $file;
            $result = $this->execSqlFile($path, $db);
            $output[$file] = $result;
        }

        // Render respuesta simple
        header('Content-Type: text/html; charset=utf-8');
        echo '<html><head><title>Admin DB Runner</title></head><body style="font-family: system-ui, sans-serif; padding: 20px;">';
        echo '<h2>Ejecución de SQL</h2>';
        echo '<ul>';
        foreach ($output as $file => $rows) {
            echo '<li><strong>' . htmlspecialchars($file) . '</strong><ul>';
            foreach ($rows as $idx => $row) {
                [$ok, $msg] = $row;
                $color = $ok ? '#0a7' : '#c00';
                echo '<li style="color:' . $color . '">Paso ' . ($idx + 1) . ': ' . htmlspecialchars($msg) . '</li>';
            }
            echo '</ul></li>';
        }
        echo '</ul>';
        echo '<p><a href="/productos?category=textil&type=camisetas">Ver productos: Ropa → Camisetas</a></p>';
        echo '</body></html>';
    }
}