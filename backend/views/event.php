<?php include __DIR__ . '/layouts/header.php'; ?>

<!-- Sección de Eventos en Desarrollo -->
<div style="margin-top: 120px; margin-bottom: 30px; min-height: calc(100vh - 180px); position: relative; overflow: hidden;">
    <!-- Logo de fondo translúcido -->
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 0; opacity: 0.08;">
        <img src="assets/img/logonegro.png" alt="FEMTRIBE Logo" style="width: 1100px; height: auto; max-width: 100vw;">
    </div>
    
    <div class="container py-5" style="position: relative; z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10 text-center">
                <!-- Título principal -->
                <h1 class="display-5 fw-bold text-dark mb-4">Eventos</h1>
                
                <!-- Subtítulo -->
                <h3 class="h5 text-muted mb-4">Sección en desarrollo</h3>
                
                <!-- Logo de herramienta con fondo verde del club -->
                <div class="mb-5">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-6" 
                         style="width: 120px; height: 120px; background-color: #87CC3E;">
                        <i class="fas fa-tools" style="font-size: 3rem; color: white;"></i>
                    </div>
                </div>
                
                <!-- Texto descriptivo -->
                <p class="text-muted mb-5 px-3" style="font-size: 1.1rem;">
                    Estamos trabajando en traerte los mejores eventos y experiencias.<br>
                    Muy pronto podrás descubrir actividades increíbles diseñadas especialmente para ti.
                </p>
                
                <!-- Indicador de progreso -->
                <div class="mb-5">
                    <div class="progress mx-auto" style="height: 8px; width: 250px; border-radius: 10px; background-color: #e9ecef;">
                        <div class="progress-bar" role="progressbar" style="width: 75%; background-color: #87CC3E;" 
                             aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted mt-2 d-block">75% completado</small>
                </div>
                
                <!-- Botón corporativo -->
                <div>
                    <a href="/" class="btn btn-lg px-5 py-3" 
                       style="background-color:#87CC3E; border: none; border-radius: 8px; color: white; font-weight: 600; font-size: 1.1rem; text-decoration: none; transition: all 0.3s ease;">
                        <i class="fas fa-home me-2"></i>Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>

