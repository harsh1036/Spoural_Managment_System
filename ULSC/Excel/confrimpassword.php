<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$messageClass = '';

// Get token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
if (empty($token)) {
    die("Invalid reset link. Please request a new password reset.");
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];

    // Debug log
    error_log("POST data received: " . print_r($_POST, true));
    error_log("Attempting password reset for email: " . $email);

    // Password validation
    $uppercase = preg_match('@[A-Z]@', $new_password);
    $lowercase = preg_match('@[a-z]@', $new_password);
    $number    = preg_match('@[0-9]@', $new_password);
    $specialChars = preg_match('@[^\w]@', $new_password);

    if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($new_password) < 8) {
        $message = 'Password should be at least 8 characters long and should include at least one uppercase letter, one lowercase letter, one number, and one special character.';
        $messageClass = 'error';
    }
    else if ($new_password !== $confirm_password) {
        $message = 'Passwords do not match. Please try again.';
        $messageClass = 'error';
    }
    else {
        try {
            // Database connection
            $db = new mysqli('localhost', 'root', '', 'spoural');

            if ($db->connect_error) {
                throw new Exception("Connection failed: " . $db->connect_error);
            }

            // Debug query to see all emails in the database
            $debug_query = "SELECT email FROM ulsc";
            $debug_result = $db->query($debug_query);
            error_log("All emails in database:");
            while ($row = $debug_result->fetch_assoc()) {
                error_log("DB Email: '" . $row['email'] . "'");
            }

            // If email exists, proceed with password update
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_sql = "UPDATE ulsc SET password = ? WHERE email = ?";
            error_log("Update SQL: " . $update_sql);
            
            $update_stmt = $db->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception("Prepare failed: " . $db->error);
            }

            $update_stmt->bind_param("ss", $hashed_password, $email);
            
            if ($update_stmt->execute()) {
                // Check if any rows were actually updated
                if ($update_stmt->affected_rows > 0) {
                    error_log("Password updated successfully for email: " . $email);
                    $message = 'Password updated successfully! You can now login with your new password.';
                    $messageClass = 'success';
                    // Redirect after 2 seconds
                    header("refresh:2;url=../index.php");
                } else {
                    error_log("No rows updated for email: " . $email);
                    throw new Exception("Failed to update password. Please try again.");
                }
            } else {
                error_log("Error executing update for email: " . $email . ". Error: " . $update_stmt->error);
                throw new Exception("Error updating password: " . $update_stmt->error);
            }

            $update_stmt->close();
            $db->close();

        } catch (Exception $e) {
            error_log("Error in password reset: " . $e->getMessage());
            $message = $e->getMessage();
            $messageClass = 'error';
        }
    }
}

// Get email from token (you should implement proper token validation)
// For now, we'll get it from the session or previous request
$email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Spoural</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2942a6 0%, #3b5ee3 100%);
            padding: 20px;
        }

        .container {
            width: 450px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 200px;
            height: auto;
        }

        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            height: 45px;
            padding: 10px 15px;
            font-size: 14px;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 6px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #2942a6;
            box-shadow: 0 0 8px rgba(41, 66, 166, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 38px;
            cursor: pointer;
            color: #666;
        }

        .submit-btn {
            width: 100%;
            height: 45px;
            background: #2942a6;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-btn:hover {
            background: #1a307c;
        }

        .message {
            margin-top: 20px;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
        }

        .message.error {
            background-color: #ffe5e5;
            color: #ff3333;
            border: 1px solid #ffcccc;
        }

        .message.success {
            background-color: #e5ffe5;
            color: #28a745;
            border: 1px solid #ccffcc;
        }

        .password-requirements {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .password-requirements h3 {
            color: #555;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .password-requirements li {
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .password-requirements i {
            color: #ccc;
        }

        .password-requirements li.valid i {
            color: #28a745;
        }

        .email-display {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .email-display strong {
            color: #2942a6;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="../assets/images/charusat.png" alt="CHARUSAT Logo">
        </div>

        <h2>Reset Your Password</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $messageClass; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="email-display">
            Resetting password for: <strong><?php echo htmlspecialchars($email); ?></strong>
        </div>

        <form action="" method="post" id="resetForm">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
                <i class='bx bx-hide password-toggle' onclick="togglePassword('new_password')"></i>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <i class='bx bx-hide password-toggle' onclick="togglePassword('confirm_password')"></i>
            </div>

            <button type="submit" class="submit-btn">
                <i class='bx bx-lock-alt'></i>
                Update Password
            </button>

            <div class="password-requirements">
                <h3>Password Requirements:</h3>
                <ul>
                    <li id="length"><i class='bx bx-x'></i>At least 8 characters long</li>
                    <li id="uppercase"><i class='bx bx-x'></i>Contains uppercase letter</li>
                    <li id="lowercase"><i class='bx bx-x'></i>Contains lowercase letter</li>
                    <li id="number"><i class='bx bx-x'></i>Contains number</li>
                    <li id="special"><i class='bx bx-x'></i>Contains special character</li>
                    <li id="match"><i class='bx bx-x'></i>Passwords match</li>
                </ul>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bx-hide');
                icon.classList.add('bx-show');
            } else {
                input.type = 'password';
                icon.classList.remove('bx-show');
                icon.classList.add('bx-hide');
            }
        }

        // Real-time password validation
        document.getElementById('new_password').addEventListener('input', validatePassword);
        document.getElementById('confirm_password').addEventListener('input', validatePassword);

        function validatePassword() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Update requirements list
            document.getElementById('length').innerHTML = 
                `<i class='bx bx-${password.length >= 8 ? 'check' : 'x'}'></i>At least 8 characters long`;
            
            document.getElementById('uppercase').innerHTML = 
                `<i class='bx bx-${/[A-Z]/.test(password) ? 'check' : 'x'}'></i>Contains uppercase letter`;
            
            document.getElementById('lowercase').innerHTML = 
                `<i class='bx bx-${/[a-z]/.test(password) ? 'check' : 'x'}'></i>Contains lowercase letter`;
            
            document.getElementById('number').innerHTML = 
                `<i class='bx bx-${/[0-9]/.test(password) ? 'check' : 'x'}'></i>Contains number`;
            
            document.getElementById('special').innerHTML = 
                `<i class='bx bx-${/[^\w]/.test(password) ? 'check' : 'x'}'></i>Contains special character`;
            
            document.getElementById('match').innerHTML = 
                `<i class='bx bx-${password === confirmPassword && password !== '' ? 'check' : 'x'}'></i>Passwords match`;

            // Add valid class for styling
            document.querySelectorAll('.password-requirements li').forEach(li => {
                if (li.querySelector('.bx-check')) {
                    li.classList.add('valid');
                } else {
                    li.classList.remove('valid');
                }
            });
        }
    </script>
</body>
</html>
