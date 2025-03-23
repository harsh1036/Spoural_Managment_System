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

// Fetch all cultural events once
$query = $dbh->prepare("SELECT * FROM events WHERE event_type = 'Sports' ORDER BY id");
$query->execute();
$all_events = $query->fetchAll(PDO::FETCH_ASSOC);

// Handle event selection
$selected_event_id = isset($_POST['selected_event']) ? intval($_POST['selected_event']) : '';
$participants = [];
$selected_event_name = "";

if (!empty($selected_event_id)) {
    // Fetch participants with student_id and department name
    $query = $dbh->prepare("
        SELECT p.id, p.student_id, d.dept_name 
        FROM participants p 
        JOIN departments d ON p.dept_id = d.dept_id 
        WHERE p.event_id = :event_id
    ");
    $query->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
    $query->execute();
    $participants = $query->fetchAll(PDO::FETCH_ASSOC);

    // Fetch event name
    $query = $dbh->prepare("SELECT event_name FROM events WHERE id = :event_id");
    $query->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
    $query->execute();
    $event = $query->fetch(PDO::FETCH_ASSOC);
    
    if ($event) {
        $selected_event_name = htmlspecialchars($event['event_name']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spoural Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
</head>

<body>
<div class="home-content">
    <?php include_once('../includes/sidebar.php'); ?>
    <div class="home-page">
        <section class="new-admin">
            
        </section>

        <!-- Download Buttons -->
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
           

            <!-- All Data PDF Button -->
            <form method="POST" action="download_sports_pdf.php">
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
                        font-size: 14px;
                    ">
                    <img src="../assets/images/pdf-icon.png" alt="PDF Icon" 
                        style="width: 20px; height: 20px;">
                    Download All Data
                </button>
            </form>
        </div>

        <?php if (!empty($participants)) { ?>
            <section class="view-admin-details">
                <h2>Participants List for <?= $selected_event_name ?></h2>
                <table border="2px" class="table table-bordered table-striped small-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Participant ID</th>
                            <th>Department Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $participant) { ?>
                            <tr>
                                <td><?= $participant['id'] ?></td>
                                <td><?= htmlspecialchars($participant['student_id']) ?></td>
                                <td><?= htmlspecialchars($participant['dept_name']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>
        <?php } ?>
    </div>
</div>
</body>
</html> 