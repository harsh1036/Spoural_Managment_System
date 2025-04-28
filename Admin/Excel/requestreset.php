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
    
    // Check if the email ends with either charusat.edu.in or charusat.ac.in
    if (!preg_match('/@(charusat\.edu\.in|charusat\.ac\.in)$/', $email)) {
        echo '<script>
            alert("Please use your Charusat email address (ending with @charusat.edu.in or @charusat.ac.in)");
            window.location.href = "resetpassword1.php";
        </script>';
        exit;
    }

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
        $mail->setFrom('spoural025@gmail.com', 'Spoural Admin');
        $mail->addAddress($email);  // Add a recipient
        $mail->addReplyTo('no-reply@gmail.com', 'No Reply');

        // Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'Password Reset Request - Spoural Management System';

        // Create a clickable link
        $link = 'http://localhost/Spoural_Managment_System/Admin/Excel/confrimpassword.php';

        // Generate a unique reset token (you might want to store this in your database)
        $reset_token = bin2hex(random_bytes(32));

        // Set the email body with a more professional and unique design
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <img src="https://charusat.ac.in/images/logo.png" alt="CHARUSAT Logo" style="max-width: 200px;">
                </div>
                
                <div style="background: linear-gradient(135deg, #2942a6 0%, #3b5ee3 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="margin: 0; font-size: 24px;">Password Reset Request</h1>
                    <p style="margin: 10px 0 0;">Spoural Management System</p>
                </div>

                <div style="background-color: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 20px;">
                    <p style="margin-bottom: 15px; color: #333; font-size: 16px;">Dear ' . ucfirst($email_part) . ',</p>
                    
                    <p style="margin-bottom: 15px; color: #333; line-height: 1.6;">We received a request to reset the password for your Spoural Management System account. To proceed with the password reset, please click the button below:</p>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $link . '?token=' . $reset_token . '" 
                           style="background: #2942a6; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                            Reset Your Password
                        </a>
                    </div>
                    
                    <p style="margin-bottom: 15px; color: #666; font-size: 14px;">This link will expire in 24 hours for security reasons. If you did not request this password reset, please ignore this email or contact support if you have concerns.</p>
                    
                    <div style="background-color: #fff; padding: 15px; border-radius: 5px; margin-top: 20px;">
                        <p style="margin: 0; color: #666; font-size: 14px;">For security reasons:</p>
                        <ul style="color: #666; font-size: 14px; margin: 10px 0;">
                            <li>Never share your password with anyone</li>
                            <li>Create a strong, unique password</li>
                            <li>Enable two-factor authentication if available</li>
                        </ul>
                    </div>
                </div>

                <div style="border-top: 1px solid #ddd; padding-top: 20px; text-align: center; color: #666;">
                    <p style="margin-bottom: 10px; font-size: 14px;">This is an automated message, please do not reply.</p>
                    <p style="margin: 0; font-size: 12px;">
                        &copy; ' . date("Y") . ' Spoural Management System - CHARUSAT<br>
                        Charotar University of Science and Technology
                    </p>
                </div>
            </div>
        ';

        // Plain text version of the email
        $mail->AltBody = "
            Password Reset Request - Spoural Management System
            
            Dear " . ucfirst($email_part) . ",
            
            We received a request to reset the password for your Spoural Management System account.
            
            To reset your password, please visit the following link:
            " . $link . "?token=" . $reset_token . "
            
            This link will expire in 24 hours.
            
            If you did not request this password reset, please ignore this email.
            
            Best regards,
            Spoural Management System Team
            CHARUSAT
        ";

        $mail->send();

        echo '<script>alert("Password reset instructions have been sent to your email."); window.location.href = "../index.php";</script>';
    } catch (Exception $e) {
        echo '<script>
            alert("Message could not be sent. Please try again later.");
            window.location.href = "resetpassword1.php";
        </script>';
    }
}
?>
