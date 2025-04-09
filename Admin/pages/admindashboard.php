<?php
include('../includes/session_management.php');
include('../includes/config.php');

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['login'])) {
    header('location:../index.php');
    exit();
}

// Fetch session data
$admin_username = $_SESSION['login'];

// Get dashboard statistics with error handling
try {
    $total_events = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    $total_events = 0;
}

try {
    $total_ulsc = $conn->query("SELECT COUNT(*) AS total FROM ulsc")->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    $total_ulsc = 0;
}

try {
    $total_admins = $conn->query("SELECT COUNT(*) AS total FROM admins")->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    $total_admins = 0;
}

// Check if tables exist before querying
$students_table_exists = $conn->query("SHOW TABLES LIKE 'students'")->num_rows > 0;
$departments_table_exists = $conn->query("SHOW TABLES LIKE 'departments'")->num_rows > 0;

$total_students = 0;
if ($students_table_exists) {
    try {
        $total_students = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0;
    } catch (Exception $e) {
        $total_students = 0;
    }
}

$total_departments = 0;
if ($departments_table_exists) {
    try {
        $total_departments = $conn->query("SELECT COUNT(*) AS total FROM departments")->fetch_assoc()['total'] ?? 0;
    } catch (Exception $e) {
        $total_departments = 0;
    }
}

// Get recent events with error handling
try {
    $recent_events = $conn->query("SELECT * FROM events ORDER BY id DESC LIMIT 5");
} catch (Exception $e) {
    $recent_events = null;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPORUAL Event Management</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner h2 {
            font-size: 28px;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .welcome-banner p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .stat-card .icon {
            font-size: 48px;
            color: var(--primary-light);
            margin-right: 20px;
        }
        
        .stat-card .stat-info {
            flex-grow: 1;
        }
        
        .stat-card .stat-title {
            font-size: 18px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        
        .stat-card .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .stat-card .stat-badge {
            background: rgba(41, 66, 166, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: var(--primary-color);
            margin-top: 5px;
            display: inline-block;
        }
        
        .recent-events-card {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
            transition: all var(--transition-speed);
        }
        
        .recent-events-card:hover {
            box-shadow: var(--box-shadow-hover);
        }
        
        .recent-events-card h3 {
            font-size: 22px;
            margin-bottom: 20px;
            color: var(--text-primary);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
        }
        
        .event-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all var(--transition-speed);
        }
        
        .event-item:hover {
            background: rgba(41, 66, 166, 0.05);
            transform: translateX(10px);
            padding-left: 10px;
        }
        
        .event-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
            font-size: 18px;
        }
        
        .event-details {
            flex-grow: 1;
        }
        
        .event-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .event-meta {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-button {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            flex: 1;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all var(--transition-speed);
            text-decoration: none;
            color: var(--text-primary);
        }
        
        .action-button:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
            color: var(--primary-color);
        }
        
        .action-button .icon {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-right: 15px;
            font-size: 24px;
            transition: all var(--transition-speed);
        }
        
        .action-button:hover .icon {
            transform: rotate(10deg);
        }
        
        .action-button .text {
            font-size: 16px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>

    <div class="home-content">
        <div class="container-fluid px-4">
            <div class="welcome-banner">
                <h2>Welcome back, <?php echo htmlspecialchars($admin_username); ?>!</h2>
                <p>Here's what's happening with your Spoural Events today</p>
                        </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon">
                        <i class='bx bxs-calendar bx-tada'></i>
                        </div>
                    <div class="stat-info">
                        <div class="stat-title">Total Events</div>
                        <div class="stat-value"><?php echo $total_events; ?></div>
                        <div class="stat-badge">Up to date</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">
                        <i class='bx bxs-group bx-flashing'></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-title">Total ULSC</div>
                        <div class="stat-value"><?php echo $total_ulsc; ?></div>
                        <div class="stat-badge">Active Teams</div>
                    </div>
                        </div>
                
                <div class="stat-card">
                    <div class="icon">
                        <i class='bx bxs-user-badge bx-spin'></i>
                        </div>
                    <div class="stat-info">
                        <div class="stat-title">Total Admins</div>
                        <div class="stat-value"><?php echo $total_admins; ?></div>
                        <div class="stat-badge">System Users</div>
                    </div>
                </div>
                
               
            </div>
            
            <div class="action-buttons">
                <a href="addevent.php" class="action-button">
                    <div class="icon">
                        <i class='bx bxs-calendar-plus'></i>
                    </div>
                    <div class="text">Add New Event</div>
                </a>
                
                <a href="schedule_matches.php" class="action-button">
                    <div class="icon">
                        <i class='bx bx-calendar-event'></i>
                    </div>
                    <div class="text">Schedule Matches</div>
                </a>
                
                <a href="addstudents.php" class="action-button">
                    <div class="icon">
                        <i class='bx bxs-user-plus'></i>
                    </div>
                    <div class="text">Add Students</div>
                </a>
            </div>
            
            <!-- <div class="recent-events-card">
                <h3>Recent Events</h3>
                <?php if ($recent_events && $recent_events->num_rows > 0): ?>
                    <?php while($event = $recent_events->fetch_assoc()): ?>
                        <div class="event-item">
                            <div class="event-icon">
                                <i class='bx bxs-calendar-event'></i>
                            </div>
                            <div class="event-details">
                                <div class="event-title"><?php echo htmlspecialchars($event['name'] ?? 'Event Name'); ?></div>
                                <div class="event-meta">
                                    <span><i class='bx bxs-time'></i> <?php echo htmlspecialchars($event['date'] ?? 'Event Date'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No recent events found.</p>
                <?php endif; ?>
            </div> -->
        </div>
    </div>

    <?php include_once('../includes/footer.php'); ?>

</body>

</html>