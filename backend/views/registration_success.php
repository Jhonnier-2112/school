<?php include __DIR__ . '/layouts/header.php'; ?>

<div class="container" style="margin-top: 120px; padding-top: 40px;">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="alert alert-success shadow-sm">
        <h4 class="alert-heading">¡Inscripción exitosa!</h4>
        <p>Gracias por inscribirte en Corre con FemTribe Ricaurte 2025.</p>
        <p class="mb-2"><strong>Nombre:</strong> <?= htmlspecialchars(($participantData['nombres'] ?? '') . ' ' . ($participantData['apellidos'] ?? '')) ?></p>
        <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($participantData['email'] ?? '') ?></p>
        <p class="mb-2"><strong>Teléfono:</strong> <?= htmlspecialchars($participantData['telefono'] ?? '') ?></p>
        <p class="mb-2"><strong>Documento:</strong> <?= htmlspecialchars($participantData['numero_documento'] ?? '') ?></p>
        <p class="mb-0">Pronto recibirás un correo con más detalles del evento.</p>
      </div>
      <div class="text-center mt-4 mb-5">
        <div class="d-flex justify-content-center gap-3 flex-wrap">
          <a href="/" class="btn btn-outline-primary btn-lg">
          <i class="fas fa-home me-2"></i>Volver al Inicio
        </a>
        <a href="/inscripcion" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>Nuevo Registro
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>
