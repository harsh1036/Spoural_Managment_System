<?php
session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login


// Fetch session data
$admin_username = $_SESSION['login'];
// Initialize variables
$event_id = $event_name = $event_type = $min_participants = $max_participants = "";

// **FETCH DATA FOR EDITING**
if (isset($_GET['edit_id'])) {
    $event_id = $_GET['edit_id'];
    $sql = "SELECT * FROM events WHERE id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $event_id, PDO::PARAM_INT);
    $query->execute();
    $eventData = $query->fetch(PDO::FETCH_ASSOC);

    if ($eventData) {
        $event_name = $eventData['event_name'];
        $event_type = $eventData['event_type'];
        $min_participants = $eventData['min_participants'];
        $max_participants = $eventData['max_participants'];
    }
}$query = $dbh->prepare("SELECT * FROM events WHERE event_type='Sports' ORDER BY id");
$query->execute();
$all_events = $query->fetchAll(PDO::FETCH_ASSOC);

// Handle event selection
$selected_event_id = isset($_POST['selected_event']) ? $_POST['selected_event'] : '';
$participants = [];
if ($selected_event_id) {
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
}

// **INSERT OR UPDATE EVENT**
if (isset($_POST['save_event'])) {
    $event_id = $_POST['event_id'];
    $event_name = $_POST['event_name'];
    $event_type = $_POST['event_type'];
    $min_participants = $_POST['min_participants'];
    $max_participants = $_POST['max_participants'];

    if (!empty($event_id)) {
        $sql = "UPDATE events SET event_name = :event_name, event_type = :event_type, min_participants = :min_participants, max_participants = :max_participants WHERE id = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $event_id, PDO::PARAM_INT);
    } else {
        $sql = "INSERT INTO events (event_name, event_type, min_participants, max_participants) VALUES (:event_name, :event_type, :min_participants, :max_participants)";
        $query = $dbh->prepare($sql);
    }

    $query->bindParam(':event_name', $event_name, PDO::PARAM_STR);
    $query->bindParam(':event_type', $event_type, PDO::PARAM_STR);
    $query->bindParam(':min_participants', $min_participants, PDO::PARAM_INT);
    $query->bindParam(':max_participants', $max_participants, PDO::PARAM_INT);

    if ($query->execute()) {
        echo "<script> window.location.href='addevent.php';</script>";
    } else {
        echo "";
    }
}

// **DELETE EVENT**
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $sql = "DELETE FROM events WHERE id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);

    if ($query->execute()) {
        echo "<script>window.location.href='addevent.php';</script>";
    } else {
        echo "<script>alert('Failed to delete event!');</script>";
    }
    
}

$query = $dbh->prepare("SELECT * FROM events where event_type='Cultural' ORDER BY id ");
$query->execute();
$events = $query->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="home-page">
        <section class="new-admin">
            <form method="POST" action="" style="padding-top:50px">
                <label for="event_select">Select Sports Event:</label>
                <select name="selected_event" id="event_select">
                    <option value="">-- Select Event --</option>
                    <?php 
                    
                    foreach ($all_events as $event) { ?>
                        <option value="<?= $event['id'] ?>" <?= ($selected_event_id == $event['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['event_name']) ?>
                        </option>
                    <?php } ?>
                </select>
                <button type="submit">View Participants</button>
            </form>
        </section>
        
        <?php if (!empty($participants)) { ?>
            <section class="view-admin-details">
                <h2>Participants List</h2>
                <table border="2px" class="table table-bordered table-striped small-table" >
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
