<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Spoural</title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
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
            max-width: 450px;
            width: 100%;
            background: #fff;
            padding: 40px 30px;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }

        .container header {
            text-align: center;
            margin-bottom: 30px;
        }

        .container header i {
            font-size: 50px;
            color: #2942a6;
            margin-bottom: 15px;
        }

        .container header h3 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .container header p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            height: 45px;
            padding: 0 15px;
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

        .submit-btn {
            width: 100%;
            height: 45px;
            background: #2942a6;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #1a307c;
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: #2942a6;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }

        @media screen and (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            .container header h3 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <i class="fas fa-lock"></i>
            <h3>Reset Your Password</h3>
            <p>Enter your email address and we'll send you instructions to reset your password.</p>
        </header>

        <form action="requestreset.php" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       placeholder="Enter your email address" 
                       required>
            </div>

            <button type="submit" name="sendpassword" class="submit-btn">
                <i class="fas fa-paper-plane"></i> Send Reset Instructions
            </button>

            <div class="back-to-login">
                <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>



