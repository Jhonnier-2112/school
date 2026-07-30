<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserCart;

class CartController extends Controller {

    public function index() {
        $currentUser = $this->currentUser();
        $dbCart = [];
        if ($currentUser) {
            $userCartModel = new UserCart();
            $dbCart = $userCartModel->getCart($currentUser['id']);
        }
        $this->view('carrito', ['dbCart' => $dbCart]);
    }

    /**
     * Sincroniza los ítems del carrito local a la base de datos para el usuario autenticado
     */
    public function sync() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        $currentUser = $this->currentUser();
        if (!$currentUser) {
            $this->json(['success' => false, 'message' => 'Usuario no autenticado'], 401);
        }

        $jsonInput = file_get_contents('php://input');
        $items = json_decode($jsonInput, true);

        if (!is_array($items)) {
            $items = $_POST['items'] ? json_decode($_POST['items'], true) : [];
        }

        $userCartModel = new UserCart();
        $ok = $userCartModel->syncCart($currentUser['id'], $items);

        $this->json([
            'success' => $ok,
            'message' => $ok ? 'Carrito guardado en la base de datos' : 'No se pudo guardar el carrito',
            'cart' => $userCartModel->getCart($currentUser['id'])
        ]);
    }

    /**
     * Obtiene el carrito persistente guardado en la base de datos
     */
    public function getDbCart() {
        $currentUser = $this->currentUser();
        if (!$currentUser) {
            $this->json(['success' => false, 'items' => []]);
        }

        $userCartModel = new UserCart();
        $items = $userCartModel->getCart($currentUser['id']);
        $this->json(['success' => true, 'items' => $items]);
    }
}