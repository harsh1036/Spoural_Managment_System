<?php
session_start();
include('../includes/config.php');

// Redirect if not logged in
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
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
    <title>Spoural Management Systems</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    
</head>

<body>
<?php include_once('../includes/sidebar.php'); ?>

<div class="home-content">
    <div class="home-page">
        <section class="new-admin">
            <form method="POST" action="" style="padding-top:50px">
            <label for="event_select">Select Sports Event:</label>
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
                <button type="submit">View Participants</button>
            </form>
        </section>

        <!-- Download Buttons -->
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
            <!-- Event-Wise PDF Button -->
            <form method="POST" action="download_sports_pdf.php">
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
                        <?= empty($selected_event_id) ? 'opacity: 0.5; pointer-events: none;' : '' ?>
                    ">
                    <img src="../assets/images/pdf-icon.png" alt="PDF Icon" 
                        style="width: 20px; height: 20px;">
                    Download Event Data
                </button>
            </form>
        </div>
            
        <?php if (!empty($selected_event_id)) { ?>
            <?php if (!empty($participants)) { ?>
                <section class="view-admin-details">
                    <h2 class="cntr">Participants List for <?= $selected_event_name ?></h2>
                    <table border="2px" class="cntr table table-bordered table-striped small-table">
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
            <?php } else { ?>
                    <section class="view-admin-details">
                        <h2>Participants List for <?= $selected_event_name ?></h2>
                        <p style="text-align: center; color: red; font-size: 16px;">No participants found for this event.</p>
                    </section>
            <?php } ?>
        <?php } ?>
    </div>
</div>

</body>
</html>