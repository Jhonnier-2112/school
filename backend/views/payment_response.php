<?php
$title = "Resultado de Transacción | FemTribe Runner";
require __DIR__ . '/layouts/header.php';

$tx = $transaction ?? [];
$ord = $order ?? [];
$status = strtoupper($tx['status'] ?? $ord['status'] ?? 'PENDING');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 text-center p-4 p-md-5">
                <?php if ($status === 'APPROVED' || $status === 'PAID'): ?>
                    <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                    <h2 class="fw-bold text-success mb-1">¡Pago Aprobado!</h2>
                    <p class="text-muted mb-4">Tu transacción ha sido procesada con éxito por Bancolombia</p>
                <?php elseif ($status === 'PENDING'): ?>
                    <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-clock fa-3x"></i>
                    </div>
                    <h2 class="fw-bold text-warning mb-1">Pago en Proceso</h2>
                    <p class="text-muted mb-4">Tu pago está siendo verificado por el sistema de Bancolombia</p>
                <?php else: ?>
                    <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-times-circle fa-3x"></i>
                    </div>
                    <h2 class="fw-bold text-danger mb-1">Transacción No Aprobada</h2>
                    <p class="text-muted mb-4">No se pudo completar el pago. Por favor intenta de nuevo o prueba con otro medio de pago</p>
                <?php endif; ?>

                <?php if (!empty($ord)): ?>
                    <div class="bg-light p-3 rounded-3 text-start mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Número de Orden:</span>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($ord['order_number']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Total:</span>
                            <span class="fw-bold text-danger">$<?= number_format($ord['total'], 0, ',', '.') ?> COP</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Cliente:</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($ord['customer_name']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Pasarela de Pago:</span>
                            <span class="badge bg-dark text-uppercase">API Bancolombia / Wompi</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-grid gap-2">
                    <a href="/perfil" class="btn btn-danger py-2.5 rounded-3 fw-bold">
                        <i class="fas fa-user me-2"></i>Ir a Mi Perfil
                    </a>
                    <a href="/productos" class="btn btn-outline-dark py-2.5 rounded-3 fw-bold">
                        <i class="fas fa-shopping-bag me-2"></i>Volver a la Tienda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Limpiar carrito si el pago fue aprobado
<?php if ($status === 'APPROVED' || $status === 'PAID'): ?>
try {
    localStorage.removeItem('ft_cart');
} catch(e) {}
<?php endif; ?>
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
