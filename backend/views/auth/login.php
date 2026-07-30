<?php
$title = "Iniciar Sesión | FemTribe Runner";
require __DIR__ . '/../layouts/header.php';
?>

<div class="page-content py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="auth-card shadow-lg border-0">
                    <div class="auth-header-banner">
                        <span class="badge-runner">
                            <i class="fas fa-running me-1"></i> COMUNIDAD RUNNER
                        </span>
                        <h3 class="fw-bold text-white mb-1">¡Hola de nuevo!</h3>
                        <p class="text-white-50 small mb-0">Accede a tu cuenta para inscribirte en eventos y comprar productos</p>
                    </div>

                    <div class="card-body p-4 p-md-5">

                        <!-- Botón Oficial de Google Auth -->
                        <a href="<?= htmlspecialchars($googleAuthUrl ?? '/auth/google') ?>" class="btn-google mb-3">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                            Continuar con Google
                        </a>

                        <div class="auth-divider">
                            <span>o ingresa con tu cuenta</span>
                        </div>

                        <!-- Alertas de Servidor / JavaScript -->
                        <div id="loginAlert" class="alert alert-danger rounded-3 mb-4 <?= empty($error) ? 'd-none' : '' ?>">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span><?= htmlspecialchars($error ?? '') ?></span>
                        </div>

                        <form action="/login" method="POST" id="loginForm" novalidate>
                            <div class="mb-3">
                                <label for="login_input" class="form-label fw-semibold text-dark">Correo Electrónico o N° Documento</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control bg-light border-start-0 py-2" id="login_input" name="login_input" 
                                           placeholder="ejemplo@correo.com o 1098765432" 
                                           value="<?= htmlspecialchars($loginInput ?? '') ?>" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="login_password" class="form-label fw-semibold text-dark mb-0">Contraseña</label>
                                </div>
                                <div class="input-group password-input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control bg-light border-start-0 py-2" id="login_password" name="password" 
                                           placeholder="••••••••" required>
                                    <button type="button" class="toggle-password" data-toggle-password="login_password" title="Mostrar/ocultar contraseña">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn w-100 py-3 rounded-3 fw-bold text-uppercase shadow-sm text-dark" style="background-color: #87CC3E; border: none; font-size: 1rem;">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </button>
                        </form>

                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="mb-0 text-muted small">¿Aún no tienes cuenta de corredor? 
                                <a href="/registro" class="fw-bold text-decoration-none ms-1" style="color: #87CC3E;">Regístrate aquí</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
