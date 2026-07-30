<?php include 'layouts/header.php'; ?>

<!-- Hero Section con Slider -->
<section class="hero-slider position-relative">
  <!-- Slider principal -->
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner">
      <div class="carousel-item active" style="background-image: url('assets/img/carrusel_camiseta.png'); background-size: cover; background-position: center; height: 100vh;">
        <div class="carousel-overlay"></div>
      </div>
      <div class="carousel-item" style="background-image: url('assets/img/carrusel1.png'); background-size: cover; background-position: center; height: 100vh;">
        <div class="carousel-overlay"></div>
      </div>
      <div class="carousel-item" style="background-image: url('assets/img/carrusel2.png'); background-size: cover; background-position: center; height: 100vh;">
        <div class="carousel-overlay"></div>
      </div>
      <div class="carousel-item" style="background-image: url('assets/img/carrusel3.png'); background-size: cover; background-position: center; height: 100vh;">
        <div class="carousel-overlay"></div>
      </div>
      <div class="carousel-item" style="background-image: url('assets/img/carrusel4.png'); background-size: cover; background-position: center; height: 100vh;">
        <div class="carousel-overlay"></div>
      </div>
      <div class="carousel-item" style="background-image: url('assets/img/carrusel5.png'); background-size: cover; background-position: center; height: 100vh;">
        <div class="carousel-overlay"></div>
      </div>
    </div>
  </div>
  
  <!-- Contenido superpuesto -->
  <div class="hero-content position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center">
    <div class="container text-center" data-aos="fade-up" data-aos-delay="200">
      <img src="assets/img/logocarrera.png" alt="Femtribe Logo" class="mb-8 img-fluid" style="height: auto; max-height: 180px; max-width: 95%; filter: brightness(0) invert(1);" onerror="this.src='assets/img/logoverde.png'; this.onerror=null;">
      <h1 class="hero-title display-4 fw-bold text-white mb-8">VIVE LA EXPERIENCIA DE LA GRAN CARRERA <br><span style="color: #7ED321;">RICAURTE 2025</span></h1>
      
      <!-- Modern Countdown -->
      <div class="modern-countdown mb-5">
        <div class="modern-countdown-item">
          <span id="countdown-days" class="modern-countdown-number">00</span>
          <span class="modern-countdown-label">Días</span>
        </div>
        <div class="modern-countdown-item">
          <span id="countdown-hours" class="modern-countdown-number">00</span>
          <span class="modern-countdown-label">Horas</span>
        </div>
        <div class="modern-countdown-item">
          <span id="countdown-minutes" class="modern-countdown-number">00</span>
          <span class="modern-countdown-label">Minutos</span>
        </div>
        <div class="modern-countdown-item">
          <span id="countdown-seconds" class="modern-countdown-number">00</span>
          <span class="modern-countdown-label">Segundos</span>
        </div>
      </div>
      
      <div class="d-flex justify-content-center gap-3">
        <a href="/inscribirse" class="btn btn-success btn-lg px-4 py-3" style="background-color: #7ED321; border-color: #7ED321; border-radius: 25px;">INSCRÍBETE AHORA</a>
                <a href="/consultar" class="btn btn-outline-light btn-lg px-4 py-3" style="border-radius: 25px;">
          <i class="fas fa-search me-2"></i>CONSULTA INSCRIPCIÓN
        </a>
      </div>
    </div>
  </div>
  
  <!-- Scroll down indicator -->
  <div class="scroll-down position-absolute bottom-0 start-50 translate-middle-x mb-4">
    <a href="#conoce-mas" class="text-white">
      <i class="fas fa-chevron-down fa-2x animate__animated animate__bounce animate__infinite"></i>
    </a>
  </div>
</section>



<!-- Video Section -->
<section id="conoce-mas" class="video-section py-5 position-relative overflow-hidden">
  <div class="video-bg-overlay"></div>
  <div class="container py-6">
    <div class="row justify-content-center mb-3" data-aos="fade-up">
      <div class="col-lg-8 text-center">
        <h5 class="fw-bold mb-3" style="color: #87CC3E;">CONOCE MÁS</h5>
        <p class="lead text-muted mb-4">Descubre todo lo que te espera en nuestra carrera más emocionante del año</p>
      </div>
    </div>
    
    <!-- Race Info Section - Card Style -->
    <div class="row justify-content-center mb-5" data-aos="fade-up" data-aos-delay="200">
      <div class="col-lg-8">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
          <div class="card-body p-4 text-center" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
            <h4 class="text-dark fw-bold mb-4">
              <i class="fas fa-map-marked-alt text-primary me-2"></i>
              Recorrido de la carrera
            </h4>
            <p class="text-muted mb-4">Conoce el recorrido 5K por los lugares más emblemáticos del Municipio de Ricaurte</p>
            <div class="map-features d-flex justify-content-center flex-wrap gap-4 align-items-center">
              <div class="feature-item">
                <i class="fas fa-route text-primary me-2"></i>
                <span class="text-muted">Recorrido urbano</span>
              </div>
              <div class="feature-item">
                <i class="fas fa-tint text-info me-2"></i>
                <span class="text-muted">Puntos de hidratación</span>
              </div>
              <div class="feature-item">
                <i class="fas fa-clock text-success me-2"></i>
                <span class="text-muted">Acompañamiento de pacers</span>
              </div>
              <div class="feature-item">
                <i class="fas fa-sign text-warning me-2"></i>
                <span class="text-muted">Señalización</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="row justify-content-center" data-aos="zoom-in" data-aos-delay="400">
      <div class="col-lg-10">
        <!-- Video Section -->
        <div class="video-container position-relative mb-5">
          <div class="video-wrapper">
            <video 
              id="femtribeVideo" 
              class="w-100 rounded-4 shadow-lg" 
              controls 
              preload="metadata"
              playsinline>
              <source src="assets/videos/video.mp4" type="video/mp4">
              <source src="assets/videos/video.webm" type="video/webm">
              Tu navegador no soporta el elemento de video.
            </video>
            
            <!-- Custom Play Button Overlay -->
            <div class="play-button-overlay position-absolute top-50 start-50 translate-middle" id="playButtonOverlay">
              <button class="custom-play-btn" onclick="playVideo()">
                <i class="fas fa-play"></i>
              </button>
            </div>
          </div>
        </div>
          
          <!-- Video Stats -->
          <div class="video-stats mt-4">
            <div class="row text-center">
              <div class="col-md-4">
                <div class="stat-item">
                  <div class="stat-icon">
                    <i class="fas fa-users text-primary"></i>
                  </div>
                  <h4 class="text-dark fw-bold">600+</h4>
                  <p class="text-muted">Participantes</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="stat-item">
                  <div class="stat-icon">
                    <i class="fas fa-route text-primary"></i>
                  </div>
                  <h4 class="text-dark fw-bold">5K</h4>
                  <p class="text-muted">Distancia</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="stat-item">
                  <div class="stat-icon">
                    <i class="fas fa-trophy text-primary"></i>
                  </div>
                  <h4 class="text-dark fw-bold">20</h4>
                  <p class="text-muted">Premios</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section py-5" style="background: linear-gradient(135deg, #87CC3E 0%, #6ca331 50%, #5a8f2a 100%); position: relative; overflow: hidden;">
  <!-- Patrón geométrico de fondo -->
  <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: 
    radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 40%),
    radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 40%),
    radial-gradient(circle at 40% 60%, rgba(255, 255, 255, 0.05) 0%, transparent 30%);
    z-index: 1;"></div>
  
  <!-- Elementos decorativos -->
  
  <!-- Líneas decorativas -->
  <div style="position: absolute; top: 30%; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.2) 50%, transparent 100%); z-index: 1;"></div>
  <div style="position: absolute; bottom: 30%; right: 0; width: 100%; height: 1px; background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.15) 50%, transparent 100%); z-index: 1;"></div>
  
  <div class="container py-4 position-relative" style="z-index: 2;">
    <div class="row justify-content-center mb-5" data-aos="fade-up">
      <div class="col-lg-8 text-center">
        <h1 class="display-5 fw-bold mb-0" style="
          color: #ffffff; 
          font-weight: 900; 
          text-shadow: 3px 3px 8px rgba(0,0,0,0.6), 0 0 30px rgba(255,255,255,0.3); 
          letter-spacing: 2px;
        ">CATEGORÍAS</h1>
      </div>
    </div>
    <div class="row justify-content-center g-4">
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
        <div class="category-card modern-card" style="background-image: url('assets/img/categoria_fem.png');">
          <div class="category-overlay"></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
        <div class="category-card modern-card" style="background-image: url('assets/img/categoria_men.png');">
          <div class="category-overlay"></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
        <div class="category-card modern-card" style="background-image: url('assets/img/categoria_kids.png');">
          <div class="category-overlay"></div>
        </div>
      </div>
    </div>
    <!-- Botón único centralizado -->
    <div class="row justify-content-center mt-5" data-aos="fade-up" data-aos-delay="400">
      <div class="col-auto">
        <a href="/inscripcion" class="btn btn-inscribete btn-lg px-5 py-3">
          <i class="fas fa-running me-2"></i>
          ¡Inscríbete Ahora!
        </a>
      </div>
    </div>
  </div>
  
  <style>
    .modern-card {
      border-radius: 20px;
      overflow: hidden;
      height: 420px;
      position: relative;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      cursor: pointer;
      border: 3px solid rgba(255, 255, 255, 0.8);
      box-shadow: 
        0 15px 50px rgba(0, 0, 0, 0.2),
        0 8px 25px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(1px);
    }
    
    .modern-card:hover {
      transform: translateY(-12px) scale(1.03);
      box-shadow: 
        0 25px 70px rgba(0, 0, 0, 0.25),
        0 15px 40px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.95);
    }
    
    .modern-card::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.1) 0%,
        rgba(255, 255, 255, 0.05) 50%,
        transparent 100%
      );
      opacity: 0;
      transition: opacity 0.4s ease;
    }
    
    .modern-card:hover::after {
      opacity: 1;
    }
    
    .category-overlay {
      display: none;
    }
    
    .btn-inscribete {
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      border: 3px solid rgba(255, 255, 255, 0.9);
      border-radius: 50px;
      color: #000000;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      box-shadow: 
        0 12px 35px rgba(0, 0, 0, 0.15),
        0 6px 20px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
      position: relative;
      overflow: hidden;
      font-size: 1.1rem;
      backdrop-filter: blur(10px);
    }
    
    .btn-inscribete:hover {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      transform: translateY(-3px) scale(1.05);
      box-shadow: 
        0 18px 50px rgba(0, 0, 0, 0.2),
        0 8px 25px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
      color: #000000;
      border-color: rgba(255, 255, 255, 1);
    }
    
    .btn-inscribete::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.6s ease;
    }
    
    .btn-inscribete:hover::before {
      left: 100%;
    }
  </style>
