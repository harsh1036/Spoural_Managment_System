<?php
session_start();
include('../includes/config.php');

// Redirect if not logged in
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit();
}

// Fetch session data
$admin_username = $_SESSION['login'];

// Fetch all cultural events
$query = $dbh->prepare("SELECT * FROM events WHERE event_type = 'Cultural' ORDER BY id");
$query->execute();
$all_events = $query->fetchAll(PDO::FETCH_ASSOC);

// Fetch participants for **all cultural events**
$query = $dbh->prepare("
    SELECT p.id, p.student_id, d.dept_name, e.event_name 
    FROM participants p 
    JOIN departments d ON p.dept_id = d.dept_id 
    JOIN events e ON p.event_id = e.id
    WHERE e.event_type = 'Cultural'
");
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
    
</head>

<body>
<div class="home-content">
    <?php include_once('../includes/sidebar.php'); ?>
    <div class="home-page"  style="margin:0px">
        <section class="new-admin"></section>

        <!-- All Data PDF Button -->
        <form method="POST" action="download_cultural_pdf.php">
            <input type="hidden" name="download_all_data" value="1">
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
                Download All Data
            </button>
        </form>

        <!-- Display all cultural event participants -->
        <?php if (!empty($participants)) { ?>
            <section class="view-admin-details">
                <h2>All Participants for Cultural Events</h2>
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
            <p>No cultural event participants found.</p>
        <?php } ?>
    </div>
</div>
</body>
</html>
