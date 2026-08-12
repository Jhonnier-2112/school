<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Config\Database;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Event;
use App\Models\Registration;
use PDO;
use PDOException;

class AdminController extends Controller {

    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Dashboard principal del módulo administrador
     */
    public function dashboard() {
        $this->requireAdmin();

        try {
            // 1. Usuarios totales (Corredores creados con rol de usuario, excluyendo administradores)
            $stmt = $this->db->query("SELECT COUNT(*) AS total FROM users WHERE status = 1 AND (role_id != 'a1b2c3d4-0002-0002-0002-000000000002' AND role != 'admin' AND role != 'administrador')");
            $totalUsers = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // 2. Ventas totales (aprobadas/pagadas)
            $stmt = $this->db->query("SELECT COUNT(*) AS total, SUM(total) AS total_sales FROM orders WHERE status = 'paid'");
            $salesData = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalOrders = (int)($salesData['total'] ?? 0);
            $totalSales = (float)($salesData['total_sales'] ?? 0.00);

            // 3. Inscripciones totales
            $stmt = $this->db->query("SELECT COUNT(*) AS total FROM registrations");
            $totalRegistrations = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // 4. Visitas totales (logs únicos por ip/correo)
            $stmt = $this->db->query("SELECT COUNT(DISTINCT COALESCE(u.email, l.ip_address)) AS total FROM user_access_logs l LEFT JOIN users u ON l.user_id = u.id");
            $totalVisits = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            // 5. Últimas 5 Compras
            $stmt = $this->db->query("SELECT id, order_number, customer_name, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
            $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 6. Últimos 5 Accesos únicos por ip/correo
            $stmt = $this->db->query("SELECT l.ip_address, l.page_url, l.method, l.created_at, u.email 
                                      FROM user_access_logs l 
                                      LEFT JOIN users u ON l.user_id = u.id 
                                      INNER JOIN (
                                          SELECT MAX(logs.id) as max_id 
                                          FROM user_access_logs logs
                                          LEFT JOIN users us ON logs.user_id = us.id
                                          GROUP BY COALESCE(us.email, logs.ip_address)
                                      ) latest ON l.id = latest.max_id
                                      ORDER BY l.created_at DESC LIMIT 5");
            $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            $totalUsers = $totalOrders = $totalRegistrations = $totalVisits = 0;
            $totalSales = 0.00;
            $recentOrders = $recentLogs = [];
        }

        $this->view('admin/dashboard', [
            'activeTab' => 'dashboard',
            'totalUsers' => $totalUsers,
            'totalOrders' => $totalOrders,
            'totalSales' => $totalSales,
            'totalRegistrations' => $totalRegistrations,
            'totalVisits' => $totalVisits,
            'recentOrders' => $recentOrders,
            'recentLogs' => $recentLogs
        ]);
    }

    /**
     * Listado y filtros de productos (Catálogo Admin)
     */
    public function products() {
        $this->requireAdmin();

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;
        $search = $_GET['search'] ?? '';

        $params = [];
        $whereClause = "WHERE p.is_active = 1";
        if ($search !== '') {
            $whereClause .= " AND (p.name LIKE :search OR p.sku LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        try {
            // Count total
            $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM products p {$whereClause}");
            $stmt->execute($params);
            $totalProducts = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            $totalPages = (int)ceil($totalProducts / $perPage);

            // Fetch items
            $offset = ($page - 1) * $perPage;
            $stmt = $this->db->prepare("SELECT p.*, c.name AS category_name 
                                        FROM products p 
                                        LEFT JOIN categories c ON p.category_id = c.id 
                                        {$whereClause} 
                                        ORDER BY p.created_at DESC 
                                        LIMIT :limit OFFSET :offset");
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            $products = [];
            $totalProducts = $totalPages = 0;
        }

        $this->view('admin/products', [
            'activeTab' => 'products',
            'products' => $products,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'search' => $search
        ]);
    }

    /**
     * Formulario de creación de producto
     */
    public function createProductForm() {
        $this->requireAdmin();

        $categoryModel = new Category();
        $categories = $categoryModel->getAll();

        $product = $_SESSION['old_product'] ?? null;
        unset($_SESSION['old_product']);

        $this->view('admin/product_form', [
            'activeTab' => 'products',
            'mode' => 'create',
            'categories' => $categories,
            'product' => $product
        ]);
    }

    /**
     * Formulario de edición de producto existente
     */
    public function editProductForm() {
        $this->requireAdmin();

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id === 0) {
            $_SESSION['admin_error'] = 'ID de producto no especificado.';
            $this->redirect('/admin/productos');
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $_SESSION['admin_error'] = 'Producto no encontrado.';
                $this->redirect('/admin/productos');
            }

            $categoryModel = new Category();
            $categories = $categoryModel->getAll();

        } catch (PDOException $e) {
            $_SESSION['admin_error'] = 'Error al cargar el producto.';
            $this->redirect('/admin/productos');
        }

        $this->view('admin/product_form', [
            'activeTab' => 'products',
            'mode' => 'edit',
            'categories' => $categories,
            'product' => $product
        ]);
    }

    /**
     * Upload AJAX de archivos de medios (imágenes/videos) para un producto.
     * Devuelve JSON con las URLs de los archivos guardados.
     */
    public function uploadMedia() {
        $this->requireAdmin();

        header('Content-Type: application/json; charset=utf-8');

        $allowedImages = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $allowedVideos = ['mp4', 'webm', 'mov'];
        $maxSize       = 20 * 1024 * 1024; // 20 MB

        $basePublic = realpath(__DIR__ . '/../../frontend/public_html');
        $imgDir     = $basePublic . '/assets/img/products/uploads/';
        $vidDir     = $basePublic . '/assets/videos/uploads/';

        if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);
        if (!is_dir($vidDir)) mkdir($vidDir, 0755, true);

        if (empty($_FILES['files'])) {
            echo json_encode(['success' => false, 'error' => 'No se recibieron archivos.']);
            return;
        }

        // Normalizar estructura de $_FILES cuando se suben múltiples archivos
        $files = [];
        if (is_array($_FILES['files']['name'])) {
            foreach ($_FILES['files']['name'] as $i => $name) {
                $files[] = [
                    'name'     => $name,
                    'type'     => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error'    => $_FILES['files']['error'][$i],
                    'size'     => $_FILES['files']['size'][$i],
                ];
            }
        } else {
            $files[] = $_FILES['files'];
        }

        $uploaded = [];
        $errors   = [];

        foreach ($files as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Error al subir: {$file['name']}";
                continue;
            }
            if ($file['size'] > $maxSize) {
                $errors[] = "{$file['name']} supera el límite de 20MB.";
                continue;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowedImages)) {
                $mediaType = 'image';
                $destDir   = $imgDir;
                $urlBase   = 'assets/img/products/uploads/';
            } elseif (in_array($ext, $allowedVideos)) {
                $mediaType = 'video';
                $destDir   = $vidDir;
                $urlBase   = 'assets/videos/uploads/';
            } else {
                $errors[] = "{$file['name']}: tipo de archivo no permitido.";
                continue;
            }