</section>


<style>
  .hero-slider {
    height: 100vh;
    overflow: hidden;
    margin-top: 0;
    padding-top: 76px; /* Altura aproximada del navbar Bootstrap */
  }
  
  /* OPTIMIZACIÓN CARRUSEL - Transición fade suave y uniforme */
  .carousel-fade .carousel-item {
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
  }
  
  .carousel-fade .carousel-item.active {
    opacity: 1;
  }
  
  .carousel-fade .carousel-item-next,
  .carousel-fade .carousel-item-prev {
    opacity: 0;
  }
  
  .carousel-fade .carousel-item-next.carousel-item-start,
  .carousel-fade .carousel-item-prev.carousel-item-end {
    opacity: 1;
  }
  
  .carousel-inner {
    overflow: hidden;
  }
  
  /* Video Section Styles */
  .video-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 50%, #f1f3f4 100%);
    position: relative;
    min-height: 50vh;
    display: flex;
    align-items: center;
  }
  
  .video-bg-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
      radial-gradient(circle at 20% 30%, rgba(40, 167, 69, 0.08) 0%, transparent 50%),
      radial-gradient(circle at 80% 70%, rgba(32, 201, 151, 0.06) 0%, transparent 50%),
      linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(248,249,250,0.95) 100%);
    z-index: 1;
  }
  
  /* Premios Section Styles */
  .premios-section {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    position: relative;
    min-height: auto;
    padding: 60px 0;
    border-top: 2px solid #e9ecef;
    border-bottom: 2px solid #e9ecef;
  }
  
  .premios-bg-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
      radial-gradient(circle at 20% 30%, rgba(135, 204, 62, 0.03) 0%, transparent 50%),
      radial-gradient(circle at 80% 70%, rgba(135, 204, 62, 0.02) 0%, transparent 50%);
    z-index: 1;
  }
  
  .premios-section .container {
    position: relative;
    z-index: 2;
  }
  
  .premios-header .premios-icon {
    display: inline-block;
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--primary-color) 0%, #6ca331 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px auto;
    box-shadow: 0 10px 25px rgba(135, 204, 62, 0.3);
    border: 3px solid rgba(135, 204, 62, 0.1);
  }
  
  .premios-header .premios-icon i {
    font-size: 2rem;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
  }
  
  .premios-section h2 {
    color: #1a252f !important;
    font-weight: 700;
    font-size: 2.2rem;
    margin-bottom: 15px;
    position: relative;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }
  
  .premios-section h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color) 0%, #6ca331 100%);
    border-radius: 2px;
  }
  
  .premios-section .lead {
    color: #495057 !important;
    font-size: 1.1rem;
    font-weight: 500;
    margin-bottom: 30px;
    text-shadow: none;
    line-height: 1.6;
  }
  
  .premios-image-container {
    position: relative;
    overflow: hidden;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 2px solid rgba(135, 204, 62, 0.1);
  }
  
  .premios-image-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.12);
    border-color: rgba(135, 204, 62, 0.2);
  }
  
  .premios-image {
    transition: all 0.3s ease;
  }
  
  .premios-image-container:hover .premios-image {
    transform: scale(1.05);
  }
  
  .premios-image-overlay {
    position: absolute;
    top: 20px;
    right: 20px;
  }
  
  .premios-badge {
    background: linear-gradient(135deg, var(--primary-color) 0%, #6ca331 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px rgba(135, 204, 62, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.2);
  }
  
  .premios-content {
    padding-left: 2rem;
  }
  
  .premios-highlight {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 249, 250, 0.95) 100%);
    padding: 1rem;
    border-radius: 12px;
    border: 2px solid rgba(135, 204, 62, 0.3);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    margin-bottom: 15px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
  }
  
  .premios-highlight h3 {
    color: #2c3e50 !important;
    font-weight: 700;
    font-size: 1.8rem;
    margin-bottom: 10px;
    text-shadow: none;
  }
  
  .premios-highlight h3 .text-warning {
    color: #87cc3e !important;
    font-weight: 800;
  }
  
  .premios-highlight p {
    color: #6c757d !important;
    font-size: 1rem;
    line-height: 1.5;
    font-weight: 400;
  }
  
  .premios-highlight::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 80px;
    height: 80px;
    background: radial-gradient(circle, rgba(135, 204, 62, 0.08) 0%, transparent 70%);
    border-radius: 50%;
    transform: translate(30px, -30px);
  }
  
  .premios-highlight::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #87cc3e 0%, #6ca331 100%);
    border-radius: 0 2px 2px 0;
  }
  
  .feature-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.4rem;
    background: linear-gradient(135deg, rgba(248, 249, 250, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
    border-radius: 15px;
    border: 2px solid rgba(135, 204, 62, 0.2);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    margin-bottom: 15px;
  }
  
  .feature-card:hover {
    background: linear-gradient(135deg, rgba(248, 249, 250, 1) 0%, rgba(255, 255, 255, 0.95) 100%);
    transform: translateX(10px);
    box-shadow: 0 12px 30px rgba(135, 204, 62, 0.2);
    border-color: rgba(135, 204, 62, 0.4);
  }
  
  .feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a9c2a 100%);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 6px 18px rgba(135, 204, 62, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.2);
  }
  
  .feature-icon i {
    font-size: 1.8rem;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));
  }
  
  .feature-content h6 {
    color: #1a252f;
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 5px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }
  
  .feature-content small {
    color: #495057;
    font-size: 0.95rem;
    font-weight: 500;
    text-shadow: none;
  }
  
  .feature-card-compact {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 1rem;
    background: linear-gradient(135deg, rgba(248, 249, 250, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
    border-radius: 12px;
    border: 2px solid rgba(135, 204, 62, 0.2);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    margin-bottom: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }
  
  .feature-card-compact:hover {
    background: linear-gradient(135deg, rgba(248, 249, 250, 1) 0%, rgba(255, 255, 255, 0.95) 100%);
    transform: translateX(8px);
    border-color: rgba(135, 204, 62, 0.4);
    box-shadow: 0 8px 25px rgba(135, 204, 62, 0.15);
  }
  
  .feature-icon-compact {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a9c2a 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(135, 204, 62, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.2);
  }
  
  .feature-icon-compact i {
    font-size: 1.4rem;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    filter: drop-shadow(0 1px 3px rgba(0,0,0,0.2));
  }
  
  .feature-content-compact h6 {
    margin-bottom: 2px;
    font-size: 1rem;
    color: #1a252f;
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }
  
  .feature-content-compact small {
    font-size: 0.85rem;
    color: #495057;
    font-weight: 500;
    text-shadow: none;
  }
  
  .premios-cta .btn {
    border-radius: 30px;
    padding: 15px 35px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    border: 2px solid transparent;
  }
  
  .premios-cta .btn-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, #6ca331 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(135, 204, 62, 0.3);
  }
  
  .premios-cta .btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(135, 204, 62, 0.4);
    background: linear-gradient(135deg, #6ca331 0%, var(--primary-color) 100%);
  }
  
  .premios-cta .btn-outline-primary {
    background: transparent;
    color: var(--primary-color);
    border-color: var(--primary-color);
  }
  
  .premios-cta .btn-outline-primary:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(135, 204, 62, 0.3);
  }

  /* Estilos modernos para las tarjetas de premios */
  .premio-card-modern {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.2rem;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 249, 250, 0.95) 100%);
    border-radius: 15px;
    border: 2px solid rgba(135, 204, 62, 0.15);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(15px);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
  }

  .premio-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, rgba(135, 204, 62, 0.6), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
  }

  .premio-card-modern:hover {
    transform: translateY(-6px) scale(1.01);
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.12);
    border-color: rgba(135, 204, 62, 0.3);
    background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(248, 249, 250, 0.98) 100%);
  }

  .premio-card-modern:hover::before {
    transform: translateX(100%);
  }

  .premio-icon-modern {
    width: 85px;
    height: 85px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(135, 204, 62, 0.3);
    transition: all 0.4s ease;
  }

  .premio-icon-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255, 255, 255, 0.3), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
  }

  .premio-card-modern:hover .premio-icon-modern::before {
    opacity: 1;
  }

  .premio-icon-modern i {
    font-size: 3.2rem;
    color: white;
    text-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.4));
    transition: all 0.3s ease;
    z-index: 1;
    position: relative;
  }

  .premio-card-modern:hover .premio-icon-modern i {
    transform: scale(1.2) rotate(8deg);
  }

  /* Colores del club para cada tipo de premio */
  .medal-gold {
    background: #87CC3E;
    box-shadow: 0 8px 20px rgba(135, 204, 62, 0.3);
  }

  .money-green {
    background: #87CC3E;
    box-shadow: 0 8px 20px rgba(135, 204, 62, 0.3);
  }

  .gift-orange {
    background: #87CC3E;
    box-shadow: 0 8px 20px rgba(135, 204, 62, 0.3);
  }

  .voucher-blue {
    background: #87CC3E;
    box-shadow: 0 8px 20px rgba(135, 204, 62, 0.3);
  }

  .premio-info-modern h5 {
    color: #2c3e50;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 4px;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: color 0.3s ease;
  }

  .premio-card-modern:hover .premio-info-modern h5 {
    color: #1a252f;
  }

  .premio-info-modern p {
    color: #6c757d;
    font-size: 0.95rem;
    font-weight: 500;
    line-height: 1.4;
    margin: 0;
    transition: color 0.3s ease;
  }

  .premio-card-modern:hover .premio-info-modern p {
    color: #495057;
  }
  
  @media (max-width: 991px) {
    .premios-content {
      padding-left: 0;
      margin-top: 2rem;
    }
    
    .premios-header .premios-icon {
      width: 60px;
      height: 60px;
    }
    
    .premios-header .premios-icon i {
      font-size: 2rem;
    }
    
    .premios-highlight {
      padding: 1.5rem;
    }
    
    .feature-card {
      padding: 0.8rem;
    }
  }
  
  .video-section .container {
    position: relative;
    z-index: 2;
  }
  
  /* Content Grid Layout */
  .content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: start;
  }
  
  .video-container {
    max-width: 100%;
    margin: 0 auto;
  }
  
  /* Map Container Styles - Horizontal Layout */
  .map-container-horizontal {
    max-width: 100%;
  }
  
  .map-wrapper-horizontal {
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(40, 167, 69, 0.1);
    backdrop-filter: blur(15px);
    transition: all 0.3s ease;
  }
  
  .map-wrapper-horizontal:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(0,0,0,0.15);
  }
  
  .map-info-content h4 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
  }
  
  .map-badges .badge {
    font-size: 0.9rem;
    padding: 0.6rem 1.2rem;
    border-radius: 25px;
  }
  
  .feature-item {
    display: flex;
    align-items: center;
    font-size: 0.95rem;
  }
  
  .feature-item i {
    width: 20px;
    text-align: center;
  }
  
  .map-image-wrapper {
    position: relative;
    overflow: hidden;
    border-radius: 15px;
  }
  
  .map-image {
    transition: all 0.3s ease;
    border-radius: 15px;
  }
  
  .map-image:hover {
    transform: scale(1.02);
  }
  
  .map-overlay {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 10;
  }
  
  .map-info .badge {
    font-size: 0.8rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
  }
  
  .video-wrapper {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    width: 100%;
    aspect-ratio: 16/9;
  }
  
  .video-wrapper video {
    border-radius: 16px;
    transition: all 0.3s ease;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  
  .video-wrapper:hover video {
    transform: scale(1.02);
  }
  
  /* Custom Play Button */
  .custom-play-btn {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), #20c997);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .custom-play-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 15px 40px rgba(40, 167, 69, 0.6);
  }
  
  .custom-play-btn i {
    margin-left: 3px;
  }
  
  .play-button-overlay {
    opacity: 1;
    transition: opacity 0.3s ease;
    z-index: 10;
  }
  
  .play-button-overlay.hidden {
    opacity: 0;
    pointer-events: none;
  }
  
  /* Video Stats */
  .video-stats {
    background: rgba(255,255,255,0.9);
    border-radius: 20px;
    padding: 2.5rem;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(40, 167, 69, 0.1);
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
  }
  
  .stat-item {
    padding: 1rem;
  }
  
  .stat-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
  }
  
  .stat-item h4 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, var(--primary-color), #20c997);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  
  /* Responsive Video Styles */
  @media (max-width: 768px) {
    .video-section {
      min-height: auto;
      padding: 3rem 0;
    }
    
    .content-grid {
      grid-template-columns: 1fr;
      gap: 2rem;
    }
    
    .map-wrapper-horizontal {
      padding: 1.5rem;
    }
    
    .map-wrapper-horizontal .row {
      flex-direction: column-reverse;
    }
    
    .map-info-content {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .map-info-content h4 {
      font-size: 1.3rem;
    }
    
    .feature-item {
      justify-content: center;
    }
    
    .custom-play-btn {
      width: 60px;
      height: 60px;
      font-size: 18px;
    }
    
    .stat-item h4 {
      font-size: 2rem;
    }
    
    .floating-element {
      display: none;
    }
  }
  
  @media (max-width: 992px) {
    .content-grid {
      gap: 2rem;
    }
    
    .map-wrapper-horizontal {
      padding: 1.8rem;
    }
    
    .map-info-content h4 {
      font-size: 1.4rem;
    }
  }
  
  .carousel-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5));
  }
  
  .hero-content {
    z-index: 10;
  }
  
  .hero-title {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    font-size: 2.5rem !important;
  }
  
  .hero-subtitle {
    text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
  }
  
  /* Estilos mejorados para el reloj de cuenta regresiva con diseño deportivo */
  .modern-countdown {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
  }
  
  .modern-countdown-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    border-radius: 8px;
    padding: 15px 20px;
    min-width: 110px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    border: 3px solid var(--primary-color);
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  
  .modern-countdown-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: var(--primary-color);
  }
  
  .modern-countdown-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.5), 0 0 15px var(--primary-color);
  }
  
  .modern-countdown-number {
    font-size: 2.8rem;
    font-weight: 800;
    color: #ffffff;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    line-height: 1;
    font-family: 'Montserrat', sans-serif;
  }
  
  .modern-countdown-label {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary-color);
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-top: 5px;
  }
  
  /* Responsive adjustments */
  @media (max-width: 768px) {
    .modern-countdown {
      gap: 10px;
    }
    
    .modern-countdown-item {
      min-width: 90px;
      padding: 12px 15px;
    }
    
    .modern-countdown-number {
      font-size: 2.5rem;
    }
    
    .modern-countdown-label {
      font-size: 0.9rem;
    }
  }
  
  @media (max-width: 576px) {
    .modern-countdown-item {
      min-width: 80px;
      padding: 10px;
    }
    
    .modern-countdown-number {
      font-size: 2rem;
    }
    
    .modern-countdown-label {
      font-size: 0.8rem;
    }
  }

  /* iPhone SE y pantallas muy pequeñas (375px y menores) */
  @media (max-width: 375px) {
    .carousel-item {
      height: 100vh !important;
      min-height: 650px !important;
    }
    
    .hero-content {
      padding: 20px 0 !important;
      justify-content: center !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      text-align: center !important;
    }
    
    .hero-content .container {
      padding-left: 15px !important;
      padding-right: 15px !important;
      max-width: 100% !important;
    }
    
    .hero-content img {
      max-height: 120px !important;
      margin-bottom: 1.5rem !important;
      width: auto !important;
    }
    
    .hero-title {
      font-size: 1.6rem !important;
      line-height: 1.3 !important;
      margin-bottom: 1.5rem !important;
      padding: 0 10px !important;
      text-align: center !important;
    }
    
    .modern-countdown {
      gap: 8px !important;
      margin-bottom: 2rem !important;
      justify-content: center !important;
      flex-wrap: wrap !important;
    }
    
    .modern-countdown-item {
      min-width: 70px !important;
      padding: 8px 10px !important;
    }
    
    .modern-countdown-number {
      font-size: 1.6rem !important;
    }
    
    .modern-countdown-label {
      font-size: 0.7rem !important;
    }
    
    .d-flex.justify-content-center.gap-3 {
      flex-direction: column !important;
      gap: 12px !important;
      align-items: center !important;
      padding: 0 15px !important;
    }
    
    .btn-lg {
      padding: 12px 20px !important;
      font-size: 0.9rem !important;
      width: 100% !important;
      max-width: 300px !important;
      border-radius: 25px !important;
    }
    
    /* Ocultar scroll indicator en pantallas muy pequeñas para evitar solapamiento */
    .scroll-down {
      display: none !important;
    }
  }
  
  .scroll-down {
    z-index: 20;
    animation: bounce 2s infinite;
  }
  
  @keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
      transform: translateY(0);
    }
    40% {
      transform: translateY(-20px);
    }
    60% {
      transform: translateY(-10px);
    }
  }

  /* Benefits Section Styles - Simplified */
  .benefits-section {
    position: relative;
    padding: 80px 0;
  }

  .benefits-header {
    margin-bottom: 60px;
  }

  .benefits-title {
    font-family: 'Poppins', sans-serif;
    margin-bottom: 0;
  }

  .benefits-title-main {
    display: block;
    font-size: 3rem;
    font-weight: 700;
    color: #ffffff;
    text-transform: none;
    letter-spacing: 2px;
    margin-bottom: 10px;
  }

  .benefits-title-subtitle {
    display: block;
    font-size: 1.2rem;
    font-weight: 300;
    color: #b8c6db;
    text-transform: none;
    letter-spacing: 1px;
  }

  /* Estilos para el título de beneficios */
  .benefits-header {
    margin-bottom: 0rem;
    margin-top: 5rem;
  }

  .benefits-title {
    margin-bottom: 0.5rem;
  }

  .benefits-title-main {
    display: block;
    font-size: 3rem;
    font-weight: 700;
    color: #ffffff;
    text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
    margin-bottom: 0.5rem;
    letter-spacing: 1px;
  }

  .benefits-title-subtitle {
    display: block;
    font-size: 1.2rem;
    font-weight: 300;
    color: #87CC3E;
    text-shadow: 1px 1px 4px rgba(0,0,0,0.3);
    letter-spacing: 0.5px;
  }

  .benefits-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 2rem 0;
  }

  .divider-line {
    height: 2px;
    width: 100px;
    background: linear-gradient(90deg, transparent, #87CC3E, transparent);
    margin: 0 1rem;
  }

  .divider-icon {
    background: #87CC3E;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(135, 204, 62, 0.4);
  }

  .divider-icon i {
    color: #ffffff;
    font-size: 1.5rem;
  }

  .benefit-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    border: none;
    background: transparent;
    justify-content: center;
  }

  .benefit-item:hover {
    transform: translateX(10px) translateY(-5px);
  }

  .benefit-icon {
    width: 80px;
    height: 80px;
    margin-right: 25px;
    flex-shrink: 0;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .benefit-icon i {
    font-size: 5rem !important;
    color: #ffd700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
  }

  .benefit-icon .benefit-image {
    width: 80px;
    height: 80px;
    object-fit: contain;
    filter: brightness(0) saturate(100%) invert(64%) sepia(85%) saturate(1392%) hue-rotate(74deg) brightness(95%) contrast(89%) drop-shadow(0 8px 16px rgba(135, 204, 62, 0.3));
    -webkit-filter: brightness(0) saturate(100%) invert(64%) sepia(85%) saturate(1392%) hue-rotate(74deg) brightness(95%) contrast(89%) drop-shadow(0 8px 16px rgba(135, 204, 62, 0.3));
    transition: all 0.3s ease;
  }

  .benefit-item:hover .benefit-icon i {
    transform: scale(1.1);
    color: #87CC3E;
    text-shadow: 0 8px 16px rgba(135, 204, 62, 0.4);
  }

  .benefit-item:hover .benefit-icon .benefit-image {
    transform: scale(1.1);
    filter: brightness(0) saturate(100%) invert(64%) sepia(85%) saturate(1392%) hue-rotate(74deg) brightness(95%) contrast(89%) drop-shadow(0 12px 20px rgba(135, 204, 62, 0.5));
    -webkit-filter: brightness(0) saturate(100%) invert(64%) sepia(85%) saturate(1392%) hue-rotate(74deg) brightness(95%) contrast(89%) drop-shadow(0 12px 20px rgba(135, 204, 62, 0.5));
  }

  .benefit-text {
    flex: 1;
  }

  .benefit-item h4 {
          color: white;
          font-weight: 600;
          font-size: 1.4rem;
          text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
          margin: 0 0 0.5rem 0;
        }
        
        .benefit-item p {
          color: rgba(255,255,255,0.9);
          font-size: 0.9rem;
          text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
          margin: 0;
        }

  .benefit-item p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    line-height: 1.4;
    margin: 0;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
  }

  /* Responsive adjustments for parallax section */
  /* ===== RESPONSIVE DESIGN - BENEFITS SECTION ===== */
  
  /* Pantallas grandes (1200px y mayores) - Diseño base ya definido arriba */
  
  /* Tablets grandes (1199px - 993px) */
  @media (max-width: 1199px) and (min-width: 993px) {
    .benefits-title-main {
      font-size: 2.5rem;
    }
    
    .benefit-item {
      padding: 12px 15px;
    }
    
    .benefit-icon {
      width: 70px;
      height: 70px;
      margin-right: 20px;
    }
    
    .benefit-icon .benefit-image {
      width: 70px;
      height: 70px;
    }
    
    .benefit-item h4 {
      font-size: 1.3rem;
    }
  }

  /* Tablets (992px y menores) */
  @media (max-width: 992px) {
    .benefits-section {
      padding: 4rem 0;
    }
    
    .benefits-header {
      margin-bottom: 2rem;
    }
    
    .benefits-title-main {
      font-size: 2.2rem;
    }
    
    .benefits-title-subtitle {
      font-size: 1.1rem;
    }
    
    .benefit-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 20px 15px;
      margin-bottom: 2rem;
    }
    
    .benefit-icon {
      width: 75px;
      height: 75px;
      margin-right: 0;
      margin-bottom: 15px;
    }
    
    .benefit-icon .benefit-image {
      width: 75px;
      height: 75px;
    }
    
    .benefit-text {
      text-align: center;
      width: 100%;
    }
    
    .benefit-item h4 {
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
    }
    
    .benefit-item p {
      font-size: 0.95rem;
    }
  }

  /* Tablets pequeñas (768px y menores) - FORZAR LAYOUT VERTICAL */
  @media (max-width: 768px) {
    .benefits-section {
      padding: 3rem 0 !important;
    }
    
    .benefits-header {
      margin-bottom: 1.5rem !important;
    }
    
    .benefits-title-main {
      font-size: 2rem !important;
    }
    
    .benefits-title-subtitle {
      font-size: 1rem !important;
    }
    
    /* FORZAR COLUMNAS A 100% EN MÓVILES */
    .col-lg-4.col-md-6.col-12 {
      flex: 0 0 100% !important;
      max-width: 100% !important;
      width: 100% !important;
      padding-left: 12px !important;
      padding-right: 12px !important;
      margin-bottom: 1.5rem !important;
    }
    
    .benefit-item {
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      justify-content: center !important;
      text-align: center !important;
      padding: 18px 12px !important;
      margin-bottom: 0 !important;
      width: 100% !important;
    }
    
    .benefit-icon {
      width: 70px !important;
      height: 70px !important;
      margin-right: 0 !important;
      margin-bottom: 12px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
    }
    
    .benefit-icon .benefit-image {
      width: 70px !important;
      height: 70px !important;
      object-fit: contain !important;
    }
    
    .benefit-text {
      text-align: center !important;
      width: 100% !important;
    }
    
    .benefit-item h4 {
      font-size: 1.1rem !important;
      line-height: 1.3 !important;
      margin-bottom: 0.3rem !important;
      text-align: center !important;
    }
    
    .benefit-item p {
      font-size: 0.9rem !important;
      line-height: 1.4 !important;
      text-align: center !important;
    }
  }

  /* Móviles (576px y menores) - MÁXIMA FUERZA VERTICAL */
  @media (max-width: 576px) {
    .benefits-section {
      padding: 2.5rem 0 !important;
    }
    
    .benefits-header {
      margin-bottom: 1.2rem !important;
    }
    
    .benefits-title-main {
      font-size: 1.8rem !important;
      line-height: 1.2 !important;
    }
    
    .benefits-title-subtitle {
      font-size: 0.95rem !important;
    }
    
    /* FORZAR COLUMNAS A 100% EN MÓVILES PEQUEÑOS */
    .col-lg-4.col-md-6.col-12 {
      flex: 0 0 100% !important;
      max-width: 100% !important;
      width: 100% !important;
      padding-left: 8px !important;
      padding-right: 8px !important;
      margin-bottom: 1.2rem !important;
    }
    
    .benefit-item {
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      justify-content: center !important;
      text-align: center !important;
      padding: 15px 8px !important;
      margin-bottom: 0 !important;
      width: 100% !important;
    }
    
    .benefit-icon {
      width: 60px !important;
      height: 60px !important;
      margin-right: 0 !important;
      margin-bottom: 10px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
    }
    
    .benefit-icon .benefit-image {
      width: 60px !important;
      height: 60px !important;
      object-fit: contain !important;
    }
    
    .benefit-text {
      text-align: center !important;
      width: 100% !important;
    }
    
    .benefit-item h4 {
      font-size: 1rem !important;
      line-height: 1.2 !important;
      margin-bottom: 0.2rem !important;
      text-align: center !important;
    }
    
    .benefit-item p {
      font-size: 0.85rem !important;
      line-height: 1.3 !important;
      text-align: center !important;
    }
  }

  /* Estilos adicionales para asegurar responsive */
  @media (max-width: 480px) {
    .col-lg-4.col-md-6.col-12 {
      flex: 0 0 100% !important;
      max-width: 100% !important;
    }
    
    .benefit-item {
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      text-align: center !important;
    }
  }
