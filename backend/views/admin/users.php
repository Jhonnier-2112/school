<?php
$title = "Administración de Usuarios | FemTribe Runner";
require __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="badge bg-danger text-uppercase px-3 py-2 rounded-pill">Panel de Administración</span>
            <h2 class="fw-bold text-dark mt-2 mb-0">Usuarios y Corredores Registrados</h2>
        </div>
        <div>
            <span class="badge bg-dark fs-6 px-3 py-2 rounded-3">Total: <?= $totalUsers ?> usuarios</span>
        </div>
    </div>

    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted text-uppercase small border-bottom">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Nombre Completo</th>
                            <th>Documento</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Ciudad</th>
                            <th>Rol</th>
                            <th class="pe-4 text-end">Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="ps-4 fw-bold">#<?= htmlspecialchars($user['id']) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($user['nombres'] . ' ' . $user['apellidos']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($user['direccion']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($user['tipo_documento'] . ' ' . $user['numero_documento']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['telefono']) ?></td>
                                    <td><?= htmlspecialchars($user['municipio']) ?>, <?= htmlspecialchars($user['departamento']) ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-danger">ADMIN</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">RUNNER</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end text-muted small">
                                        <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-2x mb-3 d-block"></i>
                                    No hay usuarios registrados en el sistema.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-item page-link <?= $i === $currentPage ? 'bg-danger border-danger' : 'text-danger' ?>" 
                           href="/admin/usuarios?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
