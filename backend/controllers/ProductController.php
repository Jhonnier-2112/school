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
            $acc = ['botella_plegable','accesorios'];
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

        $products = $result['items'];
        $pagination = $result['pagination'];

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

        // Cargar comentarios y calificaciones
        $reviewModel = new \App\Models\Review();
        $reviews = $reviewModel->getByProductId((int)$product['id']);
        $avgRating = $reviewModel->getAverageRating((int)$product['id']);
        $totalReviews = $reviewModel->getCountByProductId((int)$product['id']);

        // Cargar imágenes y videos de la tabla product_media
        $mediaModel = new \App\Models\ProductMedia();
        $media = $mediaModel->getByProductId((int)$product['id']);

        // Pasar a la vista de detalle
        $p = $product; // Usamos $p para mantener consistencia simple en la vista
        $this->view('producto_detalle', [
            'p' => $p, 
            'product' => $product,
            'reviews' => $reviews,
            'avgRating' => $avgRating,
            'totalReviews' => $totalReviews,
            'media' => $media
        ]);
    }

    /**
     * Procesa la inserción de un comentario y calificación sobre un producto
     */
    public function addReview() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            $_SESSION['review_error'] = 'Debe iniciar sesión para calificar este producto.';
            $this->redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/productos');
        }

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $slug = isset($_POST['slug']) ? trim((string)$_POST['slug']) : '';
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = isset($_POST['comment']) ? trim((string)$_POST['comment']) : '';

        if ($productId === 0 || $slug === '') {
            $this->redirect('/productos');
        }

        if ($rating < 1 || $rating > 5 || $comment === '') {
            $_SESSION['review_error'] = 'Por favor, selecciona una calificación (estrellas) y escribe tu comentario.';
            $this->redirect("/producto?slug={$slug}#reviews-section");
        }

        $reviewModel = new \App\Models\Review();
        $ok = $reviewModel->create([
            'product_id' => $productId,
            'user_id' => $_SESSION['user_id'],
            'rating' => $rating,
            'comment' => $comment
        ]);

        if ($ok) {
            $_SESSION['review_success'] = 'Comentario agregado exitosamente. ¡Gracias por tu opinión!';
        } else {
            $_SESSION['review_error'] = 'Error al guardar el comentario. Inténtalo de nuevo.';
        }

        $this->redirect("/producto?slug={$slug}#reviews-section");
    }
}