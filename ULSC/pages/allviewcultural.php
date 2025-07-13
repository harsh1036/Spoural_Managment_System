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

// Fetch all cultural events
$query = $dbh->prepare("SELECT * FROM events WHERE event_type = 'Cultural' ORDER BY event_name");
$query->execute();
$all_events = $query->fetchAll(PDO::FETCH_ASSOC);

// Fetch participants for cultural events FILTERED BY DEPARTMENT
$query = $dbh->prepare("
    SELECT p.id, p.student_id, s.student_name, d.dept_name, e.event_name 
    FROM participants p 
    JOIN departments d ON p.dept_id = d.dept_id 
    JOIN events e ON p.event_id = e.id
    LEFT JOIN student s ON p.student_id = s.student_id
    WHERE e.event_type = 'Cultural'
    AND p.dept_id = :dept_id
    ORDER BY e.event_name ASC
");
$query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
$query->execute();
$participants = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- All Data PDF Button -->
<form method="POST" action="download_cultural_pdf.php">
    <input type="hidden" name="download_all_data" value="1">
    <input type="hidden" name="dept_id" value="<?= $dept_id ?>">
    <button type="submit" name="download_pdf" class="download-btn">
        <img src="../assets/images/pdf-icon.png" alt="PDF Icon" class="pdf-icon">
        Download <?= $dept_name ?> Department Data
    </button>
</form>
<!-- Display cultural event participants for ULSC's department -->
<?php if (!empty($participants)) { ?>
    <section class="view-admin-details">
        <h2 class="section-title">All Participants for Cultural Events - <?= $dept_name ?> Department</h2>
        <table class="participants-table">
            <thead>
                <tr>
                    <th><center>Participant ID</th>
                    <th><center>Student Name</th>
                    <th><center>Event Name</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $participant) { ?>
                    <tr>
                        <td><?= htmlspecialchars($participant['student_id']) ?></td>
                        <td><?= htmlspecialchars($participant['student_name'] ?? 'Name not found') ?></td>
                        <td><?= htmlspecialchars($participant['event_name']) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>
<?php } else { ?>
    <div class="empty-message">
        No cultural event participants found for <?= $dept_name ?> department.
    </div>
<?php } ?>