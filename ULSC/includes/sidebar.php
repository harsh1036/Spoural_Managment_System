<?php
// Include the session check logic first and foremost.
// This file will also call session_start() for us.
require_once '../includes/session_management.php'; // Adjust path if necessary

// Fetch session data
$admin_username = $_SESSION['login'] ?? 'Guest';

// Initialize $dept_name to a default value *before* any conditional logic.
// This prevents the "Undefined variable" warning if the DB connection fails or ulsc_id is not set.
$dept_name = 'Department';

// Fetch department name for ULSC user if available
if (isset($_SESSION['ulsc_id'])) {
    // You may need to include your config file for DB connection
    // Ensure config.php defines $dbh (your PDO object)
    include_once __DIR__ . '/config.php'; // Adjust path for config.php

    if (isset($dbh)) { // Check if $dbh was successfully set in config.php
        try {
            $ulsc_id = $_SESSION['ulsc_id'];
            $query = $dbh->prepare("SELECT d.dept_name FROM ulsc u JOIN departments d ON u.dept_id = d.dept_id WHERE u.ulsc_id = :ulsc_id");
            $query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
            $query->execute();
            $row = $query->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $dept_name = $row['dept_name'];
            }
        } catch (PDOException $e) {
            // Log the error or display a user-friendly message, but don't expose database details
            error_log("Database error fetching department name: " . $e->getMessage());
            $dept_name = 'Department (DB Error)';
        }
    } else {
        $dept_name = 'Department (DB Not Configured)';
    }
}

// Determine current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Handle logout
if (isset($_GET['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    // Clear session-related cookies to be thorough
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    echo "<script>window.location.href='../index.php';</script>";
    exit();
}

// Make sure $remaining_time is available to JavaScript,
// it comes from session_management.php
$js_remaining_time = $remaining_time ?? $session_timeout_duration;
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
                <a href="ulscdashboard.php" class="<?php echo ($current_page == 'ulscdashboard.php') ? 'active' : ''; ?>">
                    <i class='bx bxs-dashboard'></i>
                    <span class="links_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="addsportsevent.php" class="<?php echo ($current_page == 'addsportsevent.php') ? 'active' : ''; ?>">
                    <i class='bx bx-football'></i>
                    <span class="links_name">Sports Entry</span>
                </a>
            </li>
            <li>
                <a href="addculturalevent.php" class="<?php echo ($current_page == 'addculturalevent.php') ? 'active' : ''; ?>">
                    <i class='bx bx-music'></i>
                    <span class="links_name">Cultural Entry</span>
                </a>
            </li>

            <li>
                <a href="viewsportsevent.php" class="<?php echo ($current_page == 'viewsportsevent.php') ? 'active' : ''; ?>">
                    <i class='bx bx-list-check'></i>
                    <span class="links_name">View Sports Entries</span>
                </a>
            </li>
            <li>
                <a href="viewculturalevent.php" class="<?php echo ($current_page == 'viewculturalevent.php') ? 'active' : ''; ?>">
                    <i class='bx bx-list-ul'></i>
                    <span class="links_name">View Cultural Entries</span>
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
                <h1 style="white-space: nowrap; font-size: 24px;">ULSC Member Dashboard</h1>
                <div class="logo">
                    <img src="../assets/images/ulsc.png" alt="Logo 2" title="ULSC">
                </div>
            </div>
            <div>
                <span class="dept-badge"><?php echo htmlspecialchars($dept_name); ?></span>
            </div>
            <div class="profile-details" id="profileDropdown">
                <img src="https://t4.ftcdn.net/jpg/00/97/00/09/360_F_97000908_wwH2goIihwrMoeV9QF3BW6HtpsVFaNVM.jpg" alt="profile">
                <span class="admin_name"><?php echo htmlspecialchars($admin_username); ?></span>
                <div class="session-timer">
                    <span>Session: </span>
                    <span id="countdown"></span>
                </div>
                <i class='bx bx-chevron-down'></i>

                <div class="dropdown-menu" id="profileMenu">
                    <a href="profile.php" class="dropdown-item">
                        <i class='bx bx-user-circle'></i> Profile
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

                if (profileDetails && profileMenu) {
                    profileDetails.addEventListener('click', function(e) {
                        e.stopPropagation();
                        profileMenu.classList.toggle('active');
                    });

                    document.addEventListener('click', function() {
                        profileMenu.classList.remove('active');
                    });
                }

                // Sidebar toggle functionality - simplified from your previous version,
                // matching the original logic structure but ensuring elements exist.
                const sidebar = document.querySelector(".sidebar");
                const sidebarBtn = document.querySelector(".sidebarBtn");
                const homeSection = document.querySelector(".home-section"); // Assuming this applies to the main content area

                if (sidebar && sidebarBtn && homeSection) {
                    sidebarBtn.onclick = function() {
                        sidebar.classList.toggle("active");
                        if (sidebar.classList.contains("active")) {
                            sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
                            // Adjust home-section for collapsed sidebar
                            homeSection.style.marginLeft = '60px'; // Same as COLLAPSED.contentMargin
                            homeSection.style.width = 'calc(100% - 60px)'; // Same as COLLAPSED.contentWidth
                        } else {
                            sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
                            // Adjust home-section for expanded sidebar
                            homeSection.style.marginLeft = '260px'; // Same as EXPANDED.contentMargin
                            homeSection.style.width = 'calc(100% - 260px)'; // Same as EXPANDED.contentWidth
                        }
                        // Also adjust the nav bar if it's separate from homeSection content
                        const nav = homeSection.querySelector('nav');
                        if (nav) {
                            nav.style.marginLeft = homeSection.style.marginLeft;
                            nav.style.width = homeSection.style.width;
                        }
                    };
                } else {
                    console.error('Required elements for sidebar not found.');
                }

                // Session countdown timer
                let timeLeft = <?php echo (int) $js_remaining_time; ?>; // Ensure it's an integer
                const countdownElement = document.getElementById('countdown');

                if (countdownElement) {
                    function formatTime(seconds) {
                        const minutes = Math.floor(seconds / 60);
                        const remainingSeconds = seconds % 60;
                        return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
                    }

                    // Set initial display
                    countdownElement.textContent = formatTime(timeLeft);

                    function updateCountdown() {
                        timeLeft--;
                        countdownElement.textContent = formatTime(timeLeft);

                        if (timeLeft <= 60 && timeLeft > 0) { // Highlight red for last 1 minute
                            countdownElement.style.color = '#ff0000';
                            countdownElement.style.fontWeight = 'bold';
                        } else if (timeLeft > 60) { // Reset color if it goes back up (e.g., after refresh)
                            countdownElement.style.color = '#2942a6'; // Your desired default color
                            countdownElement.style.fontWeight = '500'; // Your desired default font weight
                        }

                        if (timeLeft <= 0) {
                            clearInterval(countdownInterval); // Stop the interval
                            window.location.href = '../index.php?error=session_expired';
                        }
                    }

                    const countdownInterval = setInterval(updateCountdown, 1000);
                } else {
                    console.error('Countdown element not found.');
                }
            });
        </script>
        <?php
        include_once('../includes/footer.php');
        ?>