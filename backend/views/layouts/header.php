<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEMTRIBE</title>
    <link rel="icon" type="image/png" href="assets/img/logoverde.png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logoverde.png">
    <link href="https://fonts.googleapis.com/css2?family=Piazzolla:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/auth.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color: #1a1a1a; padding: 2px 0; min-height: 1px; line-height: 0.5 !important;">
        <div class="container" style="margin-top: -5px;">
            <a class="navbar-brand d-flex align-items-center" href="/" style="margin-top: 0 !important; margin-bottom: 0 !important; padding-top: 0 !important; padding-bottom: 0 !important;">
                <img src="assets/img/logoverde.png" alt="FemTribe Logo" style="height: 50px; margin-right: 4px;">
                <img src="assets/img/nombre.png" alt="FemTribe" style="height: 30px;">
            </a>
            
            <button class="navbar-toggler" type="button" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" id="navbarToggler">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav" style="justify-content: flex-end !important;">
                <ul class="navbar-nav align-items-center" style="gap: 25px; margin-left: auto !important; display: flex !important; justify-content: flex-end !important; width: 100% !important;">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/eventos">Eventos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/nosotros">Nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/productos">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/blog">Blog</a>
                    </li>
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link text-warning fw-bold" href="/perfil">
                                <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['user_nombres'] ?? 'Mi Perfil') ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <button class="nav-link bg-transparent border-0" type="button" data-bs-toggle="modal" data-bs-target="#authModal">
                                <i class="fas fa-sign-in-alt me-1"></i>Ingresar
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="btn inscribete-btn" type="button" data-bs-toggle="modal" data-bs-target="#authModal">
                                Registrarse
                            </button>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="cart-circle" href="/carrito" aria-label="Carrito">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="count" data-cart-count>0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Modal Superior Interactivo de Registro e Inicio de Sesión -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-0 pb-0 pe-4 pt-4">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 px-md-5 pb-5 pt-0">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-dark text-warning rounded-circle mb-3 shadow-sm" style="width: 55px; height: 55px; border: 2px solid #87CC3E;">
                            <i class="fas fa-running fa-2x" style="color: #87CC3E;"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1" id="authModalTitle">Acceso FemTribe Runner</h4>
                        <p class="text-muted small mb-0">Inicia sesión o regístrate para comprar y participar en carreras</p>
                    </div>

                    <!-- Botón de Google OAuth 2.0 -->
                    <a href="/auth/google" class="btn-google mb-3">
                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        Continuar con Google
                    </a>

                    <div class="auth-divider">
                        <span>o ingresa con tu correo</span>
                    </div>

                    <div id="authModalAlert" class="alert alert-danger d-none rounded-3 small"></div>

                    <!-- Formulario Rápido de Login -->
                    <form action="/login" method="POST" id="quickAuthForm">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Correo o N° Documento</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                <input type="text" class="form-control bg-light border-start-0" name="login_input" required placeholder="ejemplo@correo.com o documento">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Contraseña</label>
                            <div class="input-group password-input-group w-100">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control bg-light border-start-0" id="quick_password" name="password" required placeholder="••••••••">
                                <button type="button" class="toggle-password" data-toggle-password="quick_password" aria-label="Mostrar u ocultar contraseña">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 py-2.5 rounded-3 fw-bold text-uppercase shadow-sm" style="background-color: #1a1a1a; border-color: #1a1a1a;">
                            <i class="fas fa-sign-in-alt me-2" style="color: #87CC3E;"></i>Iniciar Sesión
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="small text-muted mb-0">¿No tienes una cuenta aún? 
                            <a href="/registro" class="fw-bold text-decoration-none" style="color: #87CC3E;">Registrarme como Corredor</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        /* Reglas globales: altura del navbar, sticky footer y espacio de contenido */
        :root { --nav-height: 60px; }
        body { min-height: 100vh !important; display: flex !important; flex-direction: column !important; }
        footer { margin-top: auto !important; }
        .page-content { flex: 1 0 auto; padding-top: calc(var(--nav-height) + 80px); }

        /* ESTILOS BASE PARA NAVBAR */
        .navbar-nav .nav-link {
            color: #ffffff !important;
            font-weight: 400 !important;
            font-size: 16px !important;
            padding: 4px 0 !important;
            text-decoration: none !important;
            border: none !important;
            background-color: transparent !important;
            outline: none !important;
            box-shadow: none !important;
            transition: all 0.3s ease !important;
            transform: scale(1) !important;
        }
        
        .navbar-nav .nav-link:hover {
            color: #7ED321 !important;
            transform: scale(1.1) !important;
            text-decoration: none !important;
            border: none !important;
            background-color: transparent !important;
        }
        
        .inscribete-btn {
            background-color: transparent !important;
            border: 2px solid #7ED321 !important;
            color: #7ED321 !important;
            font-weight: 400 !important;
            font-size: 16px !important;
            padding: 6px 18px !important;
            border-radius: 30px !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            white-space: nowrap !important;
            text-align: center !important;
            line-height: 1.2 !important;
            transition: all 0.3s ease !important;
            transform: scale(1) !important;
            vertical-align: middle !important;
            margin-top: -2px !important;
        }
        
        .inscribete-btn:hover {
            background-color: #7ED321 !important;
            color: #000000 !important;
            border-color: #7ED321 !important;
            transform: scale(1.05) !important;
            text-decoration: none !important;
        }
        .cart-circle { display: inline-flex !important; align-items: center !important; justify-content: center !important; position: relative !important; text-decoration: none !important; cursor: pointer !important; color: #87CC3E !important; }
        .cart-circle i,
        .cart-circle svg,
        .cart-circle .svg-inline--fa { color: inherit !important; font-size: 20px !important; transition: transform 0.2s ease !important; }
        .cart-circle:hover i,
        .cart-circle:hover svg,
        .cart-circle:hover .svg-inline--fa { transform: scale(1.15) !important; }
        .cart-circle .count { position: absolute !important; top: -10px !important; right: -12px !important; background: #ffffff !important; color: #000000 !important; border-radius: 50% !important; min-width: 20px !important; height: 20px !important; padding: 0 5px !important; font-size: 12px !important; font-weight: 700 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; border: 1px solid rgba(0,0,0,0.15) !important; box-shadow: 0 1px 2px rgba(0,0,0,0.2) !important; }

        @media (max-width: 991.98px) {
            :root { --nav-height: 70px; }
            .page-content { padding-top: calc(var(--nav-height) + 40px); }
        }
    </style>

    <script>
        // Función global para desplegar el Modal de Autenticación
        window.isUserLoggedIn = <?= !empty($_SESSION['user_id']) ? 'true' : 'false' ?>;
        window.showAuthModal = function(redirectUrl = '') {
            if (redirectUrl) {
                sessionStorage.setItem('redirect_after_auth', redirectUrl);
            }
            const modalEl = document.getElementById('authModal');
            if (modalEl) {
                const bsModal = new bootstrap.Modal(modalEl);
                bsModal.show();
            } else {
                window.location.href = '/login';
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const navbarToggler = document.getElementById('navbarToggler');
            const navbarCollapse = document.getElementById('navbarNav');
            const navbar = document.querySelector('.navbar');
            
            if (navbarToggler && navbarCollapse && navbar) {
                navbarToggler.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (navbarCollapse.classList.contains('show')) {
                        navbarCollapse.classList.remove('show');
                    } else {
                        navbarCollapse.classList.add('show');
                    }
                });
            }
        });
    </script>
    <script>
        // Actualiza contador del carrito desde localStorage
        (function(){
            const STORAGE_KEY = 'ft_cart';
            const badge = document.querySelector('[data-cart-count]');
            function update(){
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);
                    const items = raw ? JSON.parse(raw) : [];
                    const count = items.reduce((a,i)=> a + Number(i.qty||0), 0);
                    if (badge) badge.textContent = count;
                } catch(e) { if (badge) badge.textContent = '0'; }
            }
            window.addEventListener('storage', (ev)=>{ if (ev.key === STORAGE_KEY) update(); });
            document.addEventListener('DOMContentLoaded', update);
            update();
        })();
    </script>
