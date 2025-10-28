
<?php
// app/core/Mailer.php
// Clase para enviar correos usando PHPMailer (Gmail SMTP) o mail() de PHP
class Mailer
{
    public static function send($to, $subject, $message, $headers = '')
    {
        // Leer configuración desde config/config.php si está definida
        $useSMTP = defined('SMTP_USE') ? SMTP_USE : true;
        $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $smtpUsername = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $smtpPassword = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $smtpSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
        $smtpDebug = defined('SMTP_DEBUG') ? SMTP_DEBUG : 0;
        $mailFrom = defined('MAIL_FROM') ? MAIL_FROM : ($smtpUsername ?: 'no-reply@cruzmotorsac.com');
        $mailFromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Sistema CruzMotorSAC';

        if ($useSMTP && !empty($smtpHost) && !empty($smtpUsername) && !empty($smtpPassword)) {
            require_once __DIR__ . '/../../libs/phpmailer/PHPMailer-master/src/PHPMailer.php';
            require_once __DIR__ . '/../../libs/phpmailer/PHPMailer-master/src/SMTP.php';
            require_once __DIR__ . '/../../libs/phpmailer/PHPMailer-master/src/Exception.php';
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->SMTPDebug = (int)$smtpDebug;
                $mail->Debugoutput = 'html';
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUsername;
                $mail->Password = $smtpPassword;
                $mail->SMTPSecure = $smtpSecure;
                $mail->Port = $smtpPort;
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($mailFrom, $mailFromName);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->AltBody = strip_tags($message);
                $mail->send();
                return true;
            } catch (\Exception $e) {
                // Registrar error y continuar con fallback mail()
                if (class_exists('Logger')) {
                    Logger::error('Error PHPMailer: ' . $e->getMessage());
                }
            }
        }

        // Fallback: mail() clásico
        $defaultHeaders = "MIME-Version: 1.0\r\n" .
            "Content-type: text/html; charset=UTF-8\r\n" .
            "From: " . $mailFromName . " <" . $mailFrom . ">\r\n";
        $headers = $defaultHeaders . $headers;
        return mail($to, $subject, $message, $headers);
    }
}
