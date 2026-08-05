<?php

namespace App\Services;

require_once __DIR__ . '/../config/EmailConfig.php';

// Cargar autoloader de vendor si es necesario
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $vendorPath = realpath(__DIR__ . '/../../frontend/vendor/autoload.php');
    if ($vendorPath && file_exists($vendorPath)) {
        require_once $vendorPath;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }
    
    private function setupMailer() {
        try {
            // Configuración del servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = \EmailConfig::SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = \EmailConfig::SMTP_USERNAME;
            $this->mailer->Password = \EmailConfig::SMTP_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = \EmailConfig::SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';
            
            // Configurar remitente por defecto
            $this->mailer->setFrom(\EmailConfig::SMTP_USERNAME, 'FemTribe');
            
        } catch (Exception $e) {
            error_log("Error en setupMailer: " . $e->getMessage());
            throw $e; // Lanzar la excepción para que se capture arriba
        }
    }
    
    public function sendWelcomeEmail($participantData) {
        try {
            // Limpiar destinatarios anteriores
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();
            
            // Configurar destinatario
            $this->mailer->addAddress($participantData['email'], $participantData['nombres'] . ' ' . $participantData['apellidos']);
            
            // Configurar remitente
            $this->mailer->setFrom(\EmailConfig::SMTP_USERNAME, 'FemTribe');
            $this->mailer->addReplyTo(\EmailConfig::SMTP_USERNAME, 'FemTribe');
            
            // Configurar el correo
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '🎉 ¡Gracias por inscribirte a Corre con FemTribe! 🎉 - Confirmación de Inscripción';
            
            // Embeber la imagen del logo
            $logoPath = __DIR__ . '/../../img/logocorreo.png';
            if (file_exists($logoPath)) {
                $this->mailer->addEmbeddedImage($logoPath, 'femtribe_logo', 'logocorreo.png', 'base64', 'image/png');
            }
            
            // Generar el cuerpo del correo
            $this->mailer->Body = $this->generateWelcomeEmailTemplate($participantData);
            
            // Enviar el correo
            $result = $this->mailer->send();
            error_log("Email enviado a " . $participantData['email'] . ": " . ($result ? "ÉXITO" : "FALLÓ"));
            return $result;
        } catch (Exception $e) {
            error_log("Error al enviar email a " . $participantData['email'] . ": " . $e->getMessage());
            throw $e; // Lanzar la excepción para que se capture en el controlador
        }
    }
    
    private function generateWelcomeEmailTemplate($participantData) {
        $nombre = htmlspecialchars($participantData['nombres'] . ' ' . $participantData['apellidos']);
        $documento = htmlspecialchars($participantData['numero_documento']);
        $tipoDocumento = $this->getTipoDocumentoText($participantData['tipo_documento']);
        
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a FemTribe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 20px;
        }
        .header {
            background: linear-gradient(135deg, #87CC3E 0%, #87CC3E 100%);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        .logo-container {
            margin-bottom: 15px;
        }
        .logo {
            max-width: 120px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .header-title {
            font-size: 24px;
            font-weight: bold;
            margin: 15px 0 5px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .header-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
        }
        .signature {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            margin-top: 20px;
        }
        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #87CC3E;
            letter-spacing: 1px;
            margin: 0;
            text-transform: uppercase;
            display: none;
        }
        .logo-subtitle {
            font-size: 12px;
            color: #666;
            margin: 5px 0 0 0;
            text-transform: lowercase;
            letter-spacing: 2px;
            display: none;
        }
        .subtitle {
            font-size: 1.1em;
            margin: 0;
        }
        .content {
            padding: 30px;
        }
        .welcome-text {
            font-size: 1.1em;
            margin-bottom: 20px;
        }
        .info-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #87CC3E;
        }
        .info-card h3 {
            margin-top: 0;
            color: #7CB342;
        }
        .footer {
            color: #333;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="cid:femtribe_logo" alt="FemTribe Logo" class="logo">
            </div>
            <h1 class="header-title">¡Bienvenido a FemTribe!</h1>
            <p class="header-subtitle">Cuerpo fuerte, mente libre, alma en tribu</p>
        </div>
        
        <div class="content">
            <div class="welcome-text">
                <p>¡Hola <strong>' . $nombre . '</strong>!</p>
                <p><strong>Identificado con:</strong> ' . $tipoDocumento . ' - ' . $documento . '</p>
                <p>¡Gracias por unirte a <strong>Corre con FemTribe</strong>! 🙌 Estamos muy felices de que hagas parte de esta experiencia única.</p>
            </div>
            
            <div class="info-card">
                <h3>🏃‍♀️ Información de la Carrera</h3>
                <p><strong>📅 Fecha de la carrera:</strong> Domingo, 23 de noviembre de 2025</p>
                <p><strong>⏰ Hora:</strong> 6:30 a.m.</p>
                <p><strong>📍 Lugar:</strong> Parque Biosaludable de Ricaurte</p>
                <p><strong>✅️ Código de vestimenta:</strong> Negro o Verde</p>
            </div>
            
            <div class="info-card">
                <h3>🛍️ Reclamo de kit y dorsal</h3>
                <p><strong>📅 Fechas:</strong> Sábado, 22 de noviembre de 2025</p>
                <p><strong>📍 Lugar:</strong> Parque Biosaludable de Ricaurte</p>
                <p><strong>⏰ Horario:</strong> 8:00 AM a 7:00 PM</p>
            </div>
            
            <p>🖤 Recuerda llegar a tiempo el día de la carrera y vivir la energía de la tribu desde la salida hasta la meta 💚</p>
            <p><strong>¡Nos vemos muy pronto para correr juntos! </strong></p>
        </div>
        
        <div class="signature">
            <p>Con cariño,<br><strong>Equipo FemTribe</strong></p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getTipoDocumentoText($tipo) {
        switch($tipo) {
            case 'cedula_ciudadania':
                return 'Cédula de ciudadanía';
            case 'tarjeta_identidad':
                return 'Tarjeta de identidad';
            case 'pasaporte':
                return 'Pasaporte';
            default:
                return 'Documento';
        }
    }
}