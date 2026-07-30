<?php
$title = "Mi Perfil | FemTribe Runner";
require __DIR__ . '/../layouts/header.php';
$u = $user ?? [];
$myOrders = $orders ?? [];
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Columna Izquierda: Resumen Perfil -->
        <div class="col-lg-4">
            <div class="card shadow border-0 rounded-4 text-center p-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle mx-auto mb-3" style="width: 90px; height: 90px;">
                    <i class="fas fa-running fa-3x"></i>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?></h4>
                <p class="badge bg-danger text-uppercase px-3 py-2 rounded-pill mx-auto mb-3"><?= htmlspecialchars($u['role'] ?? 'runner') ?></p>
                <p class="text-muted small mb-1"><i class="fas fa-id-card me-1"></i><?= htmlspecialchars(($u['tipo_documento'] ?? 'CC') . ': ' . ($u['numero_documento'] ?? '')) ?></p>
                <p class="text-muted small mb-0"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($u['email'] ?? '') ?></p>

                <hr class="my-4">

                <div class="d-grid gap-2">
                    <a href="/inscribirse" class="btn btn-outline-danger btn-sm rounded-3 fw-bold">
                        <i class="fas fa-ticket-alt me-1"></i>Inscribirme a Carrera
                    </a>
                    <a href="/productos" class="btn btn-outline-dark btn-sm rounded-3 fw-bold">
                        <i class="fas fa-shopping-bag me-1"></i>Ir a la Tienda
                    </a>
                    <a href="/logout" class="btn btn-light text-muted btn-sm rounded-3 mt-2">
                        <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Edición de Datos y Historial de Compras -->
        <div class="col-lg-8">
            <div class="card shadow border-0 rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <h4 class="fw-bold text-dark mb-1">Mis Datos de Corredor y Envíos</h4>
                    <p class="text-muted small mb-4">Actualiza tu dirección y celular para agilizar tus inscripciones y pedidos</p>

                    <?php if (!empty($_SESSION['profile_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['profile_message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['profile_message']); ?>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['profile_error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['profile_error']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['profile_error']); ?>
                    <?php endif; ?>

                    <form action="/perfil" method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombres</label>
                                <input type="text" class="form-control" name="nombres" value="<?= htmlspecialchars($u['nombres'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Apellidos</label>
                                <input type="text" class="form-control" name="apellidos" value="<?= htmlspecialchars($u['apellidos'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Celular / Teléfono</label>
                                <input type="tel" class="form-control" name="telefono" value="<?= htmlspecialchars($u['telefono'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Dirección</label>
                                <input type="text" class="form-control" name="direccion" value="<?= htmlspecialchars($u['direccion'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Municipio</label>
                                <input type="text" class="form-control" name="municipio" value="<?= htmlspecialchars($u['municipio'] ?? 'Cali') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Departamento</label>
                                <input type="text" class="form-control" name="departamento" value="<?= htmlspecialchars($u['departamento'] ?? 'Valle del Cauca') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">EPS</label>
                                <input type="text" class="form-control" name="eps" value="<?= htmlspecialchars($u['eps'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Grupo Sanguíneo</label>
                                <select class="form-select" name="grupo_sanguineo">
                                    <option value="">Seleccionar...</option>
                                    <option value="O" <?= ($u['grupo_sanguineo'] ?? '') === 'O' ? 'selected' : '' ?>>O</option>
                                    <option value="A" <?= ($u['grupo_sanguineo'] ?? '') === 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= ($u['grupo_sanguineo'] ?? '') === 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="AB" <?= ($u['grupo_sanguineo'] ?? '') === 'AB' ? 'selected' : '' ?>>AB</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">RH</label>
                                <select class="form-select" name="rh">
                                    <option value="">Seleccionar...</option>
                                    <option value="+" <?= ($u['rh'] ?? '') === '+' ? 'selected' : '' ?>>Positivo (+)</option>
                                    <option value="-" <?= ($u['rh'] ?? '') === '-' ? 'selected' : '' ?>>Negativo (-)</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger px-4 py-2.5 rounded-3 fw-bold">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sección: Historial de Compras -->
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h4 class="fw-bold text-dark mb-1"><i class="fas fa-history me-2 text-danger"></i>Historial de Compras</h4>
                    <p class="text-muted small mb-4">Revisa tus pedidos realizados y el estado de tus pagos</p>

                    <?php if (!empty($myOrders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small border-bottom">
                                    <tr>
                                        <th>N° Orden</th>
                                        <th>Fecha</th>
                                        <th>Ítems</th>
                                        <th>Total</th>
                                        <th class="text-end">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myOrders as $ord): ?>
                                        <tr>
                                            <td class="fw-bold">#<?= htmlspecialchars($ord['order_number']) ?></td>
                                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></td>
                                            <td class="small">
                                                <?php foreach ($ord['items'] as $it): ?>
                                                    <div>• <?= htmlspecialchars($it['product_name']) ?> (x<?= $it['quantity'] ?>)</div>
                                                <?php endforeach; ?>
                                            </td>
                                            <td class="fw-bold text-danger">$<?= number_format($ord['total'], 0, ',', '.') ?> COP</td>
                                            <td class="text-end">
                                                <?php if ($ord['status'] === 'paid'): ?>
                                                    <span class="badge bg-success">PAGADO</span>
                                                <?php elseif ($ord['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">PENDIENTE</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">FALLIDO</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted bg-light rounded-3">
                            <i class="fas fa-shopping-bag fa-2x mb-2 d-block text-muted"></i>
                            Aún no has realizado ninguna compra en la tienda.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
