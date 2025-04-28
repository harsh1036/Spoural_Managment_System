<?php

// Fetch session data
$admin_username = $_SESSION['login'];

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
                <a href="ulscdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ulscdashboard.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-dashboard'></i>
                    <span class="links_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="addsportsevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'addsportsevent.php' ? 'active' : ''; ?>">
                    <i class='bx bx-football'></i>
                    <span class="links_name">Sports Entry</span>
                </a>
            </li>
            <li>
                <a href="addculturalevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'addculturalevent.php' ? 'active' : ''; ?>">
                    <i class='bx bx-music'></i>
                    <span class="links_name">Cultural Entry</span>
                </a>
            </li>
       
            <li>
                <a href="viewsportsevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'viewsportsevent.php' ? 'active' : ''; ?>">
                    <i class='bx bx-list-check'></i>
                    <span class="links_name">View Sports Entries</span>
                </a>
            </li>
            <li>
                <a href="viewculturalevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'viewculturalevent.php' ? 'active' : ''; ?>">
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

            <div class="profile-details" id="profileDropdown">
                <img src="https://t4.ftcdn.net/jpg/00/97/00/09/360_F_97000908_wwH2goIihwrMoeV9QF3BW6HtpsVFaNVM.jpg" alt="profile">
                <span class="admin_name"><?php echo htmlspecialchars($admin_username); ?></span>
                <div class="session-timer">
                    <span>Session: </span>
                    <span id="countdown"><?php echo $remaining_time; ?></span>s
                </div>
                <i class='bx bx-chevron-down'></i>
                
                <div class="dropdown-menu" id="profileMenu">
                    <a href="#" class="dropdown-item">
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
                
                // Toggle dropdown menu
                if (profileDetails && profileMenu) {
                    profileDetails.addEventListener('click', function(e) {
                        e.stopPropagation();
                        profileMenu.classList.toggle('active');
                    });
                    
                    // Close dropdown when clicking elsewhere
                    document.addEventListener('click', function() {
                        profileMenu.classList.remove('active');
                    });
                }
                
                // Sidebar toggle functionality - completely new implementation
                const sidebar = document.querySelector(".sidebar");
                const sidebarBtn = document.querySelector(".sidebarBtn");
                const homeSection = document.querySelector(".home-section");
                
                // Define sidebar states
                const EXPANDED = {
                    sidebarWidth: '260px',
                    contentMargin: '260px',
                    contentWidth: 'calc(100% - 260px)'
                };
                
                const COLLAPSED = {
                    sidebarWidth: '60px',
                    contentMargin: '60px',
                    contentWidth: 'calc(100% - 60px)'
                };
                
                function setSidebarState(state) {
                    // Apply to sidebar with a small delay to ensure smooth transition
                    sidebar.style.width = state.sidebarWidth;
                    
                    // Apply to content area - fix position to prevent cutoff
                    homeSection.style.marginLeft = state.contentMargin;
                    homeSection.style.width = state.contentWidth;
                    
                    // Apply to navbar
                    const nav = homeSection.querySelector('nav');
                    if (nav) {
                        nav.style.marginLeft = state.contentMargin;
                        nav.style.width = state.contentWidth;
                        
                        // Ensure navbar elements maintain proper position
                        const navElements = nav.querySelectorAll('div');
                        navElements.forEach(el => {
                            el.style.transition = 'all 0.5s ease';
                        });
                    }
                    
                    // Force a reflow immediately to ensure everything is positioned correctly
                    document.body.offsetHeight;
                    
                    // Add 10ms delay to ensure browser has time to process layout changes
                    setTimeout(() => {
                        // Force a reflow to ensure content properly resizes
                        document.body.style.minHeight = '100vh';
                        
                        // Double check content width and position
                        if (state === COLLAPSED) {
                            homeSection.style.width = 'calc(100% - 60px)';
                            homeSection.style.marginLeft = '60px';
                            if (nav) {
                                nav.style.width = 'calc(100% - 60px)';
                                nav.style.marginLeft = '60px';
                                nav.style.left = '0';
                            }
                        } else {
                            homeSection.style.width = 'calc(100% - 260px)';
                            homeSection.style.marginLeft = '260px'; 
                            if (nav) {
                                nav.style.width = 'calc(100% - 260px)';
                                nav.style.marginLeft = '260px';
                                nav.style.left = '0';
                            }
                        }
                    }, 50);
                    
                    // Toggle sidebar texts and icons visibility
                    const logoName = document.querySelector('.logo_name');
                    const linkNames = document.querySelectorAll('.links_name');
                    
                    if (state === COLLAPSED) {
                        if (logoName) logoName.style.display = 'none';
                        linkNames.forEach(link => link.style.display = 'none');
                        sidebarBtn.className = 'bx bx-menu-alt-right sidebarBtn';
                    } else {
                        if (logoName) logoName.style.display = 'block';
                        linkNames.forEach(link => link.style.display = 'block');
                        sidebarBtn.className = 'bx bx-menu sidebarBtn';
                    }
                }
                
                if (sidebar && sidebarBtn && homeSection) {
                    // Set initial state
                    let isCollapsed = false;
                    setSidebarState(EXPANDED);
                    
                    // Toggle sidebar on button click
                    sidebarBtn.addEventListener('click', function() {
                        console.log('Sidebar button clicked');
                        isCollapsed = !isCollapsed;
                        setSidebarState(isCollapsed ? COLLAPSED : EXPANDED);
                        console.log('Sidebar state changed to:', isCollapsed ? 'collapsed' : 'expanded');
                    });
                } else {
                    console.error('Required elements not found:', { sidebar, sidebarBtn, homeSection });
                }
                
                // Session countdown timer
                let timeLeft = <?php echo $remaining_time; ?>;
                const countdownElement = document.getElementById('countdown');
                
                if (countdownElement) {
                    function updateCountdown() {
                        timeLeft--;
                        
                        // Calculate minutes and seconds
                        const minutes = Math.floor(timeLeft / 60);
                        const seconds = timeLeft % 60;
                        
                        // Format time as MM:SS
                        countdownElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                        
                        if (timeLeft <= 10) {
                            countdownElement.style.color = '#ff0000';
                            countdownElement.style.fontWeight = 'bold';
                        }
                        
                        if (timeLeft <= 0) {
                            // Session expired, redirect to login
                            window.location.href = '../index.php?error=session_expired';
                        }
                    }
                    
                    // Update countdown every second
                    setInterval(updateCountdown, 1000);
                }
            });
        </script>
        <?php
                        include_once('../includes/footer.php');
        ?>