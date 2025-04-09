<?php
include('../includes/session_management.php');
include('../includes/config.php');

// Check for ULSC login instead of admin login
if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch ULSC Member's Department ID
$ulsc_id = $_SESSION['ulsc_id'];

// Fetch ULSC department details
$sql = "SELECT u.dept_id, d.dept_name, u.ulsc_name 
        FROM ulsc u 
        JOIN departments d ON u.dept_id = d.dept_id 
        WHERE u.ulsc_id = :ulsc_id";
$query = $dbh->prepare($sql);
$query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
$query->execute();
$ulsc_user = $query->fetch(PDO::FETCH_ASSOC);

if (!$ulsc_user) {
    session_destroy();
    header("Location: ../index.php?error=invalid_session");
    exit();
}

// Store ULSC's department ID safely
$dept_id = $ulsc_user['dept_id'];
$ulsc_name = htmlspecialchars($ulsc_user['ulsc_name']);
$dept_name = htmlspecialchars($ulsc_user['dept_name']);

<<<<<<< HEAD
// Fetch all cultural events
=======
// Fetch all sports events
>>>>>>> f78b80020a4a8441c26cb2010b6fa98d085fdf3e
$query = $dbh->prepare("SELECT * FROM events WHERE event_type = 'Sports' ORDER BY event_name");
$query->execute();
$all_events = $query->fetchAll(PDO::FETCH_ASSOC);

// Fetch participants for Sports events FILTERED BY DEPARTMENT
$query = $dbh->prepare("
    SELECT p.id, p.student_id, d.dept_name, e.event_name 
    FROM participants p 
    JOIN departments d ON p.dept_id = d.dept_id 
    JOIN events e ON p.event_id = e.id
    WHERE e.event_type = 'Sports'
    AND p.dept_id = :dept_id
");
$query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
$query->execute();
$participants = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spoural Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<<<<<<< HEAD
    <style>
        /* Custom styles for the participants table */
        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section-title {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 2px solid #4a6fdc;
        }
        
        .participants-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .participants-table thead {
            background-color: #4a6fdc;
            color: white;
        }
        
        .participants-table th {
            padding: 15px;
            text-align: center;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .participants-table td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .participants-table tbody tr:nth-child(even) {
            background-color: #f5f9ff;
        }
        
        .participants-table tbody tr:hover {
            background-color: #e8f0fe;
            transition: all 0.2s ease;
        }
        
        .empty-message {
            text-align: center;
            color: #e74c3c;
            font-size: 16px;
            padding: 20px;
            background-color: #fff8e6;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 600px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .download-btn {
            background-color: #4CAF50; 
            color: #fff; 
            border: none; 
            padding: 10px 18px; 
            border-radius: 5px; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            margin: 20px auto;
            font-size: 15px;
            transition: background-color 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .download-btn:hover {
            background-color: #45a049;
        }
        
        .pdf-icon {
            width: 22px;
            height: 22px;
        }
    </style>
=======
    
>>>>>>> f78b80020a4a8441c26cb2010b6fa98d085fdf3e
</head>

<body>
<div class="home-content">
    <?php include_once('../includes/sidebar.php'); ?>
<<<<<<< HEAD
    <div class="home-page">
        <div class="content-wrapper">
            <section class="new-admin"></section>

            <!-- All Data PDF Button -->
            <form method="POST" action="download_Sports_pdf.php">
                <input type="hidden" name="download_all_data" value="1">
                <input type="hidden" name="dept_id" value="<?= $dept_id ?>">
                <button type="submit" name="download_pdf" class="download-btn">
                    <img src="../assets/images/pdf-icon.png" alt="PDF Icon" class="pdf-icon">
                    Download <?= $dept_name ?> Department Data
                </button>
            </form>

            <!-- Display Sports event participants for ULSC's department -->
            <?php if (!empty($participants)) { ?>
                <section class="view-admin-details">
                    <h2 class="section-title">All Participants for Sports Events - <?= $dept_name ?> Department</h2>
                    <table class="participants-table">
                        <thead>
                            <tr>
                                <th><center>Participant ID</th>
                                <th><center>Department Name</th>
                                <th><center>Event Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $participant) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($participant['student_id']) ?></td>
                                    <td><?= htmlspecialchars($participant['dept_name']) ?></td>
                                    <td><?= htmlspecialchars($participant['event_name']) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </section>
            <?php } else { ?>
                <div class="empty-message">
                    No Sports event participants found for <?= $dept_name ?> department.
                </div>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>
=======
    <div class="home-page" style="margin:0px">
        <section class="new-admin"></section>

        <!-- All Data PDF Button -->
        <form method="POST" action="download_Sports_pdf.php">
            <input type="hidden" name="download_all_data" value="1">
            <input type="hidden" name="dept_id" value="<?= $dept_id ?>">
            <button type="submit" name="download_pdf"
                style="
                    background-color: #4CAF50; 
                    color: #fff; 
                    border: none; 
                    padding: 8px 15px; 
                    border-radius: 5px; 
                    cursor: pointer; 
                    display: flex; 
                    align-items: center; 
                    gap: 8px; 
                    margin-left:450px;
                    font-size: 14px;
                ">
                <img src="../assets/images/pdf-icon.png" alt="PDF Icon" 
                    style="width: 20px; height: 20px;">
                Download <?= $dept_name ?> Department Data
            </button>
        </form>

        <!-- Display Sports event participants for ULSC's department -->
        <?php if (!empty($participants)) { ?>
            <section class="view-admin-details">
                <h2>All Participants for Sports Events - <?= $dept_name ?> Department</h2>
                <table border="2px" class="table table-bordered table-striped small-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Participant ID</th>
                            <th>Department Name</th>
                            <th>Event Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $participant) { ?>
                            <tr>
                                <td><?= $participant['id'] ?></td>
                                <td><?= htmlspecialchars($participant['student_id']) ?></td>
                                <td><?= htmlspecialchars($participant['dept_name']) ?></td>
                                <td><?= htmlspecialchars($participant['event_name']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>
        <?php } else { ?>
            <p style="text-align: center; color: red; font-size: 16px;">No Sports event participants found for <?= $dept_name ?> department.</p>
        <?php } ?>
    </div>
</div>
</body>
</html>
>>>>>>> f78b80020a4a8441c26cb2010b6fa98d085fdf3e
