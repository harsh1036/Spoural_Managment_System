<?php
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (isset($_POST["email"])) {
    $email = $_POST["email"];

    // Split the email address to get the part before the '@' symbol
    $email_part = explode('@', $email)[0];

    // Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->SMTPDebug = 0;  // Send using SMTP
        $mail->Host = 'smtp.gmail.com';  // Set the SMTP server to send through
        $mail->SMTPAuth = true;  // Enable SMTP authentication
        $mail->Username   = 'spoural025@gmail.com';                     //SMTP username
        $mail->Password   = 'ribh uzqa gqwp bsnm';    
        $mail->SMTPSecure = 'ssl';  // Enable implicit TLS encryption
        $mail->Port = 465;  // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        // Recipients
        $mail->setFrom('spoural025@gmail.com', 'College Admin');
        $mail->addAddress($email);  // Add a recipient
        $mail->addReplyTo('no-reply@gmail.com', 'No Reply');

        // Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'Forgot Password';

        // Create a clickable link
        $link = 'http://localhost/Spoural-Management/Admin/Excel/confrimpassword.php';

        // Set the email body
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
                <h2 style="background-color: #4CAF50; color: white; padding: 15px; text-align: center;">Reset Your Password</h2>
                <p>Hello ' . $email_part . ',</p>
                <p>We received a request to reset your password. Click the button below to reset your password:</p>
                <div style="text-align: center; margin: 20px;">
                    <a href="' . $link . '"
                       style="background-color: #4CAF50; color: white; padding: 15px 30px; text-decoration: none; font-size: 16px; border-radius: 5px;">
                        Reset Password
                    </a>
                </div>
                <p>If you did not request this, please ignore this email. Your password will remain unchanged.</p>
                <p style="font-size: 12px; color: #888;">This link will expire in 24 hours.</p>
                <hr>
                <p style="font-size: 14px; color: #888;">Thanks, <br>The Bus Ticket Booking Team</p>
            </div>
        ';
        $mail->AltBody = 'To reset your password, click on the following link: ' . $link;
        $mail->send();

        echo '<script>alert("Email is sent to the registered email."); window.location.href = "../index.php";</script>';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
