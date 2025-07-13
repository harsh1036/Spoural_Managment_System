<?php
// // Fetch session data
// session_start();
$admin_username = $_SESSION['login'] ?? 'Guest';

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

<!DOCTYPE html>
<html lang="en">

<head>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Ubuntu, sans-serif;
        }

        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 260px;
            background: #2942a6;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .sidebar.active {
            width: 60px;
        }

        .sidebar .logo-details {
            height: 60px;
            display: flex;
            align-items: center;
            padding: 0 15px;
        }

        .sidebar .logo-details i {
            font-size: 30px;
            color: #fff;
            min-width: 45px;
        }

        .sidebar .logo-details .logo_name {
            font-size: 22px;
            font-weight: 600;
            color: #fff;
            margin-left: 10px;
        }

        .sidebar .nav-links {
            height: calc(100% - 60px);
            padding: 0;
            margin: 0;
            overflow-y: auto;
        }

        .sidebar .nav-links::-webkit-scrollbar {
            display: none;
        }

        .sidebar .nav-links li {
            list-style: none;
            width: 100%;
        }

        .sidebar .nav-links li a {
            display: flex;
            align-items: center;
            text-decoration: none;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-links li a:hover,
        .sidebar .nav-links li a.active {
            background: #1a307c;
        }

        .sidebar .nav-links li i {
            font-size: 20px;
            min-width: 45px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar .nav-links li a .links_name {
            font-size: 15px;
            font-weight: 400;
            color: #fff;
            white-space: nowrap;
        }

        .sidebar.active .nav-links li a .links_name {
            display: none;
        }

        .sidebar.active .logo-details .logo_name {
            display: none;
        }

        /* Home section */
        .home-section {
            position: relative;
            background: #f5f5f5;
            min-height: 100vh;
            width: calc(100% - 260px);
            left: 260px;
            transition: all 0.3s ease;
        }

        .sidebar.active ~ .home-section {
            width: calc(100% - 60px);
            left: 60px;
        }

        .home-section nav {
            position: fixed;
            width: calc(100% - 260px);
            left: 260px;
            height: 60px;
            background: #fff;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 99;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .sidebar.active ~ .home-section nav {
            width: calc(100% - 60px);
            left: 60px;
        }

        .home-section nav .sidebar-button {
            display: flex;
            align-items: center;
            font-size: 24px;
            font-weight: 500;
        }

        .home-section nav .sidebar-button i {
            font-size: 35px;
            margin-right: 10px;
            cursor: pointer;
        }

        .profile-details {
            display: flex;
            align-items: center;
            background: #f5f5f5;
            border-radius: 6px;
            height: 50px;
            min-width: 190px;
            padding: 0 15px;
            cursor: pointer;
        }

        .profile-details img {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-details .admin_name {
            font-size: 15px;
            font-weight: 500;
            color: #333;
            margin: 0 10px;
            white-space: nowrap;
        }

        .profile-details i {
            font-size: 25px;
            color: #333;
        }

        .session-timer {
            background-color: rgba(41, 66, 166, 0.1);
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 15px;
            font-size: 14px;
            color: #2942a6;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .session-timer span {
            color: #2942a6;
            font-weight: 500;
        }

        .dropdown-menu {
            position: absolute;
            top: 60px;
            right: 0;
            background: #fff;
            width: 200px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            display: none;
            z-index: 1000;
        }

        .dropdown-menu.active {
            display: block;
        }

        .dropdown-menu .dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            text-decoration: none;
            font-size: 15px;
            color: #333;
            transition: all 0.3s ease;
        }

        .dropdown-menu .dropdown-item:hover {
            background: #f5f5f5;
        }

        .dropdown-menu .dropdown-item i {
            font-size: 18px;
            margin-right: 10px;
            color: #333;
        }

        .logo {
            height: 50px;
        }

        .logo img {
            height: 100%;
            object-fit: contain;
        }

        /* Responsive styles */
        @media (max-width: 1200px) {
            .sidebar {
                width: 60px;
            }
            .sidebar.active {
                width: 220px;
            }
            .home-section {
                width: calc(100% - 60px);
                left: 60px;
            }
            .sidebar.active ~ .home-section {
                width: calc(100% - 220px);
                left: 220px;
            }
            .home-section nav {
                width: calc(100% - 60px);
                left: 60px;
            }
            .sidebar.active ~ .home-section nav {
                width: calc(100% - 220px);
                left: 220px;
            }
        }
    </style>
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
                    <span class="links_name">Manage ADMIN</span>
                </a>
            </li>
            <li>
                <a href="addevent.php" class="<?php echo $current_page == 'addevent.php' || $current_page == 'addsingleevent.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-calendar-plus'></i>
                    <span class="links_name">Manage Event</span>
                </a>
            </li>
            <li>
                <a href="addulsc_s.php" class="<?php echo $current_page == 'addulsc_s.php' || $current_page == 'addsingleulsc.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-group'></i>
                    <span class="links_name">Manage ULSC Student</span>
                </a>
            </li>
            <li>
                <a href="addulsc_f.php" class="<?php echo $current_page == 'addulsc_f.php' || $current_page == 'addsingleulsc.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-group'></i>
                    <span class="links_name">Manage ULSC Faculty</span>
                </a>
            </li>
            <li>
                <a href="addstudents.php" class="<?php echo $current_page == 'addstudents.php' || $current_page == 'addsinglestudent.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-user-detail'></i>
                    <span class="links_name">Manage STUDENT</span>
                </a>
            </li>
            <li>
                <a href="adddepartment.php" class="<?php echo $current_page == 'adddepartment.php' || $current_page == 'addsingledepartment.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-building'></i>
                    <span class="links_name">Manage DEPARTMENT</span>
                </a>
            </li>
            <li>
                <a href="certificate.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'certificate.php' ? 'active' : ''; ?>">
                    <i class='bx bx-list-ul'></i>
                    <span class="links_name">Certificate</span>
                </a>
            </li>
            <li>
                <a href="viewsportsevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'viewsportsevent.php' ? 'active' : ''; ?>">
                    <i class='bx bx-list-ul'></i>
                    <span class="links_name">View Sport Particapant</span>
                </a>
            </li>
            <li>
                <a href="viewculturalevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'viewculturalevent.php' ? 'active' : ''; ?>">
                    <i class='bx bx-list-ul'></i>
                    <span class="links_name">View Cultural Particapant</span>
                </a>
            </li>
        </ul>
    </div>

    <section class="home-section">
        <nav>
            <div class="sidebar-button">
                <i class='bx bx-menu sidebarBtn'></i>
                <span class="dashboard">SPOURAL</span>
            </div>

            <div style="display: flex; align-items: center; justify-content: center; flex: 1; gap: 20px;">
                <div class="logo">
                    <img src="../assets/images/charusat.png" alt="Logo 1" title="CHARUSAT University">
                </div>
                <h1 style="white-space: nowrap; font-size: 24px;">Admin Dashboard</h1>
                <div class="logo">
                    <img src="../assets/images/ulsc.png" alt="Logo 2" title="ULSC">
                </div>
            </div>

            <div class="profile-details" id="profileDropdown">
                <img src="https://t4.ftcdn.net/jpg/00/97/00/09/360_F_97000908_wwH2goIihwrMoeV9QF3BW6HtpsVFaNVM.jpg" alt="profile">
                <span class="admin_name"><?php echo htmlspecialchars($admin_username); ?></span>
                <div class="session-timer">
                    <span>Session: </span>
                    <span id="countdown"><?php echo $remaining_time; ?></span>s
                </div>
                <i class='bx bx-chevron-down'></i>

                <div class="dropdown-menu" id="profileMenu">
                    <a href="?logout=true" class="dropdown-item">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </div>
            </div>
        </nav>
        <script>
            let sidebar = document.querySelector(".sidebar");
            let sidebarBtn = document.querySelector(".sidebarBtn");

            sidebarBtn.onclick = function() {
                sidebar.classList.toggle("active");
                if(sidebar.classList.contains("active")) {
                    sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
                } else {
                    sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
                }
            }

            // Profile dropdown functionality
            const profileDetails = document.getElementById('profileDropdown');
            const profileMenu = document.getElementById('profileMenu');
            
            if (profileDetails && profileMenu) {
                profileDetails.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.classList.toggle('active');
                });
                
                document.addEventListener('click', function() {
                    profileMenu.classList.remove('active');
                });
            }

            // Session countdown timer
            let timeLeft = <?php echo $remaining_time; ?>;
            const countdownElement = document.getElementById('countdown');
            
            if (countdownElement) {
                function updateCountdown() {
                    timeLeft--;
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    countdownElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                    
                    if (timeLeft <= 10) {
                        countdownElement.style.color = '#ff0000';
                        countdownElement.style.fontWeight = 'bold';
                    }
                    
                    if (timeLeft <= 0) {
                        window.location.href = '../index.php?error=session_expired';
                    }
                }
                
                setInterval(updateCountdown, 1000);
            }
        </script>
        <?php
        // Footer is now loaded at the bottom of each page instead of here
        include_once('../includes/footer.php');
        ?>
</body>

</html>