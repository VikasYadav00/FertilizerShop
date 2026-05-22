<?php
// ============================================================
// config/mailer.php - PHPMailer SMTP Configuration
// Uses Gmail SMTP to send verification & reset emails
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Auto-load PHPMailer (install via composer OR manual include)
$phpmailerPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($phpmailerPath)) {
    require_once $phpmailerPath;
} else {
    // Manual include fallback (place PHPMailer files in /includes/PHPMailer/)
    require_once __DIR__ . '/../includes/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../includes/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../includes/PHPMailer/src/SMTP.php';
}

// ---- Gmail SMTP Credentials ----
// IMPORTANT: Use a Gmail App Password (NOT your real Gmail password)
// Steps: Google Account → Security → 2-Step Verification → App Passwords
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'vy4479365@gmail.com');   // <-- Replace with your Gmail
define('MAIL_PASSWORD', 'otga bmhv wswl emta'); // <-- Replace with Gmail App Password
define('MAIL_FROM',     'vy4479365@gmail.com');   // <-- Same as above
define('MAIL_FROM_NAME','IFFDC Maharajpur');

/**
 * Send an HTML email using PHPMailer
 * @param string $toEmail    Recipient email
 * @param string $toName     Recipient name
 * @param string $subject    Email subject
 * @param string $htmlBody   HTML content of email
 * @return array ['success' => bool, 'message' => string]
 */
function sendMail($toEmail, $toName, $subject, $htmlBody) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        // Recipients
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email could not be sent: ' . $mail->ErrorInfo];
    }
}

/**
 * Generate a verification email HTML body
 */
function getVerificationEmailHTML($name, $verifyLink) {
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden;'>
        <div style='background: #15803d; padding: 30px; text-align: center;'>
            <h1 style='color: white; margin: 0;'>IFFDC Maharajpur</h1>
            <p style='color: #dcfce7; margin: 5px 0 0;'>Krishak Seva Kendra</p>
        </div>
        <div style='padding: 30px;'>
            <h2 style='color: #14532d;'>Verify Your Email Address</h2>
            <p>Hello <strong>{$name}</strong>,</p>
            <p>Thank you for registering with IFFDC Maharajpur. Please click the button below to verify your email address and activate your account.</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$verifyLink}' style='background: #15803d; color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 1rem;'>
                    ✓ Verify My Email
                </a>
            </div>
            <p style='color: #666; font-size: 0.9rem;'>This link will expire in 24 hours. If you did not register, please ignore this email.</p>
        </div>
        <div style='background: #f9fafb; padding: 20px; text-align: center; color: #9ca3af; font-size: 0.8rem;'>
            © 2026 IFFDC Maharajpur. All rights reserved.
        </div>
    </div>";
}

/**
 * Generate a password reset email HTML body
 */
function getResetEmailHTML($name, $otp) {
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden;'>
        <div style='background: #15803d; padding: 30px; text-align: center;'>
            <h1 style='color: white; margin: 0;'>IFFDC Maharajpur</h1>
            <p style='color: #dcfce7; margin: 5px 0 0;'>Krishak Seva Kendra</p>
        </div>
        <div style='padding: 30px;'>
            <h2 style='color: #14532d;'>Password Reset OTP</h2>
            <p>Hello <strong>{$name}</strong>,</p>
            <p>You requested to reset your password. Use the OTP below to reset your password:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <div style='background: #dcfce7; border: 2px dashed #15803d; border-radius: 12px; padding: 20px; display: inline-block;'>
                    <h1 style='color: #14532d; font-size: 2.5rem; margin: 0; letter-spacing: 10px;'>{$otp}</h1>
                </div>
            </div>
            <p style='color: #666; font-size: 0.9rem;'>This OTP is valid for <strong>15 minutes</strong> only. If you did not request this, please ignore this email.</p>
        </div>
        <div style='background: #f9fafb; padding: 20px; text-align: center; color: #9ca3af; font-size: 0.8rem;'>
            © 2026 IFFDC Maharajpur. All rights reserved.
        </div>
    </div>";
}
?>
