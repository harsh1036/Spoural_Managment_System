<?php
session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch ULSC details
$ulsc_id = $_SESSION['ulsc_id'];
$query = $dbh->prepare("SELECT u.*, d.dept_name FROM ulsc u JOIN departments d ON u.dept_id = d.dept_id WHERE u.ulsc_id = :ulsc_id");
$query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
$query->execute();
$ulsc = $query->fetch(PDO::FETCH_ASSOC);

if (!$ulsc) {
    session_destroy();
    header("Location: ../index.php?error=invalid_session");
    exit();
}

$dept_id = $ulsc['dept_id'];
$ulsc_name = $ulsc['ulsc_name'];
$dept_name = $ulsc['dept_name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sports Events - Spoural</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>
    
    <div class="home-content">
        <div class="participant-entry-container">
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-football'></i> View Sports Events</h2>
                </div>
                
                <div class="quick-access-grid">
                    <a href="singleviewsports.php" class="quick-access-card">
                        <i class='bx bx-detail'></i>
                        <h3>View Single Event</h3>
                        <p>View participants for a specific sports event</p>
                    </a>
                    
                    <a href="allviewsports.php" class="quick-access-card">
                        <i class='bx bx-list-ul'></i>
                        <h3>View Multiple Events</h3>
                        <p>See all sports events and participants</p>
                    </a>
                </div>
            </div>
            
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-info-circle'></i> Sports Events Information</h2>
                </div>
                <div class="card-content">
                    <p>Use the options above to view your department's participation in various sports events. You can:</p>
                    <ul>
                        <li>View the list of students registered for a specific sports event</li>
                        <li>Manage captains and participants for each event</li>
                        <li>View all sports event registrations at once</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once('../includes/footer.php'); ?>
</body>

</html> 