<?php 
// Incluir la configuración para obtener los mensajes
require_once __DIR__ . '/../../config/RegistrationConfig.php';
$mensajes = RegistrationConfig::getMensajeCerradas();

include 'layouts/header.php'; 
?>

<style>
.registration-closed-hero {
    background: linear-gradient(135deg, rgba(30, 60, 114, 0.8) 0%, rgba(42, 82, 152, 0.8) 100%), url('assets/img/inscribete.png') center/cover no-repeat;
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 80px 0 20px 0;
    margin-top: 80px;
    margin-bottom: 0;
}

.registration-closed-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

.registration-closed-hero .container {
    position: relative;
    z-index: 2;
}

.closed-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 60px 40px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
    backdrop-filter: blur(10px);
}

.closed-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
}

.closed-icon i {
    font-size: 50px;
    color: white;
}

.closed-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
    font-family: 'Piazzolla', serif;
}

.closed-message {
    font-size: 1.2rem;
    color: #555;
    margin-bottom: 30px;
    line-height: 1.6;
}

.closed-additional {
    font-size: 1.1rem;
    color: #7f8c8d;
    margin-bottom: 40px;
    padding: 20px;
    background: rgba(126, 211, 33, 0.1);
    border-radius: 10px;
    border-left: 4px solid #7ED321;
}

.social-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
}

.social-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: #7ED321;
    color: white;
    border-radius: 50%;
    text-decoration: none;
    font-size: 20px;
    transition: all 0.3s ease;
}

.social-link:hover {
    background: #6bc91a;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(126, 211, 33, 0.4);
    color: white;
}

.btn-home {
    background: linear-gradient(135deg, #7ED321, #6bc91a);
    border: none;
    color: white;
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 50px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(126, 211, 33, 0.3);
}

.btn-home:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(126, 211, 33, 0.4);
    color: white;
    text-decoration: none;
}

.btn-consulta {
    background: transparent;
    border: 2px solid #3498db;
    color: #3498db;
    padding: 12px 30px;
    font-size: 1rem;
    font-weight: 500;
    border-radius: 50px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    margin-left: 15px;
}

.btn-consulta:hover {
    background: #3498db;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
}

.pulse-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

@media (max-width: 768px) {
    .closed-card {
        padding: 40px 20px;
        margin: 20px;
    }
    
    .closed-title {
        font-size: 2rem;
    }
    
    .closed-message {
        font-size: 1.1rem;
    }
    
    .social-links {
        gap: 15px;
    }
    
    .btn-home, .btn-consulta {
        display: block;
        margin: 10px auto;
        text-align: center;
    }
}
</style>

<div class="registration-closed-hero">
    <div class="container">
        <div class="closed-card pulse-animation">
            <div class="closed-icon">
                <i class="fas fa-door-closed"></i>
            </div>
            
            <h1 class="closed-title"><?= htmlspecialchars($mensajes['titulo']) ?></h1>
            
            <p class="closed-message">
                <?= $mensajes['mensaje'] ?>
            </p>
            
            <div class="closed-additional">
                <i class="fas fa-info-circle me-2" style="color: #7ED321;"></i>
                <?= htmlspecialchars($mensajes['adicional']) ?>
            </div>
            
            <div class="social-links">
                <a href="https://www.facebook.com/share/17Jx3KEvf1/" target="_blank" class="social-link" title="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://www.instagram.com/fem_tribe?utm_source=ig_web_button_share_sheet&igsh=eHczOGNiZmFjcW93" target="_blank" class="social-link" title="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
            
            <div class="text-center">
                <a href="/" class="btn-home">
                    <i class="fas fa-home"></i>
                    Volver al Inicio
                </a>
                
                <a href="/consultar" class="btn-consulta">
                    <i class="fas fa-search"></i>
                    Consultar Inscripción
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Agregar un poco de interactividad
document.addEventListener('DOMContentLoaded', function() {
    // Efecto de entrada suave
    const card = document.querySelector('.closed-card');
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    
    setTimeout(() => {
        card.style.transition = 'all 0.8s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, 200);
    
    // Efecto hover en los iconos sociales
    const socialLinks = document.querySelectorAll('.social-link');
    socialLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) rotate(5deg)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) rotate(0deg)';
        });
    });
});
</script>

<?php include 'layouts/footer.php'; ?>