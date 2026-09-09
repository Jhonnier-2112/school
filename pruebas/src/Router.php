<?php

namespace App;

class Router {
    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $this->convertPathToRegex($path),
            'handler' => $handler,
        ];
    }

    private function convertPathToRegex(string $path): string {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(string $method, string $uri): void {
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Remove /index.php prefix if present in URL
        $path = preg_replace('#^/index\.php#', '', $path);
        if ($path === '' || $path === false) {
            $path = '/';
        }

        // Normalizar subcarpeta si corre en entorno local (ej: /pruebas)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptDir !== '/' && $scriptDir !== '\\' && $scriptDir !== '.' && $scriptDir !== '') {
            $scriptDir = str_replace('\\', '/', $scriptDir);
            if (str_starts_with($path, $scriptDir)) {
                $path = substr($path, strlen($scriptDir));
                if ($path === '' || $path === false) {
                    $path = '/';
                }
            }
        }

        // Remove trailing slash except for root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                if (is_array($route['handler'])) {
                    [$class, $action] = $route['handler'];
                    $instance = new $class();
                    $instance->$action($params);
                } else {
                    call_user_func($route['handler'], $params);
                }
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Ruta no encontrada: ' . $method . ' ' . $path,
            'error'   => null
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function json(int $status, bool $success, string $message, mixed $data = null, mixed $error = null): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
            'error'   => $error
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function getJsonInput(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
