<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

include '../includes/config.php';
function sendULSCEmail($ulsc_name, $email, $password) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'spoural025@gmail.com';
        $mail->Password = 'ribh uzqa gqwp bsnm';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Set sender & recipient
        $mail->setFrom('spoural025@gmail.com', 'College Admin');
        $mail->addAddress($email);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Your ULSC Account Details";
        $mail->Body = "
            <p>Hello $ulsc_name,</p>
            <p>Your ULSC account has been created.</p>
            <p><strong>Login Credentials:</strong><br>
            Email: $email<br>
            Password: $password</p>
            <p>Please change your password after logging in.</p>
            <p>Best regards,<br>College Admin</p>
        ";
        
        $mail->AltBody = "Hello $ulsc_name,\n\nYour ULSC account has been created.\n\nLogin Credentials:\nEmail: $email\nPassword: $password\n\nPlease change your password after logging in.\n\nBest regards,\nCollege Admin";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
