<?php
$title = "Finalizar Pago | FemTribe Runner";
require __DIR__ . '/layouts/header.php';
$u = $user ?? [];
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Columna Izquierda: Formulario Datos de Envío -->
        <div class="col-lg-7">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <span class="badge bg-danger text-uppercase px-3 py-2 rounded-pill mb-2">Pago Seguro API Bancolombia</span>
                    <h3 class="fw-bold text-dark mb-1">Datos de Facturación y Envío</h3>
                    <p class="text-muted small mb-4">Ingresa la información requerida para tu compra o inscripción</p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger rounded-3 mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="/payment/process" method="POST" id="checkoutForm">
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Nombre Completo *</label>
                                <input type="text" class="form-control bg-light" name="customer_name" 
                                       value="<?= htmlspecialchars(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Correo Electrónico *</label>
                                <input type="email" class="form-control bg-light" name="customer_email" 
                                       value="<?= htmlspecialchars($u['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Celular / Teléfono *</label>
                                <input type="tel" class="form-control bg-light" name="customer_phone" 
                                       value="<?= htmlspecialchars($u['telefono'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Número de Documento *</label>
                                <input type="text" class="form-control bg-light" name="customer_document" 
                                       value="<?= htmlspecialchars($u['numero_documento'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Dirección de Entrega *</label>
                                <input type="text" class="form-control bg-light" name="shipping_address" 
                                       value="<?= htmlspecialchars($u['direccion'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ciudad / Municipio *</label>
                                <input type="text" class="form-control bg-light" name="city" 
                                       value="<?= htmlspecialchars($u['municipio'] ?? 'Cali') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Departamento *</label>
                                <input type="text" class="form-control bg-light" name="department" 
                                       value="<?= htmlspecialchars($u['departamento'] ?? 'Valle del Cauca') ?>" required>
                            </div>
                        </div>

                        <input type="hidden" name="items" id="itemsJson" value="">

                        <button type="submit" class="btn btn-danger w-100 py-3 rounded-3 fw-bold text-uppercase shadow-sm">
                            <i class="fas fa-credit-card me-2"></i>Pagar con Bancolombia / Nequi / PSE
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Resumen de Orden y Métodos Bancolombia -->
        <div class="col-lg-5">
            <div class="card shadow border-0 rounded-4 p-4 mb-4">
                <h4 class="fw-bold mb-3 border-bottom pb-2">Resumen del Pedido</h4>

                <div id="checkoutSummaryList" class="mb-3">
                    <!-- Inyectado dinámicamente desde el carrito local -->
                    <p class="text-muted small">Cargando ítems...</p>
                </div>

                <hr>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-semibold" id="checkoutSubtotal">$0 COP</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Envío / Procesamiento</span>
                    <span class="fw-semibold" id="checkoutShipping">$0 COP</span>
                </div>
                <div class="d-flex justify-content-between fs-5 fw-bold text-danger mt-2 pt-2 border-top">
                    <span>Total a Pagar</span>
                    <span id="checkoutTotal">$0 COP</span>
                </div>
            </div>

            <!-- Métodos de Pago Bancolombia Disponibles -->
            <div class="card border-0 bg-light rounded-4 p-3 text-center">
                <p class="small text-muted fw-semibold mb-2">Medios de Pago Habilitados por Bancolombia API</p>
                <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap">
                    <span class="badge bg-warning text-dark px-2.5 py-1.5"><i class="fas fa-university me-1"></i>Botón Bancolombia</span>
                    <span class="badge bg-primary px-2.5 py-1.5"><i class="fas fa-qrcode me-1"></i>QR Bancolombia</span>
                    <span class="badge bg-danger px-2.5 py-1.5"><i class="fas fa-mobile-alt me-1"></i>Nequi</span>
                    <span class="badge bg-dark px-2.5 py-1.5"><i class="fas fa-building me-1"></i>PSE</span>
                    <span class="badge bg-secondary px-2.5 py-1.5"><i class="fas fa-credit-card me-1"></i>Tarjetas Débito/Crédito</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const STORAGE_KEY = 'ft_cart';
    const itemsJsonInput = document.getElementById('itemsJson');
    const summaryList = document.getElementById('checkoutSummaryList');
    const subtotalEl = document.getElementById('checkoutSubtotal');
    const shippingEl = document.getElementById('checkoutShipping');
    const totalEl = document.getElementById('checkoutTotal');

    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        const items = raw ? JSON.parse(raw) : [];

        if (items.length === 0) {
            summaryList.innerHTML = '<div class="alert alert-warning py-2 mb-0">No hay productos en el carrito. <a href="/productos">Ir a la tienda</a></div>';
            return;
        }

        itemsJsonInput.value = JSON.stringify(items);

        let subtotal = 0;
        let html = '<ul class="list-group list-group-flush mb-0">';
        items.forEach(item => {
            const p = Number(item.price || 0);
            const q = Number(item.quantity || item.qty || 1);
            const itemTotal = p * q;
            subtotal += itemTotal;
            html += `<li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                <div>
                    <div class="fw-semibold text-dark">${item.name || item.title}</div>
                    <small class="text-muted">Cant: ${q} x $${p.toLocaleString('es-CO')}</small>
                </div>
                <span class="fw-semibold">$${itemTotal.toLocaleString('es-CO')}</span>
            </li>`;
        });
        html += '</ul>';
        summaryList.innerHTML = html;

        const shipping = subtotal > 150000 ? 0 : 12000;
        const total = subtotal + shipping;

        subtotalEl.textContent = '$' + subtotal.toLocaleString('es-CO') + ' COP';
        shippingEl.textContent = shipping === 0 ? '¡GRATIS!' : '$' + shipping.toLocaleString('es-CO') + ' COP';
        totalEl.textContent = '$' + total.toLocaleString('es-CO') + ' COP';

    } catch (e) {
        console.error('Error al cargar carrito en checkout:', e);
    }
});
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
