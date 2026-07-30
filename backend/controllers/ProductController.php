<?php
namespace App\Controllers;

use App\Models\Product;

use App\Core\Controller;

class ProductController extends Controller {
    public function index() {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 12;
        $order = isset($_GET['order']) ? $_GET['order'] : 'created_at DESC';

        $filters = [
            'category' => $_GET['category'] ?? null,
            'gender' => $_GET['gender'] ?? null,
            'type' => $_GET['type'] ?? null,
            'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
            'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
        ];

        // Robustez: si no viene category pero sí type[], inferir la categoría
        // textil: camisetas, esqueletos, licras, medias; accesorios: botella_plegable
        $t = $filters['type'];
        if (empty($filters['category']) && !empty($t)) {
            $types = is_array($t) ? $t : [$t];
            $types = array_map(function($x){ return strtolower((string)$x); }, $types);
            $ropa = ['camisetas','esqueletos','licras','medias'];
            $acc = ['botella_plegable'];
            $hasRopa = count(array_intersect($types, $ropa)) > 0;
            $hasAcc = count(array_intersect($types, $acc)) > 0;
            if ($hasRopa && !$hasAcc) {
                $filters['category'] = 'textil';
            } elseif ($hasAcc && !$hasRopa) {
                $filters['category'] = 'accesorios';
            } // si hay ambos, dejar category nulo para combinar resultados
        }

        $model = new Product();
        $result = $model->paginate($page, $perPage, $filters, $order);

        $this->view('productos', ['products' => $products, 'pagination' => $pagination, 'filters' => $filters]);
    }

    public function show() {
        $slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
        if ($slug === '') {
            http_response_code(404);
            echo "Producto no encontrado";
            return;
        }

        $model = new Product();
        $product = $model->findBySlug($slug);

        if (!$product) {
            http_response_code(404);
            echo "Producto no encontrado";
            return;
        }

        // Pasar a la vista de detalle
        $p = $product; // Usamos $p para mantener consistencia simple en la vista
        $this->view('producto_detalle', ['p' => $p, 'product' => $product]);
    }
}