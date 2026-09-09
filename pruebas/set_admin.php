<?php
/**
 * Script temporal para cambiar rol a admin.
 * Se auto-elimina al ejecutarse.
 */
$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
$db    = getDBConnection($config);
$email = 'jhonnierdamian@gmail.com';

header('Content-Type: application/json');

$stmt = $db->prepare("SELECT id, full_name, email, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => "Usuario '$email' no encontrado"]);
    @unlink(__FILE__);
    exit;
}

if ($user['role'] === 'admin') {
    echo json_encode(['success' => true, 'message' => "Ya tiene rol admin", 'user' => $user]);
    @unlink(__FILE__);
    exit;
}

$now = date('Y-m-d H:i:s');
$stmtUpdate = $db->prepare("UPDATE users SET role = 'admin', updated_at = ? WHERE email = ?");
$stmtUpdate->execute([$now, $email]);

if ($stmtUpdate->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => "Rol actualizado a admin correctamente", 'user' => array_merge($user, ['role' => 'admin'])]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el rol']);
}
@unlink(__FILE__); // se auto-elimina
