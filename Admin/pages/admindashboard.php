<?php
session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login


// Fetch session data
$admin_username = $_SESSION['login'];


$total_events = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];
$total_ulsc = $conn->query("SELECT COUNT(*) AS total FROM ulsc")->fetch_assoc()['total'];
$total_admins = $conn->query("SELECT COUNT(*) AS total FROM admins")->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPORUAL Event Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <div class="home-content">
        <?php
        include_once('../includes/sidebar.php');
        ?>

<div class="home-content">
        <div class="overview-boxes">
            <div class="box">
                <div class="right-side">
                    <div class="box-topic">Total ULSC</div>
                    <div class="number">
                        <?php echo $total_ulsc ?>
                        </div>
                        <div class="indicator">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span class="text">Up to date</span>
                        </div>
                    </div>
                    <i class='bx bx-user-circle bx-tada cart one'></i>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Total Admin</div>
                        <div class="number">
                        <?php echo $total_admins ?>
                        </div>
                        <div class="indicator">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span class="text">Up to date</span>
                        </div>
                    </div>
                    <i class='bx  bxs-user-pin bx-flashing cart two'></i>
                </div>
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Total Events</div>
                        <div class="number">
                        <?php echo $total_events ?>
                        </div>
                        <div class="indicator">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span class="text">Up to date</span>
                        </div>
                    </div>
                    <i class='bx bxs-info-circle bx-spin cart three'></i>
                </div>
        </section>
    </div>
    <?php
                        include_once('../includes/footer.php');
        ?>
</body>

</html>