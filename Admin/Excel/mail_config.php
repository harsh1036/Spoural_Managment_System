
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer();
try {
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth = true;
    $mail->Port = 587;
    $mail->Host = 'smtp.gmail.com';
    $mail->Username = 'visionshop02062@gmail.com'; // enter your mail address
    $mail->Password = 'xjkxebfavwikhleq';   // enter your email password
  $mail->setFrom('visionshop02062@gmail.com', 'Vision Shop');           // Set sender of the mail

    // $mail->Port       = 587;
} catch (Exception $e) {
    $e->getMessage();
}
?> 