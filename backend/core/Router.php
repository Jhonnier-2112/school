<?php
namespace App\Core;

class Router {
    private $routes = array(
        'GET' => array(),
        'POST' => array()
    );

    public function get($uri, $callback) {
        $this->routes['GET'][$this->normalize($uri)] = $callback;
    }

    public function post($uri, $callback) {
        $this->routes['POST'][$this->normalize($uri)] = $callback;
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Ajuste de base path si la app corre en subcarpeta (p.ej. /runner/public)
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if ($scriptDir && $scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
            if ($uri === '' || $uri === false) { $uri = '/'; }
        }

        // Normalizar posibles prefijos /index.php en la ruta
        if (strpos($uri, '/index.php') === 0) {
            $uri = substr($uri, strlen('/index.php'));
            if ($uri === '' || $uri === false) { $uri = '/'; }
        }

        // Manejar archivos estáticos (assets)
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|mp4|webm)$/i', $uri)) {
            $filePath = __DIR__ . '/../../public' . $uri;
            if (file_exists($filePath)) {
                $mimeTypes = array(
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'ico' => 'image/x-icon',
                    'svg' => 'image/svg+xml',
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2',
                    'ttf' => 'font/ttf',
                    'eot' => 'application/vnd.ms-fontobject',
                    'mp4' => 'video/mp4',
                    'webm' => 'video/webm'
                );
                
                $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
                $mimeType = isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
                
                header('Content-Type: ' . $mimeType);
                readfile($filePath);
                return;
            }
        }

        $uri = $this->normalize($uri);

        $callback = null;
        if (isset($this->routes[$method][$uri])) {
            $callback = $this->routes[$method][$uri];
        }

        if ($callback) {
            try {
                if (is_string($callback)) {
                    // Caso "Controller@method"
                    $parts = explode('@', $callback);
                    $controller = $parts[0];
                    $methodName = $parts[1];
                    $controller = "App\\Controllers\\$controller";

                    if (class_exists($controller) && method_exists($controller, $methodName)) {
                        $instance = new $controller();
                        call_user_func(array($instance, $methodName));
                    } else {
                        http_response_code(500);
                        echo "Error: Controlador o método no encontrado → $controller@$methodName";
                    }
                } else {
                    // Caso función anónima
                    call_user_func($callback);
                }
            } catch (\Throwable $e) {
                http_response_code(500);
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    echo "<h3>Error del servidor</h3>";
                    echo "<pre>" . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                } else {
                    echo "Ha ocurrido un error inesperado. Intenta más tarde.";
                }
                error_log('[Router] Exception: ' . $e->getMessage());
            }
        } else {
            http_response_code(404);
            echo "Página no encontrada → $uri (Método: $method)";
        }
    }

    private function normalize($uri) {
        // Siempre empieza con "/"
        $uri = '/' . ltrim($uri, '/');
        // Elimina "/" final excepto si es raíz
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }
        return $uri;
    }
}

