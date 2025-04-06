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
        /* Existing styles... */

        .sidebar.active {
            width: 60px;
        }

        .home-section {
            left: 260px;
            width: calc(100% - 260px);
            transition: all 0.5s ease;
        }

        .home-section.active {
            left: 60px;
            width: calc(100% - 60px);
        }

        @media (max-width: 1200px) {
            .sidebar {
                width: 60px;
            }

            .sidebar.active {
                width: 220px;
            }

            .home-section {
                left: 60px;
                width: calc(100% - 60px);
            }

            .home-section.active {
                left: 220px;
                width: calc(100% - 220px);
            }
        }

        /**/
        body {
            font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Ubuntu, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 260px;
            background: #2942a6;
            z-index: 100;
            transition: all 0.5s ease;
        }

        .sidebar.active {
            width: 60px;
        }

        .sidebar .logo-details {
            height: 60px;
            display: flex;
            align-items: center;
            color: white;
        }

        .sidebar .logo-details i {
            font-size: 30px;
            margin-right: 5px;
        }

        .sidebar .logo-details .logo_name {
            font-size: 22px;
            font-weight: 600;
        }

        .sidebar .nav-links {
            margin-top: 10px;
        }

        .sidebar .nav-links li {
            position: relative;
            list-style: none;
            height: 50px;
        }

        .sidebar .nav-links li a {
            height: 100%;
            width: 100%;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.4s ease;
        }

        .sidebar .nav-links li a.active {
            background: #1a307c;
        }

        .sidebar .nav-links li a:hover {
            background: #1a307c;
        }

        .sidebar .nav-links li i {
            min-width: 60px;
            text-align: center;
            font-size: 18px;
            color: white;
        }

        .sidebar .nav-links li a .links_name {
            color: white;
            font-size: 15px;
            font-weight: 400;
            white-space: nowrap;
        }

        .sidebar .log_out {
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .home-section {
            position: relative;
            background: #f5f5f5;
            height: 100vh;
            left: 260px;
            width: calc(100% - 260px);
            transition: all 0.5s ease;
        }

        .home-section nav {
            display: flex;
            justify-content: space-between;
            height: 60px;
            background: #fff;
            display: flex;
            align-items: center;
            position: fixed;
            width: calc(100% - 260px);
            left: 260px;
            z-index: 10;
            padding: 0 20px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
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
        }

        .home-section nav .profile-details {
            display: flex;
            align-items: center;
            background: #f5f5f5;
            border-radius: 6px;
            height: 50px;
            min-width: 190px;
            position: relative;
        }

        .home-section nav .profile-details img {
            height: 45px;
            width: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        .home-section nav .profile-details .admin_name {
            font-size: 15px;
            font-weight: 500;
            color: #333;
            white-space: nowrap;
            margin: 0 10px;
        }

        .home-section nav .profile-details i {
            font-size: 25px;
            color: #333;
        }

        .home-section nav .profile-details .dropdown-menu {
            position: absolute;
            top: 55px;
            right: 0;
            background: #fff;
            width: 200px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            display: none;
        }

        .home-section nav .profile-details .dropdown-menu.active {
            display: block;
        }

        .home-section nav .profile-details .dropdown-menu .dropdown-item {
            height: 50px;
            width: 100%;
            display: flex;
            align-items: center;
            padding: 0 15px;
            font-size: 15px;
            color: #333;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .home-section nav .profile-details .dropdown-menu .dropdown-item:hover {
            background: #f1f1f1;
        }

        .home-section nav .profile-details .dropdown-menu .dropdown-item i {
            font-size: 18px;
            color: #333;
            margin-right: 15px;
        }

        .home-section .home-content {
            position: relative;
            padding-top: 70px;
        }

        .home-section .home-content .overview-boxes {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .home-section .home-content .box {
            display: flex;
            flex-direction: column;
            align-items: center;
            border-radius: 12px;
            padding: 20px;
            background: #fff;
            margin: 10px 0;
            width: calc(100% / 3 - 20px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .home-section .home-content .box i {
            font-size: 35px;
            margin-bottom: 10px;
        }

        .home-section .home-content .box .text {
            font-size: 18px;
            font-weight: 500;
        }

        .home-section .home-content .box .number {
            font-size: 40px;
            font-weight: 500;
        }

        .home-section .home-content .box.total-students {
            background: #bde0fe;
        }

        .home-section .home-content .box.total-students i {
            color: #2942a6;
        }

        .home-section .home-content .box.total-departments {
            background: #fce4ec;
        }

        .home-section .home-content .box.total-departments i {
            color: #e91e63;
        }

        .home-section .home-content .box.total-events {
            background: #fff3cd;
        }

        .home-section .home-content .box.total-events i {
            color: #ff9800;
        }

        @media (max-width: 1200px) {
            .sidebar {
                width: 60px;
            }

            .sidebar.active {
                width: 220px;
            }

            .home-section {
                left: 60px;
                width: calc(100% - 60px);
            }

            .home-section nav {
                width: calc(100% - 60px);
                left: 60px;
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
                <img src="https://t4.ftcdn.net/jpg/00/97/00/09/360_F_97000908_wwH2goIihwrMoeV9QF3BW6HtpsVFaNVM.jpg" alt="profile">
                <span class="admin_name"><?php echo htmlspecialchars($admin_username); ?></span>
                <i class='bx bx-chevron-down'></i>

                <div class="dropdown-menu" id="profileMenu">
                    <a href="#" class="dropdown-item">
                        <i class='bx bx-user-circle'></i> Profile
                    </a>
                    <a href="#" class="dropdown-item">
                        <i class='bx bx-cog'></i> Settings
                    </a>
                    <a href="?logout=true" class="dropdown-item">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </div>
            </div>
        </nav>
        <script>
             document.addEventListener('DOMContentLoaded', function() {
            // Profile dropdown functionality
            const profileDetails = document.getElementById('profileDropdown');
            const profileMenu = document.getElementById('profileMenu');

            // Toggle dropdown menu
            profileDetails.addEventListener('click', function(e) {
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
            const homeSection = document.querySelector(".home-section");

            sidebarBtn.addEventListener("click", function() {
                sidebar.classList.toggle("active");
                homeSection.classList.toggle("active");

                // Update button icon based on sidebar state
                if (sidebar.classList.contains("active")) {
                    sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
                } else {
                    sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
                }
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebar = document.querySelector(".sidebar");
    const sidebarBtn = document.querySelector(".sidebarBtn");
    const homeSection = document.querySelector(".home-section");

    sidebarBtn.addEventListener("click", function() {
        sidebar.classList.toggle("active");
        homeSection.classList.toggle("active");

        // Update button icon based on sidebar state
        if (sidebar.classList.contains("active")) {
            sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
        } else {
            sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
        }
    });
});

            // // new 
            // document.addEventListener('DOMContentLoaded', function() {
            //     // Profile dropdown functionality
            //     const profileDetails = document.getElementById('profileDropdown');
            //     const profileMenu = document.getElementById('profileMenu');

            //     // Toggle dropdown menu
            //     profileDetails.addEventListener('click', function(e) {
            //         e.stopPropagation();
            //         profileMenu.classList.toggle('active');
            //     });

            //     // Close dropdown when clicking elsewhere
            //     document.addEventListener('click', function() {
            //         profileMenu.classList.remove('active');
            //     });

            //     // Sidebar toggle functionality
            //     const sidebar = document.querySelector(".sidebar");
            //     const sidebarBtn = document.querySelector(".sidebarBtn");
            //     const homeSection = document.querySelector(".home-section");

            //     sidebarBtn.addEventListener("click", function() {
            //         sidebar.classList.toggle("active");
            //         homeSection.classList.toggle("active");

            //         // Update button icon based on sidebar state
            //         if (sidebar.classList.contains("active")) {
            //             sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
            //         } else {
            //             sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
            //         }
            //     });
            // });
            // //end

            // document.addEventListener('DOMContentLoaded', function() {
            //     // Profile dropdown functionality
            //     const profileDetails = document.getElementById('profileDropdown');
            //     const profileMenu = document.getElementById('profileMenu');

            //     // Toggle dropdown menu
            //     profileDetails.addEventListener('click', function(e) {
            //         e.stopPropagation();
            //         profileMenu.classList.toggle('active');
            //     });

            //     // Close dropdown when clicking elsewhere
            //     document.addEventListener('click', function() {
            //         profileMenu.classList.remove('active');
            //     });

            //     // Sidebar toggle functionality
            //     const sidebar = document.querySelector(".sidebar");
            //     const sidebarBtn = document.querySelector(".sidebarBtn");

            //     sidebarBtn.addEventListener("click", function() {
            //         sidebar.classList.toggle("active");

            //         // Update button icon based on sidebar state
            //         if (sidebar.classList.contains("active")) {
            //             sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
            //         } else {
            //             sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
            //         }
            //     });
            // });
        </script>
        <?php
        // Footer is now loaded at the bottom of each page instead of here
        include_once('../includes/footer.php');
        ?>
</body>

</html>