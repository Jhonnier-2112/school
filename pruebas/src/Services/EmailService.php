<?php

namespace App\Services;

/**
 * Servicio de envío de correos usando SMTP nativo de PHP (sin dependencias externas).
 * Compatible con Hostinger SMTP.
 */
class EmailService {

    private array $config;

    public function __construct(array $config) {
        $this->config = $config['mail'];
    }

    /**
     * Envía el correo de bienvenida/activación al nuevo estudiante.
     * Contiene el link para que establezca su contraseña.
     */
    public function sendActivationEmail(
        string $toEmail,
        string $toName,
        string $activationToken,
        string $baseUrl
    ): bool {
        $link    = rtrim($baseUrl, '/') . '/activar-cuenta?token=' . urlencode($activationToken);
        $subject = '🎓 Bienvenido/a a la Plataforma ICFES FemTribe – Activa tu cuenta';

        $htmlBody = $this->buildActivationHtml($toName, $link);
        $textBody = $this->buildActivationText($toName, $link);

        return $this->send($toEmail, $toName, $subject, $htmlBody, $textBody);
    }

    /**
     * Envío genérico con SMTP nativo (socket).
     */
    private function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): bool {
        $host       = $this->config['host'];
        $port       = $this->config['port'];
        $encryption = $this->config['encryption'];
        $username   = $this->config['username'];
        $password   = $this->config['password'];
        $fromName   = $this->config['from_name'];
        $fromAddr   = $this->config['from_address'];

        $boundary = 'ICFES_' . md5(uniqid('', true));

        // Cabeceras MIME multipart
        $headers = implode("\r\n", [
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
            "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromAddr>",
            "Reply-To: $fromAddr",
            "X-Mailer: ICFES-PHP/1.0",
        ]);

        $message  = "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($textBody)) . "\r\n";
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $message .= "--$boundary--";

        $toHeader = "=?UTF-8?B?" . base64_encode($toName) . "?= <$toEmail>";

        // Intentar envío con mail() si no hay contraseña SMTP (modo dev/localhost)
        if (empty($password)) {
            return @mail($toEmail, $subject, $message, $headers . "\r\nTo: $toHeader");
        }

        // Envío real por SMTP con socket (SSL/TLS)
        try {
            $socketHost = ($encryption === 'ssl') ? "ssl://$host" : $host;
            $conn = @fsockopen($socketHost, $port, $errno, $errstr, 15);

            if (!$conn) {
                error_log("[EmailService] fsockopen error $errno: $errstr");
                return false;
            }

            $read = function() use ($conn) {
                return fgets($conn, 512);
            };
            $write = function(string $cmd) use ($conn) {
                fputs($conn, "$cmd\r\n");
            };

            $read(); // 220 banner

            $write("EHLO " . gethostname());
            while ($line = $read()) {
                if ($line[3] === ' ') break; // fin de respuesta EHLO
            }

            // STARTTLS solo si encryption = tls
            if ($encryption === 'tls') {
                $write("STARTTLS");
                $read();
                stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $write("EHLO " . gethostname());
                while ($line = $read()) {
                    if ($line[3] === ' ') break;
                }
            }

            $write("AUTH LOGIN");
            $read();
            $write(base64_encode($username));
            $read();
            $write(base64_encode($password));
            $authResp = $read();

            if (strpos($authResp, '235') === false) {
                error_log("[EmailService] AUTH failed: $authResp");
                fclose($conn);
                return false;
            }

            $write("MAIL FROM:<$fromAddr>");
            $read();
            $write("RCPT TO:<$toEmail>");
            $read();
            $write("DATA");
            $read();

            $rawMessage  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromAddr>\r\n";
            $rawMessage .= "To: $toHeader\r\n";
            $rawMessage .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $rawMessage .= $headers . "\r\n\r\n";
            $rawMessage .= $message;
            $write($rawMessage . "\r\n.");
            $sendResp = $read();

            $write("QUIT");
            fclose($conn);

            if (strpos($sendResp, '250') === false) {
                error_log("[EmailService] Send failed: $sendResp");
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            error_log("[EmailService] Exception: " . $e->getMessage());
            return false;
        }
    }

    // ─── PLANTILLAS ────────────────────────────────────────────────────────────

    private function buildActivationHtml(string $name, string $link): string {
        $firstName = explode(' ', trim($name))[0];
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activa tu cuenta – Plataforma ICFES</title>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;padding:40px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

        <!-- Encabezado -->
        <tr>
          <td style="background:linear-gradient(135deg,#4F46E5 0%,#7C3AED 100%);padding:40px 40px 32px;text-align:center;">
            <div style="font-size:48px;margin-bottom:8px;">🎓</div>
            <h1 style="margin:0;color:#fff;font-size:26px;font-weight:700;letter-spacing:-0.5px;">Plataforma ICFES FemTribe</h1>
            <p style="margin:8px 0 0;color:rgba(255,255,255,.8);font-size:14px;">Preparación Académica Saber 11°</p>
          </td>
        </tr>

        <!-- Cuerpo -->
        <tr>
          <td style="padding:40px;">
            <h2 style="margin:0 0 16px;color:#0F172A;font-size:22px;font-weight:700;">¡Hola, {$firstName}! 👋</h2>
            <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.7;">
              Te han inscrito en la <strong>Plataforma de Preparación ICFES FemTribe</strong>. Ya tienes acceso al curso, los simulacros y todos los materiales de estudio.
            </p>
            <p style="margin:0 0 28px;color:#475569;font-size:15px;line-height:1.7;">
              Para comenzar, haz clic en el botón de abajo para <strong>crear tu contraseña</strong> y activar tu cuenta:
            </p>

            <!-- Botón CTA -->
            <table cellpadding="0" cellspacing="0" width="100%"><tr><td align="center" style="padding:8px 0 32px;">
              <a href="{$link}"
                 style="display:inline-block;background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;text-decoration:none;font-size:16px;font-weight:700;padding:16px 40px;border-radius:50px;letter-spacing:.3px;box-shadow:0 4px 12px rgba(79,70,229,.35);">
                ✅ Activar mi cuenta y crear contraseña
              </a>
            </td></tr></table>

            <!-- Info del enlace -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
              <p style="margin:0 0 6px;color:#64748B;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">⏰ Este enlace expira en 48 horas</p>
              <p style="margin:0;word-break:break-all;color:#4F46E5;font-size:12px;">{$link}</p>
            </div>

            <p style="margin:0;color:#94A3B8;font-size:13px;line-height:1.6;">
              Si no esperabas este correo, puedes ignorarlo. Nadie más tendrá acceso a tu cuenta hasta que lo actives.<br>
              ¿Tienes dudas? Escríbenos a <a href="mailto:soporte@femtribe.com.co" style="color:#4F46E5;">soporte@femtribe.com.co</a>
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#F8FAFC;padding:20px 40px;text-align:center;border-top:1px solid #E2E8F0;">
            <p style="margin:0;color:#94A3B8;font-size:12px;">© 2026 FemTribe · Plataforma ICFES · Todos los derechos reservados</p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    private function buildActivationText(string $name, string $link): string {
        $firstName = explode(' ', trim($name))[0];
        return "Hola $firstName,\n\n"
            . "Te han inscrito en la Plataforma ICFES FemTribe.\n\n"
            . "Para activar tu cuenta y crear tu contraseña, visita:\n$link\n\n"
            . "Este enlace expira en 48 horas.\n\n"
            . "Si no esperabas este correo, ignóralo.\n\n"
            . "— Equipo FemTribe";
    }

    /**
     * Notificación cuando el acudiente envía una matrícula para revisión.
     */
    public function sendEnrollmentSubmittedEmail(
        string $toEmail,
        string $toName,
        string $enrollmentCode,
        string $studentName,
        string $grade
    ): bool {
        $subject = "📋 Solicitud de Matrícula Recibida ({$enrollmentCode}) - Jean Piaget School";
        $html = <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
            <div style="text-align: center; border-bottom: 2px solid #003366; padding-bottom: 12px; margin-bottom: 16px;">
                <h2 style="color: #003366; margin: 0;">INSTITUCIÓN EDUCATIVA JEAN PIAGET SCHOOL</h2>
                <p style="color: #15803d; font-weight: bold; margin: 4px 0;">Sistema de Matrícula Digital 2026</p>
            </div>
            <p>Estimado(a) <strong>{$toName}</strong>,</p>
            <p>Hemos recibido satisfactoriamente la solicitud de matrícula con código <strong>{$enrollmentCode}</strong> para el estudiante <strong>{$studentName}</strong>, aspirante al grado <strong>{$grade}</strong>.</p>
            <p>Los documentos oficiales (Formulario de Matrícula, Contrato de Servicios Educativos, Pagaré y Carta de Instrucciones) han sido generados y firmados electrónicamente bajo la Ley 527 de 1999.</p>
            <p>Nuestro equipo administrativo revisará la información en los próximos días hábiles. Podrá consultar el estado en cualquier momento ingresando con su usuario a la plataforma.</p>
            <div style="background-color: #f1f5f9; padding: 12px; border-radius: 6px; margin: 16px 0;">
                <p style="margin: 0; font-size: 13px; color: #334155;"><strong>Estado Actual:</strong> PENDIENTE DE REVISIÓN ADMINISTRATIVA</p>
            </div>
            <p style="font-size: 12px; color: #64748b; margin-top: 24px;">Girardot, Cundinamarca · Secretaría de Educación (Resolución No. 644 de 2015 / No. 1187 de 2019)</p>
        </div>
HTML;
        $text = "Hola $toName,\nHemos recibido la matrícula $enrollmentCode para el estudiante $studentName ($grade).\nPronto te notificaremos cuando sea aprobada.\n— Jean Piaget School";
        return $this->send($toEmail, $toName, $subject, $html, $text);
    }

    /**
     * Notificación cuando el administrador actualiza el estado de la matrícula.
     */
    public function sendEnrollmentStatusEmail(
        string $toEmail,
        string $toName,
        string $enrollmentCode,
        string $status,
        ?string $comments = null
    ): bool {
        $statusLabels = [
            'APPROVED' => ['label' => '¡MATRÍCULA APROBADA! 🎉', 'color' => '#15803d', 'msg' => 'Felicitaciones, la matrícula ha sido aprobada oficialmente por la administración de la institución. Bienvenido al Año Escolar 2026.'],
            'REJECTED' => ['label' => 'MATRÍCULA RECHAZADA', 'color' => '#dc2626', 'msg' => 'La solicitud de matrícula no ha sido admitida.'],
            'DOCUMENTS_PENDING' => ['label' => 'CORRECCIONES REQUERIDAS', 'color' => '#d97706', 'msg' => 'Se requieren correcciones o documentación adicional para continuar con el proceso.']
        ];

        $info = $statusLabels[$status] ?? ['label' => "ESTADO: $status", 'color' => '#003366', 'msg' => 'Ha habido una actualización en el estado de su matrícula.'];
        $subject = "Actualización de Matrícula ({$enrollmentCode}) - {$info['label']}";
        $obsHtml = $comments ? "<div style='background:#fffbeb;border:1px solid #fef3c7;padding:12px;border-radius:6px;margin:12px 0;'><strong style='color:#b45309;'>Observaciones:</strong><p style='margin:4px 0 0;color:#92400e;'>$comments</p></div>" : "";

        $html = <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
            <div style="text-align: center; border-bottom: 2px solid #003366; padding-bottom: 12px; margin-bottom: 16px;">
                <h2 style="color: #003366; margin: 0;">INSTITUCIÓN EDUCATIVA JEAN PIAGET SCHOOL</h2>
                <p style="color: #15803d; font-weight: bold; margin: 4px 0;">Sistema de Matrícula Digital 2026</p>
            </div>
            <p>Estimado(a) <strong>{$toName}</strong>,</p>
            <div style="border-left: 4px solid {$info['color']}; padding: 10px 16px; background: #f8fafc; margin: 16px 0;">
                <h3 style="color: {$info['color']}; margin: 0 0 6px;">{$info['label']}</h3>
                <p style="margin: 0; color: #334155;">{$info['msg']}</p>
            </div>
            {$obsHtml}
            <p>Código de matrícula: <strong>{$enrollmentCode}</strong></p>
            <p>Puede consultar sus documentos firmados y el estado detallado ingresando a la plataforma institucional.</p>
            <p style="font-size: 12px; color: #64748b; margin-top: 24px;">Institución Educativa Jean Piaget School · Girardot, Cundinamarca</p>
        </div>
HTML;
        $text = "Hola $toName,\nActualización de matrícula $enrollmentCode: {$info['label']}\n{$info['msg']}\n$comments\n— Jean Piaget School";
        return $this->send($toEmail, $toName, $subject, $html, $text);
    }
}