            $safeName = uniqid('media_', true) . '.' . $ext;
            $destPath = $destDir . $safeName;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $uploaded[] = [
                    'url'  => $urlBase . $safeName,
                    'type' => $mediaType,
                    'name' => $file['name'],
                ];
            } else {
                $errors[] = "No se pudo guardar: {$file['name']}";
            }
        }

        echo json_encode([
            'success' => count($uploaded) > 0,
            'files'   => $uploaded,
            'errors'  => $errors,
        ]);
    }

    /**
     * Guardar nuevo producto en base de datos
     */
    public function saveProduct() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/productos');
        }

        $name        = trim($_POST['name'] ?? '');
        $sku         = trim($_POST['sku'] ?? '');
        $price       = floatval($_POST['price'] ?? 0);
        $stock       = intval($_POST['stock'] ?? 0);
        $categoryId  = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $gender      = $_POST['gender'] ?? 'unisex';
        $type        = $_POST['type'] ?? 'camisetas';
        $description = trim($_POST['description'] ?? '');
        $isNew       = isset($_POST['is_new']) ? 1 : 0;
        $isOffer     = isset($_POST['is_offer']) ? 1 : 0;

        // Procesar colores (array o string)
        $rawColors   = $_POST['colors'] ?? [];
        $colorsStr   = is_array($rawColors) ? implode(', ', array_filter(array_map('trim', $rawColors))) : trim((string)$rawColors);

        // Procesar tallas (array o string)
        $rawSizes    = $_POST['sizes'] ?? [];
        $sizesStr    = is_array($rawSizes) ? implode(', ', array_filter(array_map('trim', $rawSizes))) : trim((string)$rawSizes);

        // Medios: leer JSON del campo oculto
        $mediaJson = trim($_POST['media_json'] ?? '[]');
        $mediaList = json_decode($mediaJson, true) ?: [];

        // Guardar estado actual del formulario para mantenerlo si falla
        $oldProductData = [
            'name'        => $name,
            'sku'         => $sku,
            'price'       => $price,
            'stock'       => $stock,
            'category_id' => $categoryId,
            'gender'      => $gender,
            'type'        => $type,
            'colors'      => $colorsStr,
            'sizes'       => $sizesStr,
            'description' => $description,
            'is_new'      => $isNew,
            'is_offer'    => $isOffer,
            'media'       => $mediaList
        ];

        // Imagen principal: primera imagen del listado, o placeholder
        $firstImage = 'assets/img/products/placeholder.png';
        $firstVideo = null;
        $imageUrls  = [];
        foreach ($mediaList as $m) {
            if ($m['type'] === 'image' && $firstImage === 'assets/img/products/placeholder.png') {
                $firstImage = $m['url'];
            }
            if ($m['type'] === 'video' && $firstVideo === null) {
                $firstVideo = $m['url'];
            }
            if ($m['type'] === 'image') {
                $imageUrls[] = $m['url'];
            }
        }
        $imagesStr = implode(',', $imageUrls);

        if ($name === '' || $sku === '') {
            $_SESSION['old_product'] = $oldProductData;
            $_SESSION['admin_error'] = 'Nombre y SKU son campos obligatorios.';
            $this->redirect('/admin/productos/nuevo');
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        // Verificación previa de existencia (por SKU, slug o nombre)
        try {
            $checkStmt = $this->db->prepare("SELECT id FROM products WHERE (sku = :sku OR slug = :slug OR name = :name) AND is_active = 1 LIMIT 1");
            $checkStmt->execute([':sku' => $sku, ':slug' => $slug, ':name' => $name]);
            if ($checkStmt->fetch()) {
                $_SESSION['old_product'] = $oldProductData;
                $_SESSION['admin_error'] = 'No se puede agregar este producto';
                $this->redirect('/admin/productos/nuevo');
            }
        } catch (PDOException $e) {
            // Se continua al bloque principal
        }

        try {
            $categoryName = '';
            if ($categoryId) {
                $stmt = $this->db->prepare("SELECT name FROM categories WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $categoryId]);
                $categoryName = $stmt->fetch(PDO::FETCH_COLUMN) ?: '';
            }

            $sql = "INSERT INTO products 
                        (sku, name, slug, description, price, stock, category, category_id, gender, type, colors, sizes, image, video, images, is_new, is_offer, is_active, created_at)
                    VALUES 
                        (:sku, :name, :slug, :description, :price, :stock, :category, :category_id, :gender, :type, :colors, :sizes, :image, :video, :images, :is_new, :is_offer, 1, NOW())";

            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':sku'         => $sku,
                ':name'        => $name,
                ':slug'        => $slug,
                ':description' => $description,
                ':price'       => $price,
                ':stock'       => $stock,
                ':category'    => $categoryName,
                ':category_id' => $categoryId,
                ':gender'      => $gender,
                ':type'        => $type,
                ':colors'      => $colorsStr !== '' ? $colorsStr : null,
                ':sizes'       => $sizesStr !== '' ? $sizesStr : null,
                ':image'       => $firstImage,
                ':video'       => $firstVideo,
                ':images'      => $imagesStr !== '' ? $imagesStr : null,
                ':is_new'      => $isNew,
                ':is_offer'    => $isOffer,
            ]);

            if ($ok) {
                $newId = (int)$this->db->lastInsertId();
                // Guardar medios en product_media
                if ($newId > 0 && !empty($mediaList)) {
                    $mediaModel = new \App\Models\ProductMedia();
                    $mediaModel->saveMedia($newId, $mediaList);
                }
                // Registrar log de auditoría
                \App\Services\AuditLogService::log('ADMIN_PRODUCT_CREATE', 'Creado producto: ' . $name . ' (SKU: ' . $sku . ')');

                $_SESSION['admin_success'] = 'Producto creado exitosamente.';
                $this->redirect('/admin/productos');
            } else {
                $_SESSION['old_product'] = $oldProductData;
                $_SESSION['admin_error'] = 'No se puede agregar este producto';
                $this->redirect('/admin/productos/nuevo');
            }
        } catch (PDOException $e) {
            error_log("Error guardando producto: " . $e->getMessage());
            $_SESSION['old_product'] = $oldProductData;
            $_SESSION['admin_error'] = 'No se puede agregar este producto';
            $this->redirect('/admin/productos/nuevo');
        }
    }

    /**
     * Actualizar datos del producto en base de datos
     */
    public function updateProduct() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/productos');
        }

        $id          = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name        = trim($_POST['name'] ?? '');
        $sku         = trim($_POST['sku'] ?? '');
        $price       = floatval($_POST['price'] ?? 0);
        $stock       = intval($_POST['stock'] ?? 0);
        $categoryId  = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $gender      = $_POST['gender'] ?? 'unisex';
        $type        = $_POST['type'] ?? 'camisetas';
        $description = trim($_POST['description'] ?? '');
        $isNew       = isset($_POST['is_new']) ? 1 : 0;
        $isOffer     = isset($_POST['is_offer']) ? 1 : 0;

        // Procesar colores (array o string)
        $rawColors   = $_POST['colors'] ?? [];
        $colorsStr   = is_array($rawColors) ? implode(', ', array_filter(array_map('trim', $rawColors))) : trim((string)$rawColors);

        // Procesar tallas (array o string)
        $rawSizes    = $_POST['sizes'] ?? [];
        $sizesStr    = is_array($rawSizes) ? implode(', ', array_filter(array_map('trim', $rawSizes))) : trim((string)$rawSizes);

        // Medios: leer JSON del campo oculto
        $mediaJson = trim($_POST['media_json'] ?? '[]');
        $mediaList = json_decode($mediaJson, true) ?: [];

        // Imagen principal: primera imagen del listado
        $firstImage = 'assets/img/products/placeholder.png';
        $firstVideo = null;
        $imageUrls  = [];
        foreach ($mediaList as $m) {
            if ($m['type'] === 'image' && $firstImage === 'assets/img/products/placeholder.png') {
                $firstImage = $m['url'];
            }
            if ($m['type'] === 'video' && $firstVideo === null) {
                $firstVideo = $m['url'];
            }
            if ($m['type'] === 'image') {
                $imageUrls[] = $m['url'];
            }
        }
        $imagesStr = implode(',', $imageUrls);

        if ($id === 0 || $name === '' || $sku === '') {
            $_SESSION['admin_error'] = 'Nombre, SKU y ID son campos obligatorios.';
            $this->redirect('/admin/productos');
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        try {
            $categoryName = '';
            if ($categoryId) {
                $stmt = $this->db->prepare("SELECT name FROM categories WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $categoryId]);
                $categoryName = $stmt->fetch(PDO::FETCH_COLUMN) ?: '';
            }

            $sql = "UPDATE products SET 
                        sku = :sku, 
                        name = :name, 
                        slug = :slug, 
                        description = :description, 
                        price = :price, 
                        stock = :stock, 
                        category = :category, 
                        category_id = :category_id, 
                        gender = :gender, 
                        type = :type, 
                        colors = :colors, 
                        sizes = :sizes, 
                        image = :image, 
                        video = :video, 
                        images = :images, 
                        is_new = :is_new, 
                        is_offer = :is_offer 
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':sku'         => $sku,
                ':name'        => $name,
                ':slug'        => $slug,
                ':description' => $description,
                ':price'       => $price,
                ':stock'       => $stock,
                ':category'    => $categoryName,
                ':category_id' => $categoryId,
                ':gender'      => $gender,
                ':type'        => $type,
                ':colors'      => $colorsStr !== '' ? $colorsStr : null,
                ':sizes'       => $sizesStr !== '' ? $sizesStr : null,
                ':image'       => $firstImage,
                ':video'       => $firstVideo,
                ':images'      => $imagesStr !== '' ? $imagesStr : null,
                ':is_new'      => $isNew,
                ':is_offer'    => $isOffer,
                ':id'          => $id
            ]);

            if ($ok) {
                // Actualizar medios en product_media
                $mediaModel = new \App\Models\ProductMedia();
                $mediaModel->saveMedia($id, $mediaList);
                // Registrar log de auditoría
                \App\Services\AuditLogService::log('ADMIN_PRODUCT_UPDATE', 'Actualizado producto: ' . $name . ' (ID: ' . $id . ', SKU: ' . $sku . ')');

                $_SESSION['admin_success'] = 'Producto actualizado exitosamente.';
            } else {
                $_SESSION['admin_error'] = 'No se pudo actualizar el producto.';
            }
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = 'Error de Base de Datos: ' . $e->getMessage();
        }

        $this->redirect('/admin/productos');
    }

    /**
     * Desactivar (eliminar suavemente) producto
     */
    public function deleteProduct() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/productos');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            try {
                $stmt = $this->db->prepare("UPDATE products SET is_active = 0 WHERE id = :id");
                $stmt->execute([':id' => $id]);
                // Registrar log de auditoría
                \App\Services\AuditLogService::log('ADMIN_PRODUCT_DELETE', 'Desactivado (eliminado suave) producto ID: ' . $id);

                $_SESSION['admin_success'] = 'Producto eliminado (desactivado) exitosamente.';
            } catch (PDOException $e) {
                $_SESSION['admin_error'] = 'Error de Base de Datos.';
            }
        }

        $this->redirect('/admin/productos');
    }

    /**
     * Listado y CRUD de Categorías
     */
    public function categories() {
        $this->requireAdmin();

        $categoryModel = new Category();
        $categories = $categoryModel->getAll();

        $this->view('admin/categories', [
            'activeTab' => 'categories',
            'categories' => $categories
        ]);
    }

    /**
     * Registrar nueva categoría
     */
    public function saveCategory() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/categorias');
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $_SESSION['admin_error'] = 'El nombre de la categoría es obligatorio.';
            $this->redirect('/admin/categorias');
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        try {
            $sql = "INSERT INTO categories (name, slug, description, is_active, created_at) 
                    VALUES (:name, :slug, :description, 1, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description
            ]);
            // Registrar log de auditoría
            \App\Services\AuditLogService::log('ADMIN_CATEGORY_CREATE', 'Creada categoría: ' . $name);

            $_SESSION['admin_success'] = 'Categoría creada exitosamente.';
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = 'Error de Base de Datos: ' . $e->getMessage();
        }

        $this->redirect('/admin/categorias');
    }

    /**
     * Actualizar categoría existente
     */
    public function updateCategory() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/categorias');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id === 0 || $name === '') {
            $_SESSION['admin_error'] = 'ID y Nombre de categoría obligatorios.';
            $this->redirect('/admin/categorias');
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        try {
            $sql = "UPDATE categories SET name = :name, slug = :slug, description = :description WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description,
                ':id' => $id
            ]);
            // Registrar log de auditoría
            \App\Services\AuditLogService::log('ADMIN_CATEGORY_UPDATE', 'Actualizada categoría: ' . $name . ' (ID: ' . $id . ')');

            $_SESSION['admin_success'] = 'Categoría actualizada exitosamente.';
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = 'Error de Base de Datos: ' . $e->getMessage();
        }

        $this->redirect('/admin/categorias');
    }

    /**
     * Listado de Compras / Pedidos
     */
    public function orders() {
        $this->requireAdmin();

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $status = $_GET['status'] ?? '';

        $params = [];
        $whereClause = "";
        if ($status !== '') {
            $whereClause = "WHERE status = :status";
            $params[':status'] = $status;
        }

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM orders {$whereClause}");
            $stmt->execute($params);
            $totalOrders = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            $totalPages = (int)ceil($totalOrders / $perPage);

            $stmt = $this->db->prepare("SELECT * FROM orders {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            $orders = [];
            $totalOrders = $totalPages = 0;
        }

        $this->view('admin/orders', [
            'activeTab' => 'orders',
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalOrders' => $totalOrders,
            'status' => $status
        ]);
    }

    /**
     * Ver el detalle de una compra específica
     */
    public function orderDetail() {
        $this->requireAdmin();

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id === 0) {
            $this->redirect('/admin/compras');
        }

        try {
            // Fetch order
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $_SESSION['admin_error'] = 'Orden no encontrada.';
                $this->redirect('/admin/compras');
            }

            // Fetch items
            $stmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = :id");
            $stmt->execute([':id' => $id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Fetch payment logs
            $stmt = $this->db->prepare("SELECT * FROM payments WHERE order_id = :id ORDER BY created_at DESC");
            $stmt->execute([':id' => $id]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            $_SESSION['admin_error'] = 'Error al consultar la orden.';
            $this->redirect('/admin/compras');
        }

        $this->view('admin/order_detail', [
            'activeTab' => 'orders',
            'order' => $order,
            'items' => $items,
            'payments' => $payments
        ]);
    }

    /**
     * Bitácora de accesos y visitas
     */
    public function accessLogs() {
        $this->requireAdmin();

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        try {
            $stmt = $this->db->query("SELECT COUNT(DISTINCT COALESCE(us.email, logs.ip_address)) AS total 
                                      FROM user_access_logs logs 
                                      LEFT JOIN users us ON logs.user_id = us.id");
            $totalLogs = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            $totalPages = (int)ceil($totalLogs / $perPage);

            $stmt = $this->db->prepare("SELECT l.*, u.nombres, u.apellidos, u.email 
                                        FROM user_access_logs l 
                                        LEFT JOIN users u ON l.user_id = u.id 
                                        INNER JOIN (
                                            SELECT MAX(logs.id) as max_id 
                                            FROM user_access_logs logs
                                            LEFT JOIN users us ON logs.user_id = us.id
                                            GROUP BY COALESCE(us.email, logs.ip_address)
                                        ) latest ON l.id = latest.max_id
                                        ORDER BY l.created_at DESC 
                                        LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            $logs = [];
            $totalLogs = $totalPages = 0;
        }

        $this->view('admin/access_logs', [
            'activeTab' => 'access_logs',
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs
        ]);
    }

    /**
     * Muestra la vista de configuración del evento, fechas, cupos y costos por kilometraje
     */
    public function eventConfig() {
        $this->requireAdmin();

        // Auto-migración para agregar columnas necesarias si no existen
        try {
            $this->db->exec("ALTER TABLE race_stages ADD COLUMN presale_slots_limit INT(11) DEFAULT NULL");
        } catch (\PDOException $e) {}
        try {
            $this->db->exec("ALTER TABLE registrations ADD COLUMN etapas_preventa TEXT DEFAULT NULL");
        } catch (\PDOException $e) {}

        $eventModel = new Event();
        $event = Event::getPrimaryEvent() ?: [
            'id' => 1,
            'title' => 'Carrera Corre Con FemTribe',
            'location' => 'Cali, Valle del Cauca',
            'total_slots' => 600,
            'registered_count' => 0,
            'available_slots' => 600,
            'presale_start_date' => null,
            'presale_end_date' => null,
            'event_end_date' => null,
            'is_presale_active' => false
        ];

        $stages = Event::getStages((int)($event['id'] ?? 1));

        $registrationModel = new Registration();
        $registrations = $registrationModel->getAll() ?: [];

        $this->view('admin/event_config', [
            'activeTab' => 'event',
            'event' => $event,
            'stages' => $stages,
            'registrations' => $registrations
        ]);
    }

    /**
     * Procesa la actualización de fechas, cupos totales y costos por kilometraje
     */
    public function updateEventConfig() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/evento');
        }

        $eventId = (int)($_POST['event_id'] ?? 1);
        
        // Cupos por kilometraje (stage_slots[stage_id] => normal, stage_presale_slots[stage_id] => preventa)
        $stageSlotsData = $_POST['stage_slots'] ?? [];
        $stagePresaleSlotsData = $_POST['stage_presale_slots'] ?? [];
        $stagesData = $_POST['stages'] ?? [];

        // Calcular el límite de cupos totales como la suma de los cupos (preventa + normal) de las etapas activas
        $calculatedTotalSlots = 0;
        if (is_array($stagesData)) {
            foreach ($stagesData as $stgId => $stg) {
                if (isset($stg['is_active'])) {
                    $rawSlot = $stageSlotsData[$stgId] ?? 0;
                    $rawPresaleSlot = $stagePresaleSlotsData[$stgId] ?? 0;
                    $calculatedTotalSlots += max(1, (int)$rawSlot) + max(1, (int)$rawPresaleSlot);
                }
            }
        }

        $eventData = [
            'title'               => trim($_POST['event_title'] ?? 'Carrera Corre Con FemTribe'),
            'location'            => trim($_POST['event_location'] ?? 'Cali, Valle del Cauca'),
            'total_slots'         => max(1, $calculatedTotalSlots),
            // Fechas son OPCIONALES: null si viene vacío
            'presale_start_date'  => !empty($_POST['presale_start_date']) ? $_POST['presale_start_date'] : null,
            'presale_end_date'    => !empty($_POST['presale_end_date'])   ? $_POST['presale_end_date']   : null,
            'event_end_date'      => !empty($_POST['event_end_date'])     ? $_POST['event_end_date']     : null,
        ];

        $eventModel = new Event();
        $okEvent = $eventModel->updateEvent($eventId, $eventData);

        // Registrar log de auditoría
        \App\Services\AuditLogService::log('ADMIN_EVENT_UPDATE', 'El administrador actualizó la configuración del evento y sus kilometrajes/precios/cupos.');

        // Actualizar etapas/kilometrajes con precios y cupos
        if (is_array($stagesData)) {
            foreach ($stagesData as $stgId => $stg) {
                $rawSlot = $stageSlotsData[$stgId] ?? null;
                $slotsLimit = ($rawSlot !== null && $rawSlot !== '') ? max(1, (int)$rawSlot) : null;
                
                $rawPresaleSlot = $stagePresaleSlotsData[$stgId] ?? null;
                $presaleSlotsLimit = ($rawPresaleSlot !== null && $rawPresaleSlot !== '') ? max(1, (int)$rawPresaleSlot) : null;

                $eventModel->updateStage((int)$stgId, [
                    'name'          => trim($stg['name'] ?? ''),
                    'distance'      => trim($stg['distance'] ?? '3K'),
                    'category_type' => trim($stg['category_type'] ?? 'adulto'),
                    'presale_price' => floatval($stg['presale_price'] ?? 0),
                    'price'         => floatval($stg['price'] ?? 0),
                    'is_active'     => isset($stg['is_active']) ? 1 : 0,
                    'slots_limit'   => $slotsLimit,
                    'presale_slots_limit' => $presaleSlotsLimit,
                ]);
            }
        }

        // También actualizar cupos de stages que no vienen en stages[] (stages inactivos que no se envían)
        if (is_array($stageSlotsData)) {
            foreach ($stageSlotsData as $stgId => $rawSlot) {
                if (!isset($stagesData[$stgId])) {
                    $slotsLimit = ($rawSlot !== '') ? max(1, (int)$rawSlot) : null;
                    $rawPresaleSlot = $stagePresaleSlotsData[$stgId] ?? '';
                    $presaleSlotsLimit = ($rawPresaleSlot !== '') ? max(1, (int)$rawPresaleSlot) : null;
                    $eventModel->updateStageSlots((int)$stgId, $slotsLimit, $presaleSlotsLimit);
                }
            }
        }

        if ($okEvent) {
            $_SESSION['admin_success'] = 'Información del evento, cupos y costos actualizados exitosamente.';
        } else {
            $_SESSION['admin_error'] = 'Ocurrió un error al actualizar los datos del evento.';
        }

        $this->redirect('/admin/evento');
    }

    /**
     * Guarda / Crea un nuevo kilometraje para el evento
     */
    public function saveStage() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/evento');
        }

        $eventModel = new Event();
        $ok = $eventModel->createStage([
            'event_id' => (int)($_POST['event_id'] ?? 1),
            'name' => trim($_POST['name'] ?? ''),
            'distance' => trim($_POST['distance'] ?? '5K'),
            'category_type' => trim($_POST['category_type'] ?? 'adulto'),
            'presale_price' => floatval($_POST['presale_price'] ?? 0),
            'price' => floatval($_POST['price'] ?? 0),
            'description' => trim($_POST['description'] ?? '')
        ]);

        if ($ok) {
            $_SESSION['admin_success'] = 'Nuevo kilometraje creado exitosamente.';
        } else {
            $_SESSION['admin_error'] = 'No se pudo crear el kilometraje. Verifica el nombre.';
        }

        $this->redirect('/admin/evento');
    }

    /**
     * Elimina un kilometraje del evento
     */
    public function deleteStage() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/evento');
        }

        $stageId = (int)($_POST['stage_id'] ?? 0);
        if ($stageId > 0) {
            $eventModel = new Event();
            if ($eventModel->deleteStage($stageId)) {
                $_SESSION['admin_success'] = 'Kilometraje eliminado exitosamente.';
            } else {
                $_SESSION['admin_error'] = 'No se pudo eliminar el kilometraje.';
            }
        }

        $this->redirect('/admin/evento');
    }

    private function execSqlFile(string $path, PDO $db): array {
        $results = [];
        if (!is_file($path)) {
            return [[false, "Archivo no encontrado: $path"]];
        }
        $sql = file_get_contents($path);
        $lines = explode("\n", $sql);
        $clean = [];
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '--')) continue;
            $clean[] = $line;
        }
        $sql = implode("\n", $clean);
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

    public function runDb() {
        $base = __DIR__ . '/../sql/';
        $tasks = [
            'create_users_table.sql',
            'create_auth_tokens_and_google.sql',
            'create_roles_and_seeds.sql',
            'create_ecommerce_and_payments.sql',
            'create_access_logs_cart_and_stages.sql',
            'create_admin_and_seed_products.sql',
            'alter_products_media_and_reviews.sql',
            'create_product_media_table.sql',
            'create_events_table.sql',
            'alter_race_stages_presale_slots.sql',
            'create_audit_logs.sql',
        ];

        $output = [];
        foreach ($tasks as $file) {
            $path = $base . $file;
            $result = $this->execSqlFile($path, $this->db);
            $output[$file] = $result;
        }

        // Migración de datos heredados a la tabla product_media
        try {
            $stmt = $this->db->query("SELECT id, video, images FROM products");
            $prods = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $checkStmt = $this->db->prepare("SELECT COUNT(*) AS total FROM product_media WHERE product_id = :p_id");
            $insertStmt = $this->db->prepare("INSERT INTO product_media (product_id, type, url, sort_order) VALUES (:p_id, :type, :url, :sort)");
            
            foreach ($prods as $p) {
                $checkStmt->execute([':p_id' => $p['id']]);
                $hasMedia = (int)($checkStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0;
                
                if (!$hasMedia) {
                    $order = 0;
                    if (!empty($p['images'])) {
                        $imgs = explode(',', $p['images']);
                        foreach ($imgs as $img) {
                            $img = trim($img);
                            if ($img !== '') {
                                $insertStmt->execute([
                                    ':p_id' => $p['id'],
                                    ':type' => 'image',
                                    ':url' => $img,
                                    ':sort' => $order++
                                ]);
                            }
                        }
                    }
                    if (!empty($p['video'])) {
                        $vids = explode(',', $p['video']);
                        foreach ($vids as $vid) {
                            $vid = trim($vid);
                            if ($vid !== '') {
                                $insertStmt->execute([
                                    ':p_id' => $p['id'],
                                    ':type' => 'video',
                                    ':url' => $vid,
                                    ':sort' => $order++
                                ]);
                            }
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Error migrando datos de media heredados: " . $e->getMessage());
        }

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
        echo '<p><a href="/admin/dashboard">Ir al Dashboard Administrador</a></p>';
        echo '</body></html>';
    }

    public function exportRegistrations() {
        $this->requireAdmin();

        $registrationModel = new Registration();
        $registrations = $registrationModel->getAll() ?: [];
        $stages = Event::getStages(1);

        $stagesMap = [];
        foreach ($stages as $s) {
            $stagesMap[$s['id']] = $s;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="usuarios_inscritos.csv"');
        
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        
        fputcsv($output, [
            'ID',
            'Nombres',
            'Apellidos',
            'Email',
            'Tipo Documento',
            'Número Documento',
            'Categoría',
            'Nombre Mascota',
            'Nombre Acudiente',
            'Etapas / Kilometraje',
            'Talla Camiseta Adulto',
            'Talla Camiseta Niño',
            'Estado Pago',
            'Número Orden',
            'Total Pago',
            'Fecha Inscripción'
        ], ';');

        foreach ($registrations as $idx => $reg) {
            $stgIds = !empty($reg['etapas_seleccionadas']) ? (is_array($reg['etapas_seleccionadas']) ? $reg['etapas_seleccionadas'] : json_decode($reg['etapas_seleccionadas'], true)) : [];
            $selectedStageNames = [];
            if (is_array($stgIds)) {
                foreach ($stgIds as $sid) {
                    if (isset($stagesMap[$sid])) {
                        $selectedStageNames[] = $stagesMap[$sid]['name'] . ' (' . $stagesMap[$sid]['distance'] . ')';
                    }
                }
            }
            $etapasStr = implode(', ', $selectedStageNames);

            $categoria = $reg['categoria_participante'] ?? 'adulto';
            $categoriaText = 'Adulto';
            if ($categoria === 'mascota') {
                $categoriaText = 'Pet Run';
            } elseif ($categoria === 'nino') {
                $categoriaText = 'Infantil';
            }

            fputcsv($output, [
                $idx + 1,
                $reg['nombres'] ?? '',
                $reg['apellidos'] ?? '',
                $reg['email'] ?? '',
                $reg['tipo_documento'] ?? 'CC',
                $reg['numero_documento'] ?? '',
                $categoriaText,
                $reg['nombre_mascota'] ?? '',
                $reg['acudiente_nombre'] ?? '',
                $etapasStr,
                $reg['talla_camiseta_adulto'] ?? 'N/A',
                $reg['talla_camiseta_nino'] ?? 'N/A',
                $reg['payment_status'] ?? 'pending',
                $reg['order_number'] ?? '',
                $reg['payment_amount'] ?? 0.00,
                !empty($reg['created_at']) ? date('d/m/Y g:i A', strtotime($reg['created_at'])) : ''
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Bitácora de auditoría de transacciones y operaciones del sistema
     */
    public function auditLogs() {
        $this->requireAdmin();

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        try {
            // Auto-creación de tabla por seguridad si no existe
            $this->db->exec("CREATE TABLE IF NOT EXISTS `audit_logs` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NULL,
              `action` VARCHAR(100) NOT NULL,
              `description` TEXT NOT NULL,
              `ip_address` VARCHAR(45) NULL,
              `user_agent` VARCHAR(255) NULL,
              `metadata` TEXT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $stmt = $this->db->query("SELECT COUNT(*) AS total FROM audit_logs");
            $totalLogs = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            $totalPages = (int)ceil($totalLogs / $perPage);

            $stmt = $this->db->prepare("SELECT l.*, u.nombres, u.apellidos, u.email 
                                        FROM audit_logs l 
                                        LEFT JOIN users u ON l.user_id = u.id 
                                        ORDER BY l.created_at DESC 
                                        LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (PDOException $e) {
            $logs = [];
            $totalLogs = $totalPages = 0;
        }

        $this->view('admin/audit_logs', [
            'activeTab' => 'audit_logs',
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs
        ]);
    }
}