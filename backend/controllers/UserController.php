<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class UserController extends Controller {

    /**
     * Muestra la lista de usuarios registrados para administradores
     */
    public function index() {
        $this->requireAdmin();

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $userModel = new User();
        $users = $userModel->getAll($perPage, $offset);
        $totalUsers = $userModel->countAll();
        $totalPages = (int)ceil($totalUsers / $perPage);

        $this->view('admin/users', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
}
