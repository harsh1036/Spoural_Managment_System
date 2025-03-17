
<?php

// Fetch session data
$admin_username = $_SESSION['login'];


?>

<link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="sidebar">
        <div class="logo-details">
            <i class='bx bxl-c-plus-plus'></i>
            <span class="logo_name">Spoural</span>
        </div>
        <ul class="nav-links">
            <li>
                <a href="admindashboard.php" class="active">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="addevent.php">
                    <i class='bx bx-box'></i>
                    <span class="links_name">ADD Event</span>
                </a>
            </li>
            <li>
                <a href="addulsc.php">
                    <i class='bx bx-box'></i>
                    <span class="links_name">ADD ULSC</span>
                </a>
            </li>
       
            <li>
                <a href="addadmin.php">
                <i class='bx bx-box'></i>
                    <span class="links_name">ADD ADMIN</span>
                </a>
            </li>
            <li>
                <a href="schedule_matches.php">
                <i class='bx bx-box'></i>
                    <span class="links_name">ADD SCHEDULE</span>
                </a>
            </li>

        </ul>
    </div>

    <section class="home-section">
        <nav>
            <div class="sidebar-button">
                <i class='bx bx-menu sidebarBtn'></i>
                
            </div>
            <div class="logo">
                <br><br>
                <img src="../assets/images/charusat.png" alt="Logo 1">
            </div>
            <h1>Spoural Event Management System</h1>

            <div class="logo">
                <img src="../assets/images/ulsc.png" alt="Logo 2">
            </div>

            <div class="profile-details">
                <img src="https://t4.ftcdn.net/jpg/00/97/00/09/360_F_97000908_wwH2goIihwrMoeV9QF3BW6HtpsVFaNVM.jpg"
                    alt="profile">
                <span class="admin_name">   <?php echo htmlspecialchars($admin_username); ?>  </span>
            </div>
        </nav>
        <?php
                        include_once('../includes/footer.php');
        ?>