<?php
session_start();
include('includes/config.php');

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
    $sql = "SELECT * FROM admin WHERE username = :username AND password = :password";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['login'] = $user['username']; // Store username dynamically
        $_SESSION['admin_id'] = $user['id']; // Store admin ID
        echo "<script>window.location.href='pages/admindashboard.php';</script>";
        exit();
    } else {
            $error = "Invalid username or password";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" href="http://cb.mitindia.edu/cb/workshopimages/cbi1.png">
    <title>Spoural Event System - Admin Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-primary: #ffffff;
            --transition-speed: 0.2s;
        }
        
        * {
            margin: 0;
        padding: 0;
        box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    body {
            background-color: #6366f1;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-container {
        display: flex;
        width: 100%;
            max-width: 1280px;
            height: 100vh;
            background-color: #ffffff;
        }
        
        .login-left {
            flex: 1;
            position: relative;
            background: #8f8f8f url('assets/images/charusat.png') center/cover no-repeat;
        }
        
        .login-left::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .left-content {
            position: absolute;
            bottom: 80px;
            left: 60px;
            color: white;
            z-index: 10;
        }
        
        .left-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .left-content p {
            font-size: 1.1rem;
            max-width: 80%;
        }
        
        .login-right {
            flex: 1;
            display: flex;
        flex-direction: column;
        justify-content: center;
            padding: 60px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            margin-bottom: 50px;
        }
        
        .logo-container img {
            height: 50px;
            margin-right: 12px;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo-title {
            color: var(--text-primary);
            font-weight: bold;
            font-size: 1.4rem;
            letter-spacing: 0.5px;
        }
        
        .logo-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .login-form {
            max-width: 400px;
        }
        
        .login-form h2 {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .login-form p {
            color: var(--text-secondary);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .input-container {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 14px;
            color: var(--primary-color);
        }
        
        .form-control {
        width: 100%;
            padding: 16px 16px 16px 44px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all var(--transition-speed);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        .forgot-link {
        display: block;
        text-align: right;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 24px;
            font-size: 0.9rem;
    }

        .btn-login {
            display: flex;
        align-items: center;
            justify-content: center;
        width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
            transition: background-color var(--transition-speed);
        }
        
        .btn-login i {
            margin-left: 8px;
        }
        
        .btn-login:hover {
            background-color: var(--primary-dark);
        }
        
        .copyright {
            margin-top: 40px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        text-align: center;
    }

        .error-alert {
            background-color: #fef2f2;
            color: #b91c1c;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .error-alert i {
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                height: auto;
            }
            
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 40px 20px;
            }
            
            .login-form {
                max-width: 100%;
            }
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-left">
            <div class="left-content">
                <h2>Spoural Event System</h2>
                <p>Manage sports events, teams, and participants with ease</p>
                        </div>
                    </div>
        <div class="login-right">
            <div class="logo-container">
                <img src="assets/images/ulsc.png" alt="ULSC Logo">
                <div class="logo-text">
                    <span class="logo-title">SPOURAL</span>
                    <span class="logo-subtitle">Event Management System</span>
                        </div>
                    </div>
            
            <div class="login-form">
                <h2>Welcome Back</h2>
                <p>Sign in to continue to your dashboard</p>
                
                <?php if(isset($error)): ?>
                <div class="error-alert">
                    <i class='bx bx-error-circle'></i>
                    <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-container">
                            <i class='bx bx-user input-icon'></i>
                            <input type="text" id="username" name="username" class="form-control" placeholder="admin" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-container">
                            <i class='bx bx-lock-alt input-icon'></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <a href="#" class="forgot-link">Forgot password?</a>
                    
                    <button type="submit" name="login" class="btn-login">
                        Sign In <i class='bx bx-right-arrow-alt'></i>
                    </button>
                </form>
                
                <p class="copyright">© <?php echo date('Y'); ?> Spoural Event Management System. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>

</html>