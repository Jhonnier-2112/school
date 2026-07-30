<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class UserController extends Controller {

    /**
     * Muestra la lista de usuarios registrados para administradores con filtros
     */
    public function index() {
        $this->requireAdmin();

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 15;
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';

        $filters = [
            'search' => $search,
            'role' => $role
        ];

        $userModel = new User();
        $result = $userModel->paginateUsers($page, $perPage, $filters);

        // Contar corredores con rol de usuario (excluyendo administradores)
        $db = (new \App\Config\Database())->getConnection();
        $stmt = $db->query("SELECT COUNT(*) AS total FROM users WHERE status = 1 AND (role_id != 'a1b2c3d4-0002-0002-0002-000000000002' AND role != 'admin' AND role != 'administrador')");
        $totalUsers = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);

        $this->view('admin/users', [
            'activeTab' => 'users',
            'users' => $result['items'],
            'totalUsers' => $totalUsers,
            'currentPage' => $page,
            'totalPages' => $result['pages'],
            'search' => $search,
            'role' => $role
        ]);
    }

    /**
     * Muestra el formulario para editar un usuario
     */
    public function edit() {
        $this->requireAdmin();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id === 0) {
            $this->redirect('/admin/usuarios');
        }

        $userModel = new User();
        $user = $userModel->findById($id);

        if (!$user) {
            $_SESSION['admin_error'] = 'Usuario no encontrado.';
            $this->redirect('/admin/usuarios');
        }

        $this->view('admin/user_form', [
            'activeTab' => 'users',
            'user' => $user
        ]);
    }

    /**
     * Procesa la actualización del usuario
     */
    public function update() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/usuarios');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id === 0) {
            $this->redirect('/admin/usuarios');
        }

        $roleType = $_POST['role_type'] ?? 'cliente';
        if ($roleType === 'admin') {
            $role = 'admin';
            $role_id = 'a1b2c3d4-0002-0002-0002-000000000002';
        } else {
            $role = 'runner';
            $role_id = 'a1b2c3d4-0001-0001-0001-000000000001';
        }

        $data = [
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'tipo_documento' => trim($_POST['tipo_documento'] ?? 'CC'),
            'numero_documento' => trim($_POST['numero_documento'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'municipio' => trim($_POST['municipio'] ?? 'Cali'),
            'departamento' => trim($_POST['departamento'] ?? 'Valle del Cauca'),
            'eps' => trim($_POST['eps'] ?? ''),
            'grupo_sanguineo' => trim($_POST['grupo_sanguineo'] ?? ''),
            'rh' => trim($_POST['rh'] ?? ''),
            'role' => $role,
            'role_id' => $role_id
        ];

        if (empty($data['nombres']) || empty($data['email']) || empty($data['numero_documento'])) {
            $_SESSION['admin_error'] = 'Los campos Nombre, Documento y Correo son obligatorios.';
            $this->redirect("/admin/usuarios/editar?id={$id}");
        }

        $userModel = new User();
        $ok = $userModel->adminUpdateUser($id, $data);

        if ($ok) {
            $_SESSION['admin_success'] = 'Usuario actualizado exitosamente.';
        } else {
            $_SESSION['admin_error'] = 'No se pudo actualizar el usuario. Verifica si el correo o documento ya están registrados.';
        }

        $this->redirect('/admin/usuarios');
    }
}