</style>

<!-- Benefits Section - With background image -->
<section class="benefits-section py-3" id="benefits-section" style="background-image: url('assets/img/fondo_1.png'); background-size: cover; background-position: center; background-repeat: no-repeat; position: relative;">
  <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1;"></div>
  <div class="container" style="position: relative; z-index: 2; color: white;">
    <div class="row justify-content-center">
      <div class="col-lg-10 text-center">
        <!-- Título de la sección de beneficios -->
        <div class="benefits-header mb-5" data-aos="fade-up">
          <h2 class="benefits-title">
            <span class="benefits-title-main">Beneficios exclusivos</span>
            <span class="benefits-title-subtitle">Para todos los participantes</span>
          </h2>
        </div>
        
        <div class="benefits-content" data-aos="fade-up">
            <div class="row justify-content-center">
              <!-- PRIMERA COLUMNA -->
              <!-- Asistencia médica -->
              <div class="col-lg-4 col-md-6 col-12 mb-4 px-6">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <img src="assets/img/camion-medico.png" alt="Asistencia médica" class="benefit-image">
                  </div>
                  <div class="benefit-text">
                    <h4>Asistencia<br>médica</h4>
                  </div>
                </div>
              </div>
              
              <!-- Fisioterapia -->
              <div class="col-lg-4 col-md-6 col-12 mb-4 px-6">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <img src="assets/img/masaje-facial.png" alt="Fisioterapia" class="benefit-image">
                  </div>
                  <div class="benefit-text">
                    <h4>Servicio de<br>Fisioterapia</h4>
                  </div>
                </div>
              </div>
              
              <!-- Medalla oficial -->
              <div class="col-lg-4 col-md-6 col-12 mb-4 px-6">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <img src="assets/img/medalla.png" alt="Medalla oficial" class="benefit-image">
                  </div>
                  <div class="benefit-text">
                    <h4>Medalla<br>oficial</h4>
                  </div>
                </div>
              </div>
              
              <!-- SEGUNDA FILA -->
              <!-- Zona de hidratación -->
              <div class="col-lg-4 col-md-6 col-12 mb-4 px-6">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <img src="assets/img/bebida-deportiva.png" alt="Zona de hidratación" class="benefit-image">
                  </div>
                  <div class="benefit-text">
                    <h4>Zona de<br>hidratación</h4>
                  </div>
                </div>
              </div>
              
              <!-- Fotografía profesional -->
              <div class="col-lg-4 col-md-6 col-12 mb-4 px-6">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <img src="assets/img/camara.png" alt="Fotografía profesional" class="benefit-image">
                  </div>
                  <div class="benefit-text">
                    <h4>Fotografía<br>profesional</h4>
                  </div>
                </div>
              </div>
              
              <!-- Número de dorsal -->
              <div class="col-lg-4 col-md-6 col-12 mb-4 px-6">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <img src="assets/img/dorsal.png" alt="Número de dorsal" class="benefit-image">
                  </div>
                  <div class="benefit-text">
                    <h4>Número de<br>dorsal</h4>
                  </div>
                </div>
              </div>
              
              <!-- TERCERA FILA -->
              <div class="col-lg-4 col-md-6 col-12 mb-4 px-6">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <img src="assets/img/regalo.png" alt="Obsequio patrocinador" class="benefit-image">
                  </div>
                  <div class="benefit-text">
                    <h4>Obsequios<br>patrocinadores</h4>
                  </div>
                </div>
              </div>
              
              <div class="col-lg-4 col-md-6 col-12 mb-4 px-6">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <img src="assets/img/estrella-del-trofeo.png" alt="Premiación" class="benefit-image">
                  </div>
                  <div class="benefit-text">
                    <h4>Premiación</h4>
                  </div>
                </div>
              </div>
              
              <!-- Tula -->
              <div class="col-lg-4 col-md-6 col-12 mb-4 px-6">
                <div class="benefit-item">
                  <div class="benefit-icon">
                    <img src="assets/img/maletin.png" alt="Tula" class="benefit-image">
                  </div>
                  <div class="benefit-text">
                    <h4>Kit<br>Exclusivo</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Premios Section -->
