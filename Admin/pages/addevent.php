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
    <title>Spoural Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css' rel='stylesheet'>
    
</head>

<body>
    <div class="home-content">
        <?php
        include_once('../includes/sidebar.php');
        ?>
        <br><br>
        <div class="home-content">
            <br><br>
            <div class="overview-boxes">
                <div class="box">

                    <div class="right-side">
                        <a href="addsingleevent.php">
                            <div class="box-topic">Single Event  </div>
                            <div class="number">

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
                        <a href="importevent.php">
                            <div class="box-topic">Upload Excel Sheets  </div>
                            <div class="number">

                            </div>
                            <div class="indicator">
                                <i class='bx bx-up-arrow-alt'></i>
                                <span class="text">Up to date</span>
                            </div>
                    </div>
                    <i class='bx  bxs-user-pin bx-flashing cart two'></i>
                </div>

                </section>
            </div>
            <?php
                        include_once('../includes/footer.php');
        ?>

</body>

</html>