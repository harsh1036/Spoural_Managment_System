<?php
session_start();

// Check if the form is submitted and the email field is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = $_POST['email'];

    // Hash the new password
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Database connection
        $db = new mysqli('localhost', 'root', '', 'spoural');

        // Check connection
        if ($db->connect_error) {
            die("Connection failed: " . $db->connect_error);
        }

        // Prepare the SQL query
        $stmt = $db->prepare("UPDATE admin SET password = ? WHERE username = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $db->error);
        }

        // Bind parameters and execute the statement
        $stmt->bind_param("ss", $hashed_password, $email);
        if ($stmt->execute()) {
            echo '<p id="success-message" style="color: green;">Password updated successfully!</p>';
            session_destroy();
        } else {
            echo '<p id="error-message" style="color: red;">Error updating password: ' . $stmt->error . '</p>';
        }

        $stmt->close();
        $db->close();
    } else {
        echo '<p id="error-message" style="color: red;">Passwords do not match. Please try again.</p>';
    }
} else {
    echo '<p id="error-message" style="color: red;">Invalid request. Please submit the form again.</p>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            width: 100%;
            height: 100vh;
        }

        .container {
            width: 400px;
            margin: 50px auto;
            padding: 15px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="password"] {
            width: 90%;
            height: 40px;
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
        }

        button[type="submit"] {
            width: 100%;
            height: 40px;
            background-color: #4CAF50;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #3e8e41;
        }

        #error-message {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }

        #success-message {
            color: green;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>New Password</h2>
        <form action="" method="post">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Update Password</button>
        </form>
    </div>
</body>

</html>