<section class="premios-section py-3 position-relative overflow-hidden">
  <div class="premios-bg-overlay"></div>
  <div class="container py-2">
    <!-- Título y descripción de la sección -->
    <div class="row justify-content-center mb-3" data-aos="fade-up">
      <div class="col-lg-8 text-center">
        <div class="premios-header mb-2">
          <div class="premios-icon mb-2">
            <i class="fas fa-trophy"></i>
          </div>
          <h2 class="display-5 fw-bold text-white mb-3">PREMIACIÓN</h2>
          <p class="lead text-white mb-0">Premios para los tres primeros puestos de cada categoría</p>
        </div>
      </div>
    </div>
    
    <!-- Contenido principal con imagen y premios -->
    <div class="row align-items-center" data-aos="fade-up">
      <!-- Imagen de Premios -->
      <div class="col-lg-4 mb-3 mb-lg-0">
        <div class="premios-image-container position-relative">
          <img src="assets/img/premio.png" alt="Premios de la carrera" class="img-fluid rounded-3 shadow-lg" style="width: 100%; height: 400px; object-fit: contain;">
          <div class="premios-image-overlay">
            <div class="premios-badge">
              <i class="fas fa-trophy me-2"></i>
              ¡Increíbles Premios!
            </div>
          </div>
        </div>
      </div>
      
      <!-- Contenido de Premios -->
      <div class="col-lg-8">
        <div class="premios-content">
          <div class="premios-highlight mb-3">
            <h3 class="mb-1">Más de <span class="text-warning">5 millones</span> de premios en efectivo</h3>
          </div>
          
          <div class="row g-3">
            <!-- Medalla de ganador -->
            <div class="col-md-6">
              <div class="premio-card-modern">
                <div class="premio-icon-modern medal-gold">
                  <i class="fas fa-medal"></i>
                </div>
                <div class="premio-info-modern">
                  <h5 class="fw-bold mb-2">Medallas de ganadores</h5>
                  <p class="text-muted small mb-0">Para todas las categorías</p>
                </div>
              </div>
            </div>
            
            <!-- Premios en Efectivo -->
            <div class="col-md-6">
              <div class="premio-card-modern">
                <div class="premio-icon-modern money-green">
                  <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="premio-info-modern">
                  <h5 class="fw-bold mb-2">Premios en Efectivo</h5>
                  <p class="text-muted small mb-0">Para las categorías FemTribe y MenTribe</p>
                </div>
              </div>
            </div>
            
            <!-- Premio Sorpresa -->
            <div class="col-md-6">
              <div class="premio-card-modern">
                <div class="premio-icon-modern gift-orange">
                  <i class="fas fa-gift"></i>
                </div>
                <div class="premio-info-modern">
                  <h5 class="fw-bold mb-2">Premio sorpresa</h5>
                  <p class="text-muted small mb-0">Para el 4° puesto de cada categoría</p>
                </div>
              </div>
            </div>
            
            <!-- Bonos Regalo -->
            <div class="col-md-6">
              <div class="premio-card-modern">
                <div class="premio-icon-modern voucher-blue">
                  <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="premio-info-modern">
                  <h5 class="fw-bold mb-2">Bonos y obsequios</h5>
                  <p class="text-muted small mb-0">Para la categoría KidsTribe</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
