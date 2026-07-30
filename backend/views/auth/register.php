<?php
$title = "Registro de Usuario | FemTribe Runner";
require __DIR__ . '/../layouts/header.php';
$d = $data ?? [];
?>

<div class="page-content py-5 bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <div class="auth-card shadow-lg border-0">
                    <div class="auth-header-banner">
                        <span class="badge-runner">
                            <i class="fas fa-user-plus me-1"></i> NUEVA CUENTA
                        </span>
                        <h3 class="fw-bold text-white mb-1">Únete a la Comunidad Runner</h3>
                        <p class="text-white-50 small mb-0">Regístrate para inscribirte en carreras, gestionar tus pedidos y acceder a beneficios exclusivos</p>
                    </div>

                    <div class="card-body p-4 p-md-5">

                        <!-- Opción de Registro Rápido con Google -->
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-semibold d-block text-center mb-2">OPCIÓN RÁPIDA DE REGISTRO</label>
                            <a href="<?= htmlspecialchars($googleAuthUrl ?? '/auth/google') ?>" class="btn-google">
                                <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                </svg>
                                Registrarme con Google
                            </a>
                        </div>

                        <div class="auth-divider">
                            <span>o completa el formulario tradicional</span>
                        </div>

                        <!-- Alertas de Servidor / JS -->
                        <div id="registerAlert" class="alert alert-danger rounded-3 mb-4 <?= empty($errors) ? 'd-none' : '' ?>">
                            <h6 class="fw-bold mb-2"><i class="fas fa-exclamation-circle me-2"></i>Por favor corrige los siguientes errores:</h6>
                            <ul class="mb-0 ps-3">
                                <?php if (!empty($errors)): ?>
                                    <?php foreach ($errors as $err): ?>
                                        <li><?= htmlspecialchars($err) ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <form action="/registro" method="POST" id="registerForm" novalidate>
                            
                            <!-- SECCIÓN 1: DATOS PERSONALES -->
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge rounded-circle p-2 me-2 text-dark" style="background-color: #87CC3E; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold;">1</span>
                                <h5 class="fw-bold text-dark mb-0">Datos Personales</h5>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="nombres" class="form-label fw-semibold small">Nombres *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control bg-light border-start-0" id="nombres" name="nombres" 
                                               value="<?= htmlspecialchars($d['nombres'] ?? '') ?>" placeholder="Ej. Ana María" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="apellidos" class="form-label fw-semibold small">Apellidos *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user-tag"></i></span>
                                        <input type="text" class="form-control bg-light border-start-0" id="apellidos" name="apellidos" 
                                               value="<?= htmlspecialchars($d['apellidos'] ?? '') ?>" placeholder="Ej. Pérez Gómez" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="tipo_documento" class="form-label fw-semibold small">Tipo Documento *</label>
                                    <select class="form-select bg-light" id="tipo_documento" name="tipo_documento" required>
                                        <option value="CC" <?= ($d['tipo_documento'] ?? '') === 'CC' ? 'selected' : '' ?>>Cédula de Ciudadanía (CC)</option>
                                        <option value="CE" <?= ($d['tipo_documento'] ?? '') === 'CE' ? 'selected' : '' ?>>Cédula de Extranjería (CE)</option>
                                        <option value="Pasaporte" <?= ($d['tipo_documento'] ?? '') === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                                        <option value="TI" <?= ($d['tipo_documento'] ?? '') === 'TI' ? 'selected' : '' ?>>Tarjeta de Identidad (TI)</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label for="numero_documento" class="form-label fw-semibold small">Número de Documento *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control bg-light border-start-0" id="numero_documento" name="numero_documento" 
                                               value="<?= htmlspecialchars($d['numero_documento'] ?? '') ?>" placeholder="Ej. 1098765432" required>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: CUENTA Y CONTACTO -->
                            <div class="d-flex align-items-center mb-3 pt-2">
                                <span class="badge rounded-circle p-2 me-2 text-dark" style="background-color: #87CC3E; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold;">2</span>
                                <h5 class="fw-bold text-dark mb-0">Credenciales y Contacto</h5>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label for="email" class="form-label fw-semibold small">Correo Electrónico *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control bg-light border-start-0" id="email" name="email" 
                                               value="<?= htmlspecialchars($d['email'] ?? '') ?>" placeholder="tuemail@ejemplo.com" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-semibold small">Contraseña *</label>
                                    <div class="input-group password-input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control bg-light border-start-0" id="password" name="password" 
                                               placeholder="Mínimo 6 caracteres" required>
                                        <button type="button" class="toggle-password" data-toggle-password="password" title="Mostrar/ocultar contraseña">
                                            <i class="far fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength-meter">
                                        <div id="passwordStrengthBar" class="password-strength-bar"></div>
                                    </div>
                                    <div id="passwordStrengthText"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="password_confirm" class="form-label fw-semibold small">Confirmar Contraseña *</label>
                                    <div class="input-group password-input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-check-double"></i></span>
                                        <input type="password" class="form-control bg-light border-start-0" id="password_confirm" name="password_confirm" 
                                               placeholder="Repite tu contraseña" required>
                                        <button type="button" class="toggle-password" data-toggle-password="password_confirm" title="Mostrar/ocultar contraseña">
                                            <i class="far fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="passwordMatchError" class="small text-danger mt-1 d-none">Las contraseñas no coinciden.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="telefono" class="form-label fw-semibold small">Celular / Teléfono *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-phone"></i></span>
                                        <input type="tel" class="form-control bg-light border-start-0" id="telefono" name="telefono" 
                                               value="<?= htmlspecialchars($d['telefono'] ?? '') ?>" placeholder="Ej. 3101234567" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="direccion" class="form-label fw-semibold small">Dirección de Entrega *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" class="form-control bg-light border-start-0" id="direccion" name="direccion" 
                                               value="<?= htmlspecialchars($d['direccion'] ?? '') ?>" placeholder="Calle/Cra, N° Casa, Barrio" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="municipio" class="form-label fw-semibold small">Ciudad / Municipio *</label>
                                    <input type="text" class="form-control bg-light" id="municipio" name="municipio" 
                                           value="<?= htmlspecialchars($d['municipio'] ?? 'Cali') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="departamento" class="form-label fw-semibold small">Departamento *</label>
                                    <input type="text" class="form-control bg-light" id="departamento" name="departamento" 
                                           value="<?= htmlspecialchars($d['departamento'] ?? 'Valle del Cauca') ?>" required>
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="terms" required checked>
                                <label class="form-check-label small text-muted" for="terms">
                                    Acepto los términos y condiciones de la comunidad **FemTribe Runner** y la política de tratamiento de datos personales.
                                </label>
                            </div>

                            <button type="submit" class="btn w-100 py-3 rounded-3 fw-bold text-uppercase shadow-sm text-dark" style="background-color: #87CC3E; border: none; font-size: 1rem;">
                                <i class="fas fa-user-plus me-2"></i>Completar Registro
                            </button>
                        </form>

                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="mb-0 text-muted small">¿Ya posees una cuenta? 
                                <a href="/login" class="fw-bold text-decoration-none ms-1" style="color: #87CC3E;">Inicia Sesión aquí</a>
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
