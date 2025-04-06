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

$dept_id = $ulsc['dept_id'];
$admin_username = $ulsc['ulsc_name'];

// Count total events
$eventQuery = $dbh->prepare("SELECT COUNT(*) as total FROM events");
$eventQuery->execute();
$total_events = $eventQuery->fetch(PDO::FETCH_ASSOC)['total'];

// Count total participants for this department
$participantQuery = $dbh->prepare("SELECT COUNT(*) as total FROM participants WHERE dept_id = :dept_id");
$participantQuery->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
$participantQuery->execute();
$total_part = $participantQuery->fetch(PDO::FETCH_ASSOC)['total'];

// Count registered events for this department
$registeredEventQuery = $dbh->prepare("
    SELECT COUNT(DISTINCT event_id) as total 
    FROM participants 
    WHERE dept_id = :dept_id
");
$registeredEventQuery->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
$registeredEventQuery->execute();
$registered_events = $registeredEventQuery->fetch(PDO::FETCH_ASSOC)['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ULSC Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>

    <div class="home-content">
        <div class="participant-entry-container">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <h2>Welcome, <?php echo htmlspecialchars($admin_username); ?>!</h2>
                <p>Manage your department's sports and cultural event entries through this dashboard.</p>
                <p class="dept-badge"><?php echo htmlspecialchars($ulsc['dept_name']); ?></p>
            </div>

            <!-- Stats Overview -->
            <div class="overview-boxes">
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Events Managed</div>
                        <div class="number"><?php echo $registered_events; ?></div>
                        <div class="indicator">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span class="text">Out of <?php echo $total_events; ?></span>
                        </div>
                    </div>
                    <i class='bx bx-calendar-event bx-tada cart one'></i>
                </div>
                
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Total Participants</div>
                        <div class="number"><?php echo $total_part; ?></div>
                        <div class="indicator">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span class="text">Department Total</span>
                        </div>
                    </div>
                    <i class='bx bx-group bx-tada cart three'></i>
                </div>
                
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Quick Actions</div>
                        <div class="quick-links">
                            <a href="addsportsevent.php" class="quick-link">
                                <i class='bx bx-football'></i> Add Sports Entry
                            </a>
                            <a href="addculturalevent.php" class="quick-link">
                                <i class='bx bx-music'></i> Add Cultural Entry
                            </a>
                        </div>
                    </div>
                    <i class='bx bx-extension bx-tada cart two'></i>
                </div>
            </div>

            <!-- Notifications -->
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-bell'></i> Notifications</h2>
                </div>
                <div class="notifications">
                    <ul>
                        <li><i class='bx bx-bell'></i> <?php echo $total_part; ?> students from your department have registered for events.</li>
                        <li><i class='bx bx-calendar-check'></i> Your department is participating in <?php echo $registered_events; ?> events.</li>
                        <li><i class='bx bx-info-circle'></i> Register participants by the deadline to ensure participation.</li>
                        <li><i class='bx bx-trophy'></i> View your entries to track your department's participation status.</li>
                    </ul>
                </div>
            </div>
            
            <!-- Recent Entries -->
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-list-ul'></i> Quick Access</h2>
                </div>
                <div class="quick-access-grid">
                    <a href="viewsportsevent.php" class="quick-access-card">
                        <i class='bx bx-football'></i>
                        <h3>View Sports Entries</h3>
                        <p>Check your registered sports participants</p>
                    </a>
                    <a href="viewculturalevent.php" class="quick-access-card">
                        <i class='bx bx-music'></i>
                        <h3>View Cultural Entries</h3>
                        <p>Check your registered cultural participants</p>
                    </a>
                    <a href="addsportsevent.php" class="quick-access-card">
                        <i class='bx bx-plus-circle'></i>
                        <h3>Add Sports Entry</h3>
                        <p>Register participants for sports events</p>
                    </a>
                    <a href="addculturalevent.php" class="quick-access-card">
                        <i class='bx bx-plus-circle'></i>
                        <h3>Add Cultural Entry</h3>
                        <p>Register participants for cultural events</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>