/* Efectos de partículas animadas */
.premio-particles {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
}

.particle {
  position: absolute;
  width: 4px;
  height: 4px;
  background: #ffd700;
  border-radius: 50%;
  animation: float 6s ease-in-out infinite;
}

.particle:nth-child(1) { left: 10%; animation-delay: 0s; }
.particle:nth-child(2) { left: 30%; animation-delay: 1s; }
.particle:nth-child(3) { left: 50%; animation-delay: 2s; }
.particle:nth-child(4) { left: 70%; animation-delay: 3s; }
.particle:nth-child(5) { left: 90%; animation-delay: 4s; }

@keyframes float {
  0%, 100% { transform: translateY(100vh) scale(0); opacity: 0; }
  10% { opacity: 1; transform: translateY(90vh) scale(1); }
  90% { opacity: 1; transform: translateY(10vh) scale(1); }
}

/* Header espectacular */
.premios-header-spectacular {
  position: relative;
  z-index: 2;
}

.premio-main-icon {
  position: relative;
  display: inline-block;
}

.icon-glow {
  width: 120px;
  height: 120px;
  background: linear-gradient(45deg, #ffd700, #ffed4e, #ffd700);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  animation: pulse-glow 2s ease-in-out infinite;
  box-shadow: 0 0 30px rgba(255, 215, 0, 0.6);
}

.icon-glow i {
  font-size: 3.5rem;
  color: #1a1a1a;
  animation: bounce 2s ease-in-out infinite;
}

@keyframes pulse-glow {
  0%, 100% { transform: scale(1); box-shadow: 0 0 30px rgba(255, 215, 0, 0.6); }
  50% { transform: scale(1.1); box-shadow: 0 0 50px rgba(255, 215, 0, 0.9); }
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.sparkles {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.sparkle {
  position: absolute;
  font-size: 1.5rem;
  animation: sparkle 3s ease-in-out infinite;
}

.sparkle-1 { top: 10%; left: 10%; animation-delay: 0s; }
.sparkle-2 { top: 20%; right: 10%; animation-delay: 0.5s; }
.sparkle-3 { bottom: 20%; left: 15%; animation-delay: 1s; }
.sparkle-4 { bottom: 10%; right: 15%; animation-delay: 1.5s; }

@keyframes sparkle {
  0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
  50% { opacity: 1; transform: scale(1) rotate(180deg); }
}

.premio-title-glow {
  text-shadow: 0 0 20px rgba(255, 215, 0, 0.8), 0 0 40px rgba(255, 215, 0, 0.6);
  animation: title-glow 3s ease-in-out infinite;
}

@keyframes title-glow {
  0%, 100% { text-shadow: 0 0 20px rgba(255, 215, 0, 0.8), 0 0 40px rgba(255, 215, 0, 0.6); }
  50% { text-shadow: 0 0 30px rgba(255, 215, 0, 1), 0 0 60px rgba(255, 215, 0, 0.8); }
}

.premio-subtitle-box {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 193, 7, 0.1));
  border: 2px solid rgba(255, 215, 0, 0.5);
  border-radius: 20px;
  padding: 1.5rem;
  backdrop-filter: blur(10px);
  animation: subtitle-pulse 4s ease-in-out infinite;
}

@keyframes subtitle-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

/* Tarjetas espectaculares */
.premio-spectacular-card {
  background: linear-gradient(145deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.05) 100%);
  border: 2px solid rgba(255,255,255,0.3);
  border-radius: 25px;
  padding: 2rem;
  backdrop-filter: blur(15px);
  position: relative;
  overflow: hidden;
  transition: all 0.4s ease;
  animation: card-float 6s ease-in-out infinite;
}

