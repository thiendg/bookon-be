<?php
// Simple autoloader for PHPMailer
spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base_dir = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email using PHPMailer.
 *
 * @param string $toEmail Recipient's email address.
 * @param string $subject Email subject.
 * @param string $bodyHtml HTML content of the email.
 * @param string $bodyText Plain text content of the email.
 * @return bool True on success, false on failure.
 */
function sendEmail(string $toEmail, string $subject, string $bodyHtml, string $bodyText): bool|string
{
    // Load environment variables if not already loaded
    if (!function_exists('loadEnv')) {
        require_once __DIR__ . '/env-loader.php';
        loadEnv(__DIR__ . '/../.env');
    }

    $mail = new PHPMailer(true); // Passing true enables exceptions

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.example.com';
        $mail->SMTPAuth   = filter_var($_ENV['MAIL_SMTP_AUTH'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? 'user@example.com';
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? 'secret';
        $mail->SMTPSecure = $_ENV['MAIL_SMTP_SECURE'] ?? PHPMailer::ENCRYPTION_STARTTLS; // Use PHPMailer::ENCRYPTION_SMTPS for ssl
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;

        // Recipients
        $mail->setFrom($_ENV['MAIL_FROM_EMAIL'] ?? 'no-reply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'BookOn Support');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyText;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error and return the error message for debugging
        $errorMessage = "Message could not be sent. Mailer Error: {$mail->ErrorInfo} | Exception: {$e->getMessage()}";
        error_log($errorMessage);
        return $errorMessage; // Return error message for debugging
    }
}
