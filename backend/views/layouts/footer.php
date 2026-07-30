</main>

<footer class="minimal-footer">
  <!-- Main footer content -->
  <div class="footer-content">
    <div class="container">
      <!-- Centered Logo and Slogan -->
      <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10 text-center">
          <div class="footer-brand-center">
            <div class="logo-container">
              <img src="assets/img/femtribe_verde.png" alt="Femtribe Logo" class="footer-logo" onerror="this.src='assets/img/logoverde.png'; this.onerror=null;">
            </div>
            <h2 class="brand-slogan">CUERPO FUERTE, MENTE LIBRE, ALMA EN TRIBU</h2>
          </div>
        </div>
      </div>  
      

      
      <!-- Texto Síguenos -->
      <div class="row justify-content-center mt-4">
        <div class="col-auto">
          <h3 class="siguenos-text">SÍGUENOS</h3>
        </div>
      </div>
      
      <!-- Social Media Icons -->
      <div class="row justify-content-center mt-4">
        <div class="col-auto">
          <div class="social-icons-center">
            <a href="https://www.facebook.com/share/17Jx3KEvf1/" target="_blank" aria-label="Facebook">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="https://www.instagram.com/fem_tribe?utm_source=ig_web_button_share_sheet&igsh=eHczOGNiZmFjcW93" target="_blank" aria-label="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
            <!-- <a href="#" aria-label="TikTok">
              <i class="fab fa-tiktok"></i>
            </a> -->

            <a href="https://strava.app.link/dSrxQ3c7aXb" aria-label="Strava">
              <i class="fab fa-strava"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom Bar -->
  <div class="footer-bottom">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-12 text-center">
          <p class="copyright-text">
            &copy; <?php echo date('Y'); ?> Femtribe. Todos los derechos reservados.
          </p>
        </div>
      </div>
    </div>
  </div>
</footer>

<style>
/* Minimal Footer Styles */
.minimal-footer {
  background: #2c2c2c;
  color: #ffffff;
  border-top: 1px solid #404040;
}

/* Footer Content */
.footer-content {
  padding: 30px 0 20px;
}

/* Centered Brand Section */
.footer-brand-center {
  margin-bottom: 15px;
  margin-top: -30px;
}

.logo-container {
  margin-bottom: 10px;
  position: relative;
}

.footer-logo {
  height: 100px;
  width: auto;
  transition: all 0.3s ease;
}

.footer-logo:hover {
  transform: scale(1.05);
}

.brand-slogan {
  font-size: 1.1rem;
  font-weight: 300;
  font-family: 'Piazzolla', serif;
  color: #ffffff;
  margin: 0;
  letter-spacing: 2px;
  line-height: 1.4;
  position: relative;
  text-transform: uppercase;
}

.brand-slogan::after {
  content: '';
  position: absolute;
  bottom: -15px;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 3px;
  background: linear-gradient(90deg, transparent, #87CC3E, transparent);
  border-radius: 2px;
}

/* Texto Síguenos */
.siguenos-text {
  color: #87CC3E;
  font-size: 1.1rem;
  font-weight: 600;
  text-align: center;
  margin: 0;
  letter-spacing: 2px;
  text-transform: uppercase;
  font-family: 'Piazzolla', serif;
}

/* Contact Row */
.contact-row {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 40px;
  flex-wrap: wrap;
  padding: 20px 0;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 15px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(135, 204, 62, 0.1);
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 15px;
  transition: all 0.3s ease;
}

.contact-item:hover {
  transform: translateY(-3px);
}

.contact-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, rgba(135, 204, 62, 0.3), rgba(135, 204, 62, 0.4));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(135, 204, 62, 0.15);
  transition: all 0.3s ease;
}

.contact-icon:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(135, 204, 62, 0.25);
}

.contact-icon i {
  color: #ffffff;
  font-size: 1.2rem;
}

.contact-text {
  display: flex;
  flex-direction: column;
}

.contact-label {
  color: #87CC3E;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.contact-value {
  color: #ffffff;
  font-size: 1rem;
  font-weight: 500;
}

/* Social Icons Center */
.social-icons-center {
  display: flex;
  gap: 20px;
  justify-content: center;
  flex-wrap: wrap;
}

.social-icons-center a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.1);
  border: 2px solid rgba(135, 204, 62, 0.3);
  border-radius: 50%;
  color: #cccccc;
  text-decoration: none;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  position: relative;
  overflow: hidden;
}

.social-icons-center a::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(135, 204, 62, 0.2), transparent);
  transition: left 0.5s ease;
}

.social-icons-center a:hover::before {
  left: 100%;
}

.social-icons-center a:hover {
  transform: translateY(-5px) scale(1.1);
  border-color: #87CC3E;
  color: #87CC3E;
  box-shadow: 0 8px 25px rgba(135, 204, 62, 0.4);
}

.social-icons-center a i {
  font-size: 1.2rem;
  z-index: 1;
  position: relative;
}

/* Footer Bottom */
.footer-bottom {
  background: #1a1a1a;
  padding: 10px 0;
  border-top: 1px solid #404040;
}

.copyright-text {
  color: #cccccc;
  margin: 0;
  font-size: 0.9rem;
  font-weight: 300;
}

/* Responsive Design */
@media (max-width: 768px) {
  .footer-content {
    padding: 25px 0 15px;
  }
  
  .footer-logo {
    height: 75px;
  }
  
  .brand-slogan {
    font-size: 1rem;
  }
  
  .contact-row {
    gap: 25px;
    padding: 15px;
  }
  
  .contact-item {
    flex-direction: column;
    text-align: center;
    gap: 10px;
  }
  
  .social-icons-center {
    gap: 15px;
  }
  
  .social-icons-center a {
    width: 45px;
    height: 45px;
  }
}

@media (max-width: 576px) {
  .brand-slogan {
    font-size: 0.9rem;
    padding: 0 20px;
  }
  
  .contact-row {
    flex-direction: column;
    gap: 20px;
  }
  
  .footer-logo {
    height: 65px;
  }
}

/* Animation for logo */
@keyframes logoGlow {
  0%, 100% { 
    transform: scale(1);
  }
  50% { 
    transform: scale(1.02);
  }
}

.footer-logo {
  animation: logoGlow 3s ease-in-out infinite;
}
</style>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Font Awesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true
  });
  
  // Navbar scroll effect
  window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (navbar && navbar.classList) {
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    }
  });
</script>

<!-- Tu JavaScript -->
<!-- <script src="assets/js/app.js"></script> -->

</body>
</html>