@keyframes card-float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

.premio-spectacular-card:hover {
  transform: translateY(-15px) scale(1.02);
  box-shadow: 0 25px 50px rgba(0,0,0,0.4);
}

.card-glow-effect {
  position: absolute;
  top: -2px;
  left: -2px;
  right: -2px;
  bottom: -2px;
  background: linear-gradient(45deg, #ffd700, #87CC3E, #ffd700, #87CC3E);
  border-radius: 25px;
  z-index: -1;
  animation: border-glow 3s linear infinite;
}

@keyframes border-glow {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.femtribe-card .card-glow-effect { background: linear-gradient(45deg, #e91e63, #ffd700, #e91e63, #ffd700); }
.mentribe-card .card-glow-effect { background: linear-gradient(45deg, #2196f3, #ffd700, #2196f3, #ffd700); }
.kidstribe-card .card-glow-effect { background: linear-gradient(45deg, #ff9800, #ffd700, #ff9800, #ffd700); }

/* Header visual de categorías */
.premio-header-visual {
  text-align: center;
  margin-bottom: 2rem;
}

.category-badge {
  background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
  border-radius: 20px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(255,255,255,0.3);
}

.badge-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 0.5rem;
  font-size: 1.5rem;
}

.femtribe-badge .badge-icon { background: linear-gradient(135deg, #e91e63, #ad1457); color: white; }
.mentribe-badge .badge-icon { background: linear-gradient(135deg, #2196f3, #1565c0); color: white; }
.kidstribe-badge .badge-icon { background: linear-gradient(135deg, #ff9800, #e65100); color: white; }

.trophy-visual {
  margin: 1rem 0;
}

.trophy-container {
  position: relative;
  display: inline-block;
}

.trophy-main {
  font-size: 3rem;
  color: #ffd700;
  animation: trophy-shine 2s ease-in-out infinite;
}

.trophy-container.kids .trophy-main {
  color: #ff9800;
}

@keyframes trophy-shine {
  0%, 100% { filter: brightness(1) drop-shadow(0 0 10px currentColor); }
  50% { filter: brightness(1.3) drop-shadow(0 0 20px currentColor); }
}

/* Lista visual de premios */
.premio-visual-list {
  gap: 1rem;
}

.premio-visual-item {
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
  border-radius: 15px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  border-left: 5px solid;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.premio-visual-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s ease;
}

.premio-visual-item:hover::before {
  left: 100%;
}

.premio-visual-item:hover {
  transform: translateX(10px) scale(1.02);
  box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

.gold-prize { border-left-color: #ffd700; }
.silver-prize { border-left-color: #c0c0c0; }
.bronze-prize { border-left-color: #cd7f32; }
.special-prize { border-left-color: #87CC3E; }
.kids-prize { border-left-color: #ff9800; }

/* Medallas y posiciones */
.prize-medal {
  margin-right: 1.5rem;
}

.medal-container {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  animation: medal-rotate 4s linear infinite;
}

@keyframes medal-rotate {
  0% { transform: rotateY(0deg); }
  100% { transform: rotateY(360deg); }
}

.medal-container { background: linear-gradient(135deg, #ffd700, #ffed4e); }
.medal-container.silver { background: linear-gradient(135deg, #c0c0c0, #e8e8e8); }
.medal-container.bronze { background: linear-gradient(135deg, #cd7f32, #daa520); }
.medal-container.special { background: linear-gradient(135deg, #87CC3E, #6ba82f); }
.medal-container.kids { background: linear-gradient(135deg, #ff9800, #ffa726); }

.medal-container i {
  font-size: 1.5rem;
  color: #1a1a1a;
}

.position-number {
  font-size: 0.8rem;
  font-weight: bold;
  color: #1a1a1a;
}

/* Información de premios */
.prize-info h5 {
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.money-display {
  display: flex;
  align-items: baseline;
  margin-bottom: 0.5rem;
}

.currency {
  font-size: 1.2rem;
  color: #ffd700;
  font-weight: bold;
  margin-right: 0.2rem;
}

.amount {
  font-size: 1.8rem;
  font-weight: bold;
  color: #ffd700;
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
  animation: money-glow 2s ease-in-out infinite;
}

@keyframes money-glow {
  0%, 100% { text-shadow: 0 2px 4px rgba(0,0,0,0.5), 0 0 10px rgba(255,215,0,0.5); }
  50% { text-shadow: 0 2px 4px rgba(0,0,0,0.5), 0 0 20px rgba(255,215,0,0.8); }
}

.money-display.silver .currency,
.money-display.silver .amount { color: #c0c0c0; }

.money-display.bronze .currency,
.money-display.bronze .amount { color: #cd7f32; }

.surprise-display,
.kids-display {
  margin-bottom: 0.5rem;
}

.surprise-text,
.kids-prize-text {
  font-size: 1.2rem;
  font-weight: bold;
  color: #87CC3E;
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
  animation: text-pulse 2s ease-in-out infinite;
}

.kids-prize-text {
  color: #ff9800;
}

@keyframes text-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.prize-extras {
  font-size: 0.9rem;
  color: rgba(255,255,255,0.8);
  font-weight: bold;
  text-align: center;
  animation: extras-bounce 3s ease-in-out infinite;
}

@keyframes extras-bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}
</style>


<!-- Call to Action - COMENTADO TEMPORALMENTE
<section class="section py-5" style="background-image: url('assets/img/femtribe_verde.png'); background-size: cover; background-position: center; position: relative;">>
  <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8));"></div>
  <div class="container py-5 position-relative" style="z-index: 2;">
    <div class="row">
      <div class="col-lg-8 mx-auto text-center text-white" data-aos="fade-up">
        <h6 class="text-primary fw-bold mb-3">ÚNETE A NOSOTROS</h6>
        <h2 class="display-4 fw-bold mb-4">¿Lista para correr con Femtribe?</h2>
        <p class="lead mb-5">No te pierdas la oportunidad de ser parte de esta increíble experiencia. ¡Inscríbete ahora y vive la carrera de tus sueños!</p>
        <div class="d-flex justify-content-center gap-3">
          <a href="/inscripcion" class="btn btn-primary btn-lg">INSCRÍBETE AHORA</a>
                <a href="/contacto" class="btn btn-outline-light btn-lg">Contáctanos</a>
        </div>
      </div>
    </div>
  </div>
</section>
FIN COMENTARIO CALL TO ACTION -->

<!-- FAQ Section - COMENTADO TEMPORALMENTE
<section class="section py-5">
  <div class="container py-4">
    <div class="row justify-content-center mb-5" data-aos="fade-up">
      <div class="col-lg-7 text-center">
        <h6 class="text-primary fw-bold mb-3">PREGUNTAS FRECUENTES</h6>
        <h2 class="display-5 fw-bold">Todo lo que necesitas saber</h2>
      </div>
    </div>
    
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item border-0 mb-3 shadow-sm" data-aos="fade-up" data-aos-delay="100">
            <h2 class="accordion-header" id="headingOne">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                ¿Cuándo es la carrera?</button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                La carrera Corre con Femtribe se realizará el 5 de diciembre de 2025. La hora de inicio será a las 7:00 AM para todas las categorías.
              </div>
            </div>
          </div>
          
          <div class="accordion-item border-0 mb-3 shadow-sm" data-aos="fade-up" data-aos-delay="200">
            <h2 class="accordion-header" id="headingTwo">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                ¿Qué incluye la inscripción?</button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                La inscripción incluye: camiseta técnica oficial del evento, número de participante, chip de cronometraje, medalla de finalista, hidratación durante el recorrido, kit de recuperación y acceso a la zona de recuperación post-carrera.
              </div>
            </div>
          </div>
          
          <div class="accordion-item border-0 mb-3 shadow-sm" data-aos="fade-up" data-aos-delay="300">
            <h2 class="accordion-header" id="headingThree">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                ¿Hay límite de edad para participar?</button>
            </h2>
            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Para la categoría 5K, la edad mínima es de 12 años. Para 10K, 15 años y para 21K, 18 años. Todos los menores de edad deben contar con autorización firmada por sus padres o tutores legales.
              </div>
            </div>
          </div>
          
          <div class="accordion-item border-0 mb-3 shadow-sm" data-aos="fade-up" data-aos-delay="400">
            <h2 class="accordion-header" id="headingFour">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                ¿Puedo transferir mi inscripción a otra persona?</button>
            </h2>
            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                Sí, las inscripciones son transferibles hasta 30 días antes del evento. Deberás contactar a la organización a través del correo electrónico info@femtribe.com con los datos de la persona a quien deseas transferir tu inscripción.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
FIN COMENTARIO FAQ -->

<script>
// Cuenta regresiva
const targetDate = new Date("2025-11-23T06:30:00").getTime();

setInterval(() => {
    const now = new Date().getTime();
    const diff = targetDate - now;

    if (diff <= 0) {
        document.getElementById("countdown-days").innerHTML = "00";
        document.getElementById("countdown-hours").innerHTML = "00";
        document.getElementById("countdown-minutes").innerHTML = "00";
        document.getElementById("countdown-seconds").innerHTML = "00";
        return;
    }

    const days = Math.floor(diff / (1000*60*60*24));
    const hours = Math.floor((diff % (1000*60*60*24)) / (1000*60*60));
    const mins = Math.floor((diff % (1000*60*60)) / (1000*60));
    const secs = Math.floor((diff % (1000*60)) / 1000);

    document.getElementById("countdown-days").innerHTML = days.toString().padStart(2, '0');
    document.getElementById("countdown-hours").innerHTML = hours.toString().padStart(2, '0');
    document.getElementById("countdown-minutes").innerHTML = mins.toString().padStart(2, '0');
    document.getElementById("countdown-seconds").innerHTML = secs.toString().padStart(2, '0');
}, 1000);

// Carousel autoplay - OPTIMIZADO
document.addEventListener('DOMContentLoaded', function() {
  const heroCarousel = new bootstrap.Carousel(document.getElementById('heroCarousel'), {
    interval: 4000, // Cambiado a 4000ms como solicitado
    ride: 'carousel',
    wrap: true,
    pause: 'hover' // Pausa al hacer hover para mejor UX
  });
});
</script>

<script>
// Video Player Functionality
function playVideo() {
  const video = document.getElementById('femtribeVideo');
  const overlay = document.getElementById('playButtonOverlay');
  
  if (video) {
    video.play();
    overlay.classList.add('hidden');
  }
}

// Show overlay when video is paused or ended
document.addEventListener('DOMContentLoaded', function() {
  const video = document.getElementById('femtribeVideo');
  const overlay = document.getElementById('playButtonOverlay');
  
  if (video && overlay) {
    video.addEventListener('pause', function() {
      overlay.classList.remove('hidden');
    });
    
    video.addEventListener('ended', function() {
      overlay.classList.remove('hidden');
    });
    
    video.addEventListener('play', function() {
      overlay.classList.add('hidden');
    });
    
    // Hide overlay when video starts playing
    video.addEventListener('loadeddata', function() {
      if (!video.paused) {
        overlay.classList.add('hidden');
      }
    });
  }
});

// Animate stats when they come into view
function animateStats() {
  const stats = document.querySelectorAll('.stat-item h4');
  
  stats.forEach(stat => {
    const finalValue = stat.textContent;
    const numericValue = parseInt(finalValue.replace(/\D/g, ''));
    
    if (numericValue) {
      let currentValue = 0;
      const increment = numericValue / 50;
      const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= numericValue) {
          stat.textContent = finalValue;
          clearInterval(timer);
        } else {
          stat.textContent = Math.floor(currentValue) + (finalValue.includes('+') ? '+' : '');
        }
      }, 30);
    }
  });
}

// Intersection Observer for stats animation
const statsObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      animateStats();
      statsObserver.unobserve(entry.target);
    }
  });
});

document.addEventListener('DOMContentLoaded', function() {
  const videoStats = document.querySelector('.video-stats');
  if (videoStats) {
    statsObserver.observe(videoStats);
  }
});

// COMENTADO TEMPORALMENTE - Sponsors Carousel JavaScript
/*
document.addEventListener('DOMContentLoaded', function() {
    const track = document.getElementById('sponsorsTrack');
    const prevBtn = document.getElementById('sponsorsPrev');
    const nextBtn = document.getElementById('sponsorsNext');
    
    if (!track || !prevBtn || !nextBtn) return;
    
    const items = track.querySelectorAll('.sponsor-item');
    const itemWidth = 150; // Width of each sponsor item
    const gap = 40; // Gap between items
    const totalItemWidth = itemWidth + gap;
    
    let currentIndex = 0;
    let itemsToShow = 4; // Default items to show
    
    // Calculate items to show based on container width
    function calculateItemsToShow() {
        const containerWidth = track.parentElement.offsetWidth;
        itemsToShow = Math.floor(containerWidth / totalItemWidth);
        if (itemsToShow < 1) itemsToShow = 1;
        if (itemsToShow > items.length) itemsToShow = items.length;
    }
    
    // Update carousel position
    function updateCarousel() {
        const translateX = -currentIndex * totalItemWidth;
        track.style.transform = `translateX(${translateX}px)`;
        
        // Update button states
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= items.length - itemsToShow;
    }
    
    // Navigate to previous items
    function goToPrev() {
        if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    }
    
    // Navigate to next items
    function goToNext() {
        if (currentIndex < items.length - itemsToShow) {
            currentIndex++;
            updateCarousel();
        }
    }
    
    // Event listeners
    prevBtn.addEventListener('click', goToPrev);
    nextBtn.addEventListener('click', goToNext);
    
    // Handle window resize
    window.addEventListener('resize', function() {
        calculateItemsToShow();
        // Adjust current index if needed
        if (currentIndex > items.length - itemsToShow) {
            currentIndex = Math.max(0, items.length - itemsToShow);
        }
        updateCarousel();
    });
    
    // Initialize carousel
    calculateItemsToShow();
    updateCarousel();
    
    // Auto-scroll functionality (optional)
    let autoScrollInterval;
    
    function startAutoScroll() {
        autoScrollInterval = setInterval(() => {
            if (currentIndex < items.length - itemsToShow) {
                goToNext();
            } else {
                currentIndex = 0;
                updateCarousel();
            }
        }, 4000);
    }
    
    function stopAutoScroll() {
        clearInterval(autoScrollInterval);
    }
    
    // Start auto-scroll
    startAutoScroll();
    
    // Pause auto-scroll on hover
    const carouselContainer = document.querySelector('.sponsors-carousel-container');
    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', stopAutoScroll);
        carouselContainer.addEventListener('mouseleave', startAutoScroll);
    }
});
*/
// FIN COMENTARIO JAVASCRIPT SPONSORS

</script>

<?php include 'layouts/footer.php'; ?>

