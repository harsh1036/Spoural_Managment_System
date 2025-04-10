<?php
include('../includes/session_management.php');
include('../includes/config.php');

// Redirect if not logged in
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

// Fetch all cultural events once
$query = $dbh->prepare("SELECT * FROM events WHERE event_type = 'Cultural' ORDER BY event_name");
$query->execute();
$all_events = $query->fetchAll(PDO::FETCH_ASSOC);

// Handle event selection
$selected_event_id = isset($_POST['selected_event']) ? intval($_POST['selected_event']) : '';
$participants = [];
$selected_event_name = "";

if (!empty($selected_event_id)) {
    // Fetch participants with student_id and student name
    // Filter by ULSC's department
    $query = $dbh->prepare("
        SELECT p.id, p.student_id, d.dept_name, s.student_name
        FROM participants p 
        JOIN departments d ON p.dept_id = d.dept_id 
        LEFT JOIN student s ON p.student_id = s.student_id
        WHERE p.event_id = :event_id
        AND p.dept_id = :dept_id
    ");
    $query->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
    $query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
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

</head>

<body>
<?php include_once('../includes/sidebar.php'); ?>
<div class="home-content">
    <div class="home-page">
    <div class="content-wrapper">
        <section class="new-admin">
            <form method="POST" action="" style="padding-top:50px">
                <label for="event_select">Select Cultural Event:</label>
                <select class="form-select" name="selected_event" id="event_select">
                    <option value="">-- Select Event --</option>
                    <?php foreach ($all_events as $event) { ?>
                        <option value="<?= $event['id'] ?>" <?= ($selected_event_id == $event['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['event_name']) ?>
                        </option>
                    <?php } ?>
                </select>
                
                <button 
                style="
                        background-color: #007BFF; 
                        color: #fff; 

                        border: none; 
                        padding: 8px 15px; 
                        border-radius: 5px; 
                        cursor: pointer; 
                        display: flex; 
                        align-items: center; 
                        gap: 8px;
                        margin-top:15px;
                        margin-left:110px;
                        
                        font-size: 12px;
                    "
                type="submit">View Participants</button>
            </form>
        </section>

        <!-- Download Buttons -->
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
            <!-- Event-Wise PDF Button -->
            <form method="POST" action="download_cultural_pdf.php">
                <input type="hidden" name="selected_event_pdf" value="<?= htmlspecialchars($selected_event_id) ?>">
                <button type="submit" name="download_pdf"
                    style="
                        background-color: #007BFF; 
                        color: #fff; 
                        border: none; 
                        padding: 8px 15px; 
                        border-radius: 5px; 
                        cursor: pointer; 
                        display: flex; 
                        align-items: center; 
                        gap: 8px; 
                        font-size: 14px;
                        <?= empty($selected_event_id) || empty($participants) ? 'opacity: 0.5; pointer-events: none;' : '' ?>
                    ">
                    <img src="../assets/images/pdf-icon.png" alt="PDF Icon" 
                        style="width: 20px; height: 20px;">
                    Download Event Data
                </button>
            </form>

           
            </form>
        </div>

       <?php if (!empty($selected_event_id)) { ?>
    <?php if (!empty($participants)) { ?>
        <section class="view-admin-details">
            <h2 class="cntr">Participants List for <?= $selected_event_name ?></h2>
            <table border="2px" class="cntr table table-bordered table-striped small-table">
            <table class="participants-table">
                <thead>
                    <tr>
                      
                        <th><center>Participant ID</th>
                        <th><center>Student Name</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                    $serial = 1;
                    foreach ($participants as $participant) { ?>
                        <tr>
                          
                            <td><?= htmlspecialchars($participant['student_id']) ?></td>
                            <td><?= htmlspecialchars($participant['student_name'] ?? 'Name not found') ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    <?php } else { ?>
        <section class="view-admin-details">
            <h2>Participants List for <?= $selected_event_name ?></h2>
            <p style="text-align: center; color: red; font-size: 16px;">No participants found </p>
        </section>
    <?php } ?>
<?php } ?>

    </div>
</div>
</body>
</html>