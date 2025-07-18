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
<!-- Download Buttons and Table (as before) -->
<?php if (!empty($selected_event_id)) { ?>
<?php if (!empty($participants)) { ?>
    <section class="view-admin-details">
        <h2 class="cntr">Participants List for <?= $selected_event_name ?></h2>
        <table border="2px" class="cntr table table-bordered table-striped small-table participants-table">
            <thead>
                <tr>
                    <th><center>Participant ID</th>
                    <th><center>Student Name</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($participants as $participant) { ?>
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
<!-- Download Button (as before) -->
<div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
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
            <img src="../assets/images/pdf-icon.png" alt="PDF Icon" style="width: 20px; height: 20px;">
            Download Event Data
        </button>
    </form>
</div>