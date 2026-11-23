<?php
// app/Services/MailService.php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    /**
     * Send an HTML email via SMTP
     */
    public static function send($toEmail, $toName, $subject, $htmlBody)
    {
        $mail = new PHPMailer(true);

        try {
            // ---- SMTP config (load from env/config, not hardcoded) ----
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER') ?: 'noreply@ngembamining.co.tz';
            $mail->Password   = getenv('SMTP_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)(getenv('SMTP_PORT') ?: 587);
            $mail->CharSet    = 'UTF-8';

            // ---- From / To ----
            $mail->setFrom(
                getenv('SMTP_FROM_EMAIL') ?: 'noreply@ngembamining.co.tz',
                getenv('SMTP_FROM_NAME') ?: 'Ngemba Mining'
            );
            $mail->addAddress($toEmail, $toName);

            // ---- Content ----
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('MailService error: ' . $mail->ErrorInfo);
            throw $e;
        }
    }
}