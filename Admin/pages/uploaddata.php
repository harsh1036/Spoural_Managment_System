<?php
session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login


// Fetch session data
$admin_username = $_SESSION['login'];




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
            <!-- <a href="../Excel/importstudent.php"> -->
            <div class="box">
                <div class="right-side">
                <a href="../Excel/importstudent.php"> 
                    <div class="box-topic">Upload Student Data</div>
                    </a>
                        <div class="indicator">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span class="text">Up to date</span>
                        </div>
                    </div>
                    <i class='bx bx-user-circle bx-tada cart one'></i>

                </div>
                <div class="box">
                    <div class="right-side">
                    <a href="../Excel/importevent.php">
                        <div class="box-topic">Upload Event Data</div></a>
                        <div class="indicator">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span class="text">Up to date</span>
                        </div>
                    </div>
                    <i class='bx bx-user-circle bx-tada cart one'></i>
                </div>
                <div class="box">
                    <div class="right-side">
                    <a href="../Excel/importdepartment.php">
                        <div class="box-topic">Upload Department Data</div></a>
                        
                        <div class="indicator">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span class="text">Up to date</span>
                        </div>
                    </div>
                    <i class='bx bx-user-circle bx-tada cart one'></i>
                </div>
        </section>
    </div>
    <?php
                        include_once('../includes/footer.php');
        ?>
</body>

</html>