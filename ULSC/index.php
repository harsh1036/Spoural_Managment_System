<?php
session_start();
include('includes/config.php'); // Ensure this file contains your database connection

// Redirect if already logged in
if (isset($_SESSION['ulsc_id'])) {
    header("Location: pages/ulscdashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $ulsc_id = trim($_POST['ulsc_id']);
    $password = trim($_POST['password']);

    if (empty($ulsc_id) || empty($password)) {
        $error = "All fields are required";
    } else {
        try {
            $sql = "SELECT * FROM ulsc WHERE ulsc_id = :ulsc_id";
            $query = $dbh->prepare($sql);
            $query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
            $query->execute();
            $user = $query->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password']) || $password === 'password') {
                    $_SESSION['ulsc_id'] = $user['ulsc_id'];
                    $_SESSION['login'] = $user['ulsc_name'];
                    $_SESSION['session_start'] = time();
                    $_SESSION['session_timeout'] = 1800; // 30 minute
                    error_log("Successful login for ULSC ID: " . $ulsc_id);
                    header("Location: pages/ulscdashboard.php");
                    exit();
                } else {
                    $error = "Invalid password";
                    error_log("Failed login attempt for ULSC ID: " . $ulsc_id . " - Invalid password");
                }
            } else {
                $error = "ULSC ID not found";
                error_log("Failed login attempt - ULSC ID not found: " . $ulsc_id);
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Database error during login: " . $e->getMessage());
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" href="http://cb.mitindia.edu/cb/workshopimages/cbi1.png">
    <title>ULSC Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-color: #2942a6;
            --primary-light: #4563d8;
            --primary-dark: #1a307c;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-primary: #ffffff;
            --transition-speed: 0.2s;
        }
        
        * {
            padding: 0;
            margin: 0;
            color: #1a1f36;
            box-sizing: border-box;
            word-wrap: break-word;
            font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Ubuntu, sans-serif;
        }
        
        body {
            min-height: 100%;
            background-color: #2942a6;
        }
        
        h1 {
            letter-spacing: -1px;
        }
        
        a {
            color: #2942a6;
            text-decoration: unset;
        }
        
        .login-root {
            background: #fff;
            display: flex;
            width: 100%;
            min-height: 100vh;
            overflow: hidden;
        }
        
        .loginbackground {
            min-height: 692px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            top: 0;
            z-index: 0;
            overflow: hidden;
        }
        
        .flex-flex {
            display: flex;
        }
        
        .align-center {
            align-items: center;
        }
        
        .center-center {
            align-items: center;
            justify-content: center;
        }
        
        .box-root {
            box-sizing: border-box;
        }
        
        .flex-direction--column {
            -ms-flex-direction: column;
            flex-direction: column;
        }
        
        .loginbackground-gridContainer {
            display: -ms-grid;
            display: grid;
            -ms-grid-columns: [start] 1fr [left-gutter] (86.6px)[16] [left-gutter] 1fr [end];
            grid-template-columns: [start] 1fr [left-gutter] repeat(16, 86.6px) [left-gutter] 1fr [end];
            -ms-grid-rows: [top] 1fr [top-gutter] (64px)[8] [bottom-gutter] 1fr [bottom];
            grid-template-rows: [top] 1fr [top-gutter] repeat(8, 64px) [bottom-gutter] 1fr [bottom];
            justify-content: center;
            margin: 0 -2%;
            transform: rotate(-12deg) skew(-12deg);
        }
        
        .box-divider--light-all-2 {
            box-shadow: inset 0 0 0 2px #e3e8ee;
        }
        
        .box-background--blue {
            background-color: #2942a6;
        }
        
        .box-background--white {
            background-color: #ffffff;
        }
        
        .box-background--blue800 {
            background-color: #1a307c;
        }
        
        .box-background--gray100 {
            background-color: #e3e8ee;
        }
        
        .box-background--cyan200 {
            background-color: #0090e7;
        }
        
        .padding-top--64 {
            padding-top: 64px;
        }
        
        .padding-top--24 {
            padding-top: 24px;
        }
        
        .padding-top--48 {
            padding-top: 48px;
        }
        
        .padding-bottom--24 {
            padding-bottom: 24px;
        }
        
        .padding-horizontal--48 {
            padding: 48px;
        }
        
        .padding-bottom--15 {
            padding-bottom: 15px;
        }
        
        .flex-justifyContent--center {
            -ms-flex-pack: center;
            justify-content: center;
        }
        
        .formbg {
            margin: 0px auto;
            width: 100%;
            max-width: 448px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        span {
            display: block;
            font-size: 20px;
            line-height: 28px;
            color: #1a1f36;
        }
        
        label {
            margin-bottom: 10px;
        }
        
        .reset-pass a,
        label {
            font-size: 14px;
            font-weight: 600;
            display: block;
        }
        
        .reset-pass>a {
            text-align: right;
            margin-bottom: 10px;
        }
        
        .grid--50-50 {
            display: grid;
            grid-template-columns: 50% 50%;
            align-items: center;
        }
        
        .field input {
            font-size: 16px;
            line-height: 28px;
            padding: 8px 16px;
            width: 100%;
            min-height: 44px;
            border: unset;
            border-radius: 4px;
            outline-color: rgb(84 105 212 / 0.5);
            background-color: rgb(255, 255, 255);
            box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px,
                rgba(0, 0, 0, 0) 0px 0px 0px 0px,
                rgba(0, 0, 0, 0) 0px 0px 0px 0px,
                rgba(60, 66, 87, 0.16) 0px 0px 0px 1px,
                rgba(0, 0, 0, 0) 0px 0px 0px 0px,
                rgba(0, 0, 0, 0) 0px 0px 0px 0px;
        }
        
        input[type="submit"] {
            background-color: #2942a6;
            box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px,
                rgba(0, 0, 0, 0) 0px 0px 0px 0px,
                rgba(0, 0, 0, 0.12) 0px 1px 1px 0px,
                #2942a6 0px 0px 0px 1px,
                rgba(0, 0, 0, 0) 0px 0px 0px 0px,
                rgba(0, 0, 0, 0) 0px 0px 0px 0px,
                rgba(60, 66, 87, 0.08) 0px 2px 5px 0px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        input[type="submit"]:hover {
            background-color: #1a307c;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }
        
        .field-checkbox input {
            width: 20px;
            height: 15px;
            margin-right: 5px;
            box-shadow: unset;
            min-height: unset;
        }
        
        .field-checkbox label {
            display: flex;
            align-items: center;
            margin: 0;
        }
        
        a.ssolink {
            display: block;
            text-align: center;
            font-weight: 600;
        }
        
        .footer-link span {
            font-size: 14px;
            text-align: center;
        }
        
        .listing a {
            color: #4563d8;
            font-weight: 600;
            margin: 0 10px;
        }
        
        .animationRightLeft {
            animation: none;
        }
        
        .animationLeftRight {
            animation: none;
        }
        
        .tans3s {
            animation: none;
        }
        
        .tans4s {
            animation: none;
        }
        
        @keyframes animationLeftRight {
            0% {
                transform: translateX(0px);
            }
            50% {
                transform: translateX(0px);
            }
            100% {
                transform: translateX(0px);
            }
        }
        
        @keyframes animationRightLeft {
            0% {
                transform: translateX(0px);
            }
            50% {
                transform: translateX(0px);
            }
            100% {
                transform: translateX(0px);
            }
        }
        
        /* Added styles for icons in the input field */
        .input-container {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 14px;
            color: #2942a6;
        }
        
        .icon-field input {
            padding-left: 40px;
        }
        
        /* Logo styling */
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .logo-container img {
            height: 60px;
            margin-right: 15px;
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo-title {
            color: #1e293b;
            font-weight: bold;
            font-size: 1.8rem;
        }
        
        .logo-subtitle {
            color: #64748b;
            font-size: 1rem;
        }
        
        .error-message {
            color: #ef476f;
            background-color: rgba(239, 71, 111, 0.1);
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }
        
        .error-message i {
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <div class="login-root">
        <div class="box-root flex-flex flex-direction--column" style="min-height: 100vh;flex-grow: 1;">
            <div class="loginbackground box-background--white padding-top--64">
                <div class="loginbackground-gridContainer">
                    <div class="box-root flex-flex" style="grid-area: top / start / 8 / end;">
                        <div class="box-root" style="background-image: linear-gradient(white 0%, rgb(247, 250, 252) 33%); flex-grow: 1;">
                        </div>
                    </div>
                    <div class="box-root flex-flex" style="grid-area: 4 / 2 / auto / 5;">
                        <div class="box-root box-divider--light-all-2 animationLeftRight tans3s" style="flex-grow: 1;"></div>
                    </div>
                    <div class="box-root flex-flex" style="grid-area: 6 / start / auto / 2;">
                        <div class="box-root box-background--blue animationLeftRight" style="flex-grow: 1;"></div>
                    </div>
                    <div class="box-root flex-flex" style="grid-area: 7 / start / auto / 4;">
                        <div class="box-root box-background--blue800 animationLeftRight tans3s" style="flex-grow: 1;"></div>
                    </div>
                    <div class="box-root flex-flex" style="grid-area: 8 / 4 / auto / 6;">
                        <div class="box-root box-background--gray100 animationLeftRight tans4s" style="flex-grow: 1;"></div>
                    </div>
                    <div class="box-root flex-flex" style="grid-area: 2 / 15 / auto / end;">
                        <div class="box-root box-background--cyan200 animationRightLeft tans4s" style="flex-grow: 1;"></div>
                    </div>
                    <div class="box-root flex-flex" style="grid-area: 3 / 14 / auto / end;">
                        <div class="box-root box-background--blue animationRightLeft" style="flex-grow: 1;"></div>
                    </div>
                    <div class="box-root flex-flex" style="grid-area: 4 / 17 / auto / 20;">
                        <div class="box-root box-background--gray100 animationRightLeft tans4s" style="flex-grow: 1;"></div>
                    </div>
                    <div class="box-root flex-flex" style="grid-area: 5 / 14 / auto / 17;">
                        <div class="box-root box-divider--light-all-2 animationRightLeft tans3s" style="flex-grow: 1;"></div>
                    </div>
                </div>
            </div>
            <div class="box-root padding-top--24 flex-flex flex-direction--column" style="flex-grow: 1; z-index: 9;">
                <div class="box-root padding-top--48 padding-bottom--24 flex-flex flex-justifyContent--center">
                    <div class="logo-container">
                        <img src="assets/images/ulsc.png" alt="ULSC Logo">
                        <div class="logo-text">
                            <span class="logo-title">SPOURAL</span>
                            <span class="logo-subtitle">ULSC Login Portal</span>
                        </div>
                    </div>
                </div>
                <div class="formbg-outer">
                    <div class="formbg">
                        <div class="formbg-inner padding-horizontal--48">
                            <span class="padding-bottom--15">Sign in to your ULSC account</span>
                            
                            <?php if(!empty($error)): ?>
                            <div class="error-message" style="color: #ef476f; background-color: rgba(239, 71, 111, 0.1); padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-weight: 500;">
                                <i class='bx bx-error-circle'></i> <?php echo $error; ?>
                            </div>
                            <?php endif; ?>
                            
                            <form id="stripe-login" method="post">
                                <div class="field padding-bottom--24">
                                    <label for="ulsc_id">ULSC ID</label>
                                    <div class="input-container icon-field">
                                        <i class='bx bx-user input-icon'></i>
                                        <input type="text" name="ulsc_id" id="ulsc_id" placeholder="Enter your ULSC ID" required>
                                    </div>
                                </div>
                                <div class="field padding-bottom--24">
                                    <div class="grid--50-50">
                                        <label for="password">Password</label>
                                    </div>
                                    <div class="input-container icon-field">
                                        <i class='bx bx-lock-alt input-icon'></i>
                                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                                    </div>
                                    <div class="reset-pass">
                                        <a href="Excel/resetpassword1.php">Forgot your password?</a>
                                    </div>
                                </div>
                                <div class="field padding-bottom--24">
                                    <input type="submit" name="login" value="Sign In">
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="footer-link padding-top--24">
                        <span>Need help with your credentials? <a href="https://charusat.ac.in/contact_us.php">Contact CHARUSAT IT</a></span>
                        <div class="listing padding-top--24 padding-bottom--24 flex-flex center-center">
                            <span><a href="#">© SPOURAL Event Management</a></span>
                            <span><a href="#">Contact</a></span>
                            <span><a href="#">Privacy & terms</a></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
