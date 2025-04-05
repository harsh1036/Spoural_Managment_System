<?php

// Fetch session data
$admin_username = $_SESSION['login'];

// Determine current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    echo "<script>window.location.href='../index.php';</script>";
    exit();
}

?>

<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="sidebar">
        <div class="logo-details">
            <i class='bx bxs-trophy'></i>
            <span class="logo_name">Spoural</span>
        </div>
        <ul class="nav-links">
            <li>
                <a href="admindashboard.php" class="<?php echo $current_page == 'admindashboard.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-dashboard'></i>
                    <span class="links_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="addadmin.php" class="<?php echo $current_page == 'addadmin.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-user-plus'></i>
                    <span class="links_name">ADD ADMIN</span>
                </a>
            </li>
            <li>
                <a href="addevent.php" class="<?php echo $current_page == 'addevent.php' || $current_page == 'addsingleevent.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-calendar-plus'></i>
                    <span class="links_name">ADD Event</span>
                </a>
            </li>
            <li>
                <a href="addulsc.php" class="<?php echo $current_page == 'addulsc.php' || $current_page == 'addsingleulsc.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-group'></i>
                    <span class="links_name">ADD ULSC</span>
                </a>
            </li>
            <li>
                <a href="addstudents.php" class="<?php echo $current_page == 'addstudents.php' || $current_page == 'addsinglestudent.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-user-detail'></i>
                    <span class="links_name">ADD STUDENT</span>
                </a>
            </li>
            <li>
                <a href="adddepartment.php" class="<?php echo $current_page == 'adddepartment.php' || $current_page == 'addsingledepartment.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-building'></i>
                    <span class="links_name">ADD DEPARTMENT</span>
                </a>
            </li>

        </ul>
    </div>

    <section class="home-section">
        <nav>
            <div class="sidebar-button">                                    
                <i class='bx bx-menu sidebarBtn'></i>
                <span class="dashboard">SPORUAL</span>
            </div>

            <div style="display: flex; align-items: center; justify-content: center; flex: 1; gap: 20px;">
                <div class="logo">
                    <img src="../assets/images/charusat.png" alt="Logo 1" title="CHARUSAT University">
                </div>
                <h1 style="white-space: nowrap; font-size: 24px;">Spoural Event Management System</h1>
                <div class="logo">
                    <img src="../assets/images/ulsc.png" alt="Logo 2" title="ULSC">
                </div>
            </div>

            <div class="profile-details" id="profileDropdown">
                <img src="https://t4.ftcdn.net/jpg/00/97/00/09/360_F_97000908_wwH2goIihwrMoeV9QF3BW6HtpsVFaNVM.jpg" alt="Admin Profile">
                <span class="admin_name"><?php echo htmlspecialchars($admin_username); ?></span>
                <i class='bx bx-chevron-down'></i>
                
                <div class="dropdown-menu" id="profileMenu">
                    <a href="#" class="dropdown-item">
                        <i class='bx bx-user-circle'></i> Profile
                    </a>
                    <a href="#" class="dropdown-item">
                        <i class='bx bx-cog'></i> Settings
                    </a>
                    <a href="../logout.php" class="dropdown-item">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </div>
            </div>
        </nav>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Profile dropdown functionality
                const profileDropdown = document.getElementById('profileDropdown');
                const profileMenu = document.getElementById('profileMenu');
                
                // Toggle dropdown menu
                profileDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.classList.toggle('active');
                });
                
                // Close dropdown when clicking elsewhere
                document.addEventListener('click', function() {
                    profileMenu.classList.remove('active');
                });
                
                // Sidebar toggle functionality
                const sidebar = document.querySelector(".sidebar");
                const sidebarBtn = document.querySelector(".sidebarBtn");
                
                sidebarBtn.addEventListener("click", function() {
                    sidebar.classList.toggle("active");
                    
                    // Update button icon based on sidebar state
                    if (sidebar.classList.contains("active")) {
                        sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
                    } else {
                        sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
                    }
                });
            });
        </script>
        <?php
        // Footer is now loaded at the bottom of each page instead of here
        // include_once('../includes/footer.php');
        ?>