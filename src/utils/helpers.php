<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only load autoload if it exists (after composer install)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}
function generateOTP(int $length = 6): string {
    $characters = '0123456789';
    $otp = '';

    for ($i = 0; $i < $length; $i++) {
        $otp .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $otp;
}


function sendMail(string $to, string $subject, string $message): bool {
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer not installed. Run: composer require phpmailer/phpmailer");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = Config::get('MAIL_USERNAME');       // ⚠️ CHANGE THIS
        $mail->Password = Config::get('MAIL_PASS');   // ⚠️ CHANGE THIS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email Settings
        $mail->setFrom(Config::get('MAIL_USERNAME'), 'WorkHub');  // ⚠️ CHANGE THIS
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        error_log(" Email sent successfully to: $to");
        return true;
        
    } catch (Exception $e) {
        error_log(" Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
