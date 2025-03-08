<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

include '../includes/config.php';
function sendULSCEmail($ulsc_name, $ulsc_id, $random_password) {
    $email = trim($ulsc_id) . "@charusat.edu.in"; 
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Use your SMTP host
        $mail->SMTPAuth = true;
        $mail->Username = 'spoural025@gmail.com'; // Your email
        $mail->Password = 'ribh uzqa gqwp bsnm'; // Your email password or app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port= 587;

        // Set sender & recipient
        $mail->setFrom('spoural025@gmail.com', 'College Admin');
        $mail->addAddress($email);

        // Email content
        $mail->isHTML(false);
        $mail->Subject = "Your ULSC Account Details";
        $mail->Body = "Hello $ulsc_name,\n\nYour ULSC account has been created.\n\nLogin Credentials:\nEmail: $email\nPassword: $random_password\n\nPlease change your password after logging in.";
        echo "Sending email to: " . $email . "<br>";

        $mail->send();
        echo "Email sent successfully!";
        return true;
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo; // Show detailed error
        return false;
    }
}
?>
