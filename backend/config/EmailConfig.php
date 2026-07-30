<?php

class EmailConfig {
    // Configuración SMTP para Gmail
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_SECURE = 'tls';
    
    // Credenciales del correo (deberán ser configuradas por el administrador)
    // IMPORTANTE: Cambiar estos valores por las credenciales reales
    // Para Gmail, usar una "Contraseña de aplicación" en lugar de la contraseña normal
    const SMTP_USERNAME = 'femtribe25@gmail.com'; // Cambiar por el correo real
    const SMTP_PASSWORD = 'ajec fuvo namt wbnt';                // Cambiar por la contraseña de aplicación
    
    // Información del remitente
    const FROM_EMAIL = 'femtribe25@gmail.com';    // Cambiar por el correo real
    const FROM_NAME = 'FEMTRIBE - Corre con FemTribe';
    
    // Configuración del correo
    const CHARSET = 'UTF-8';
    const IS_HTML = true;
    
    // Método para verificar si la configuración está lista
    public static function isConfigured() {
        return self::SMTP_USERNAME !== 'femtribe.notifications@gmail.com' && 
               self::SMTP_PASSWORD !== 'app-password-here';
    }
}