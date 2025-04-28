<?php
include('../includes/session_management.php');
include('../includes/config.php');

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ../index.php");
    exit;
}

// Check session timeout
if (isset($_SESSION['session_start']) && isset($_SESSION['session_timeout'])) {
    $current_time = time();
    $session_age = $current_time - $_SESSION['session_start'];
    
    if ($session_age > $_SESSION['session_timeout']) {
        // Session expired
        session_destroy();
        header("Location: ../index.php?error=session_expired");
        exit;
    }
    
    // Calculate remaining time
    $remaining_time = $_SESSION['session_timeout'] - $session_age;
} else {
    // Session variables not set, redirect to login
    session_destroy();
    header("Location: ../index.php?error=invalid_session");
    exit;
}

// Fetch ULSC details
$ulsc_id = $_SESSION['ulsc_id'];
$sql = "SELECT u.*, d.dept_name 
        FROM ulsc u 
        JOIN departments d ON u.dept_id = d.dept_id 
        WHERE u.ulsc_id = :ulsc_id";
$query = $dbh->prepare($sql);
$query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
$query->execute();
$ulsc = $query->fetch(PDO::FETCH_ASSOC);

if (!$ulsc) {
    // If ULSC not found, redirect to login
    session_destroy();
    header("Location: ../index.php?error=invalid_session");
    exit;
}

$admin_username = $ulsc['ulsc_name'];

// Process form submission
$msg = "";
$error = "";

if (isset($_POST['update_profile'])) {
    // Get input values
    $name = $_POST['ulsc_name'];
    $email = $_POST['email'];
    $phone = $_POST['contact'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone)) {
        $error = "Please fill all required fields";
    } else {
        // Start transaction
        $dbh->beginTransaction();
        
        try {
            // Update basic info
            $updateSql = "UPDATE ulsc SET 
                        ulsc_name = :ulsc_name,
                        email = :email,
                        contact = :contact
                        WHERE ulsc_id = :ulsc_id";
                        
            $updateQuery = $dbh->prepare($updateSql);
            $updateQuery->bindParam(':ulsc_name', $name, PDO::PARAM_STR);
            $updateQuery->bindParam(':email', $email, PDO::PARAM_STR);
            $updateQuery->bindParam(':contact', $phone, PDO::PARAM_STR);
            $updateQuery->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
            $updateQuery->execute();
            
            $dbh->commit();
            $msg = "Profile updated successfully";
            
            // Refresh user data
            $query->execute();
            $ulsc = $query->fetch(PDO::FETCH_ASSOC);
            $admin_username = $ulsc['ulsc_name'];
            
        } catch (PDOException $e) {
            $dbh->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ULSC Profile</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container {
            max-width: 700px;
            width: 100%;
            margin: 40px auto 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 0 20px 20px 20px;
        }
        
        .profile-header {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--box-shadow);
        }
        
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--primary-color);
        }
        
        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-info h2 {
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .profile-badge {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .profile-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
        }
        
        .profile-card:hover {
            box-shadow: var(--box-shadow-hover);
            transform: translateY(-5px);
        }
        
        .form-title {
            font-size: 18px;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(41, 66, 166, 0.1);
            outline: none;
        }
        
        .submit-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .alert-box {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: rgba(74, 222, 128, 0.2);
            color: #166534;
            border: 1px solid rgba(74, 222, 128, 0.5);
        }
        
        .alert-danger {
            background-color: rgba(239, 71, 111, 0.2);
            color: #991b1b;
            border: 1px solid rgba(239, 71, 111, 0.5);
        }
        @media (max-width: 900px) {
            .profile-container {
                max-width: 98vw;
                padding: 0 5px 20px 5px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>

    <div class="home-content">
        <div class="profile-container">
            <?php if (!empty($msg)): ?>
            <div class="alert-box alert-success">
                <i class='bx bx-check-circle'></i> <?php echo $msg; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert-box alert-danger">
                <i class='bx bx-error-circle'></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <div class="profile-header">
                <div class="profile-picture">
                    <img src="https://t4.ftcdn.net/jpg/00/97/00/09/360_F_97000908_wwH2goIihwrMoeV9QF3BW6HtpsVFaNVM.jpg" alt="Profile Picture">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($ulsc['ulsc_name']); ?></h2>
                    <p><?php echo htmlspecialchars($ulsc['email']); ?></p>
                    <span class="profile-badge"><?php echo htmlspecialchars($ulsc['dept_name']); ?></span>
                </div>
            </div>
            
            <div class="profile-card">
                <form method="POST" action="">
                    <h3 class="form-title">Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="ulsc_name">Name</label>
                            <input type="text" class="form-control" id="ulsc_name" name="ulsc_name" value="<?php echo htmlspecialchars($ulsc['ulsc_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($ulsc['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="contact">Phone Number</label>
                            <input type="text" class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($ulsc['contact']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="department">Department</label>
                            <input type="text" class="form-control" id="department" value="<?php echo htmlspecialchars($ulsc['dept_name']); ?>" readonly disabled>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn" name="update_profile">
                        <i class='bx bx-save'></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Session countdown timer
            let timeLeft = <?php echo $remaining_time; ?>;
            const countdownElement = document.getElementById('countdown');
            
            if (countdownElement) {
                function updateCountdown() {
                    timeLeft--;
                    
                    // Calculate minutes and seconds
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    
                    // Format time as MM:SS
                    countdownElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                    
                    if (timeLeft <= 10) {
                        countdownElement.style.color = '#ff0000';
                        countdownElement.style.fontWeight = 'bold';
                    }
                    
                    if (timeLeft <= 0) {
                        // Session expired, redirect to login
                        window.location.href = '../index.php?error=session_expired';
                    }
                }
                
                // Update countdown every second
                setInterval(updateCountdown, 1000);
            }
        });
    </script>

    <?php include_once('../includes/footer.php'); ?>
</body>

</html> 