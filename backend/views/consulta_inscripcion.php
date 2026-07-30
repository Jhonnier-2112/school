<?php
include 'layouts/header.php';
?>

<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 0;
        color: white;
        text-align: center;
        display: none;
    }
    
    .consultation-form {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        padding: 40px;
        position: relative;
        z-index: 10;
    }
    
    .form-floating {
        margin-bottom: 20px;
    }
    
    .form-floating > .form-control {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem 0.75rem;
        height: calc(3.5rem + 2px);
        transition: all 0.3s ease;
    }
    
    .form-floating > .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .form-floating > label {
        color: #6c757d;
        font-weight: 500;
    }
    
    .btn-consultar {
        background: #87CC3E;
        border: none;
        border-radius: 12px;
        padding: 15px 40px;
        font-weight: 600;
        font-size: 1.1rem;
        color: white;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .btn-consultar:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(135, 204, 62, 0.3);
        color: white;
        background: #7AB836;
    }
    
    .consultation-icon {
        background: #87CC3E;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
        color: white;
        font-size: 2rem;
    }
    
    .result-container {
        margin-top: 30px;
        padding: 20px;
        border-radius: 12px;
        display: none;
    }
    
    .result-success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        color: white;
    }
    
    .result-not-found {
        background: linear-gradient(135deg, #ff6b6b 0%, #ffa8a8 100%);
        color: white;
    }
    
    .participant-data {
        background: rgba(255,255,255,0.2);
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }
    
    .data-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.3);
    }
    
    .data-row:last-child {
        border-bottom: none;
    }
    
    .data-label {
        font-weight: 600;
        opacity: 0.9;
    }
    
    .data-value {
        font-weight: 400;
    }
    
    .btn-inscribirse {
        background: white;
        color: #667eea;
        border: 2px solid white;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .btn-inscribirse:hover {
        background: transparent;
        color: white;
        text-decoration: none;
    }
    
    .loading-spinner {
        display: none;
        text-align: center;
        margin: 20px 0;
    }
    
    .spinner-border {
        color: #667eea;
    }
    
    /* Media queries para responsive */
    @media (max-width: 768px) {
        .container {
            margin-top: 90px !important;
        }
    }
    
    @media (max-width: 576px) {
        .container {
            margin-top: 100px !important;
            padding: 0 10px !important;
        }
        
        .display-5 {
            font-size: 1.75rem !important;
            line-height: 1.2 !important;
        }
        
        .lead {
            font-size: 1rem !important;
        }
        
        .consultation-form {
            padding: 25px 15px !important;
            margin: 0 5px !important;
            border-radius: 15px !important;
        }
        
        .consultation-icon {
            width: 50px !important;
            height: 50px !important;
            font-size: 1.2rem !important;
            margin-bottom: 15px !important;
        }
        
        .form-floating > .form-control {
            height: calc(2.8rem + 2px) !important;
            font-size: 16px !important;
        }
        
        .btn-consultar {
            padding: 10px 25px !important;
            font-size: 0.95rem !important;
        }
        
        .participant-data {
            padding: 12px !important;
        }
    }
    
    /* Media query específica para iPhone SE y pantallas muy pequeñas */
    @media (max-width: 375px) {
        .container {
            margin-top: 110px !important;
        }
        
        .display-5 {
            font-size: 1.5rem !important;
        }
        
        .consultation-form {
            padding: 20px 10px !important;
            margin: 0 2px !important;
        }
    }
    
    @media (max-width: 480px) {
        .container {
            margin-top: 105px !important;
            padding: 0 8px !important;
        }
        
        .display-5 {
            font-size: 1.5rem !important;
        }
        
        .consultation-form {
            padding: 20px 12px !important;
            margin: 0 2px !important;
        }
        
        .form-floating > .form-control {
            font-size: 16px !important;
        }
        
        .btn-consultar {
            padding: 8px 20px !important;
            font-size: 0.9rem !important;
        }
    }
</style>

<div class="container" style="margin-top: 120px; margin-bottom: 30px; min-height: calc(100vh - 180px);">
    <div class="container py-5" style="position: relative; z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10 text-center">
                <h1 class="display-5 fw-bold mb-3" style="color: #000000;">Consulta tu inscripción</h1>
                <p class="lead text-muted mb-5">Verifica si ya estás registrado en la carrera <strong>CORRE CON FEMTRIBE</strong></p>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-md-10">
                        <div class="consultation-form">
                            <div class="consultation-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            
                            <h3 class="text-center mb-4 fw-bold">Buscar Inscripción</h3>
                            <p class="text-center text-muted mb-4">
                                Ingresa tu número de documento para verificar tu inscripción
                            </p>
                
                <form id="consultaForm">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="numero_documento" name="numero_documento" 
                               placeholder="Número de documento" required pattern="[0-9]+" 
                               title="Solo se permiten números">
                        <label for="numero_documento">
                            <i class="fas fa-hashtag me-2"></i>Número de Documento
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-consultar">
                        <i class="fas fa-search me-2"></i>Consultar Inscripción
                    </button>
                </form>
                
                <div class="loading-spinner">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Consultando...</span>
                    </div>
                    <p class="mt-2 text-muted">Buscando tu inscripción...</p>
                </div>
                
                <div id="resultContainer" class="result-container">
                    <!-- Los resultados se mostrarán aquí -->
                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('consultaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const numeroDocumento = document.getElementById('numero_documento').value;
    const loadingSpinner = document.querySelector('.loading-spinner');
    const resultContainer = document.getElementById('resultContainer');
    
    // Validaciones
    if (!numeroDocumento) {
        alert('Por favor ingresa tu número de documento');
        return;
    }
    
    if (!/^[0-9]+$/.test(numeroDocumento)) {
        alert('El número de documento solo debe contener números');
        return;
    }
    
    // Mostrar loading
    loadingSpinner.style.display = 'block';
    resultContainer.style.display = 'none';
    
    // Realizar consulta AJAX
    fetch('consultar_inscripcion', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `numero_documento=${encodeURIComponent(numeroDocumento)}`
    })
    .then(response => response.json())
    .then(data => {
        loadingSpinner.style.display = 'none';
        
        if (data.success) {
            // Participante encontrado
            resultContainer.className = 'result-container result-success';
            resultContainer.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h4 class="fw-bold">¡Inscripción Encontrada!</h4>
                    <p class="mb-0">Tu inscripción está registrada exitosamente</p>
                </div>
                
                <div class="participant-data">
                    <div class="data-row">
                        <span class="data-label">Nombre Completo:</span>
                        <span class="data-value">${data.participant.nombres} ${data.participant.apellidos}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Documento:</span>
                        <span class="data-value">${data.participant.tipo_documento} ${data.participant.numero_documento}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Email:</span>
                        <span class="data-value">${data.participant.email}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Teléfono:</span>
                        <span class="data-value">${data.participant.telefono}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Fecha de Inscripción:</span>
                        <span class="data-value">${new Date(data.participant.created_at).toLocaleDateString('es-ES')}</span>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="mb-2"><strong>¡Nos vemos en la carrera!</strong></p>
                    <p class="mb-0">Recuerda revisar tu correo electrónico para más información.</p>
                </div>
            `;
        } else {
            // Participante no encontrado
            resultContainer.className = 'result-container result-not-found';
            resultContainer.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h4 class="fw-bold">Inscripción No Encontrada</h4>
                    <p class="mb-3">No encontramos ninguna inscripción con el documento ${numeroDocumento}</p>
                    <p class="mb-4">¡Pero aún puedes participar!</p>
                    
                    <a href="/inscribirse" class="btn-inscribirse">
                        <i class="fas fa-running me-2"></i>Inscríbete Ahora
                    </a>
                </div>
            `;
        }
        
        resultContainer.style.display = 'block';
        resultContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    })
    .catch(error => {
        loadingSpinner.style.display = 'none';
        console.error('Error:', error);
        
        resultContainer.className = 'result-container result-not-found';
        resultContainer.innerHTML = `
            <div class="text-center">
                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                <h4 class="fw-bold">Error en la Consulta</h4>
                <p class="mb-3">Ocurrió un error al procesar tu consulta. Por favor, inténtalo de nuevo.</p>
                <button onclick="location.reload()" class="btn-inscribirse">
                    <i class="fas fa-redo me-2"></i>Intentar de Nuevo
                </button>
            </div>
        `;
        resultContainer.style.display = 'block';
    });
});

// Validación en tiempo real para solo números
document.getElementById('numero_documento').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php include 'layouts/footer.php'; ?>