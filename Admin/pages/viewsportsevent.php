<?php
include('../includes/session_management.php');
include('../includes/config.php');

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch admin details using admin_id from session
$admin_id = $_SESSION['login'];
$query = $dbh->prepare("SELECT * FROM admins WHERE admin_id = :admin_id");
$query->bindParam(':admin_id', $admin_id, PDO::PARAM_STR);
$query->execute();
$admin = $query->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_destroy();
    header("Location: ../index.php?error=invalid_session");
    exit;
}

$dept_id = $admin['dept_id'];
$ulsc_name = $admin['admin_name'];
// $dept_name = $admin['dept_name'];

// Fetch all events
$all_events = $dbh->query("SELECT * FROM events WHERE event_type = 'Sports' ORDER BY event_name")->fetchAll(PDO::FETCH_ASSOC);
// Fetch all departments
$all_departments = $dbh->query("SELECT * FROM departments ORDER BY dept_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sports Events - Spoural</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    .card-container {
      display: flex;
      gap: 2rem;
      justify-content: center;
      margin: 2rem 0;
    }
    .upload-card {
      background: #f5f8fa;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08);
      padding: 2rem 2.5rem;
      min-width: 320px;
      text-align: center;
      transition: box-shadow 0.2s, background 0.2s;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .upload-card:hover {
      background: #fff;
      box-shadow: 0 8px 32px rgba(44, 62, 80, 0.12);
    }
    .icon-title-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 0.5rem;
    }
    .icon-title-row i {
      font-size: 2.2rem;
      color: #2236d1;
    }
    .card-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: #222;
    }
    .card-subtitle {
      color: #888;
      font-size: 1rem;
      margin: 0;
    }
    </style>
</head>
<body>
<?php include_once('../includes/sidebar.php'); ?>
<br>
<div class="home-content">
    <div class="participant-entry-container">
        <div class="content-card">
            <div class="content-header">
                <h2><i class='bx bx-football'></i> View Sports Events</h2>
            </div>
            <div class="card-container">
                <div class="upload-card" id="eventWiseCard">
                    <div class="icon-title-row">
                        <i class='bx bx-detail'></i>
                        <span class="card-title">Event-wise View</span>
                    </div>
                    <p class="card-subtitle">View participants for a specific sports event</p>
                </div>
                <div class="upload-card" id="departmentWiseCard">
                    <div class="icon-title-row">
                        <i class='bx bx-list-ul'></i>
                        <span class="card-title">Department-wise View</span>
                    </div>
                    <p class="card-subtitle">View all sports participants from a department</p>
                </div>
            </div>
        </div>

        <!-- Event-wise Content -->
        <div class="content-card" id="eventWiseContent" style="display:none;">
            <div class="content-header">
                <h2><i class='bx bx-detail'></i> Event-wise Participants</h2>
            </div>
            <div class="main-content">
                <form id="eventWiseForm" method="POST" action="viewsportsevent.php">
                    <label for="event_select">Select Sports Event:</label>
                    <select class="form-select" name="eventwise_event_id" id="event_select">
                        <option value="" selected>-- Select Event --</option>
                        <?php foreach (
                            $all_events as $event) { ?>
                            <option value="<?= $event['id'] ?>">
                                <?= htmlspecialchars($event['event_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" id="eventWiseBtn" style="background-color: #007BFF; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin-top:15px; font-size: 12px;">View Participants</button>
                </form>
                <div id="eventWiseResults">
                <?php
                if (isset($_POST['eventwise_event_id']) && $_POST['eventwise_event_id'] != '') {
                    $event_id = intval($_POST['eventwise_event_id']);
                    $event_query = $dbh->prepare("SELECT event_name FROM events WHERE id = :event_id");
                    $event_query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                    $event_query->execute();
                    $event_name = $event_query->fetchColumn();
                    $query = $dbh->prepare("
                        SELECT p.id, p.student_id, d.dept_name, s.student_name
                        FROM participants p
                        JOIN departments d ON p.dept_id = d.dept_id
                        LEFT JOIN student s ON p.student_id = s.student_id
                        WHERE p.event_id = :event_id
                        ORDER BY d.dept_name, s.student_name
                    ");
                    $query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
                    $query->execute();
                    $participants = $query->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($participants)) {
                        echo "<section class='view-admin-details'>";
                        echo "<h2 class='cntr'>Participants List for " . htmlspecialchars($event_name) . "</h2>";
                        echo "<input type='text' id='eventWiseSearch' placeholder='Search participants...' class='form-control mb-3' style='max-width: 300px;'>";
                        echo "<table border='2px' class='cntr table table-bordered table-striped small-table participants-table' id='eventWiseTable'>";
                        echo "<thead><tr><th><center>Participant ID</th><th><center>Student Name</th><th><center>Department</th></tr></thead>";
                        echo "<tbody>";
                        foreach ($participants as $participant) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($participant['student_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($participant['student_name'] ?? 'Name not found') . "</td>";
                            echo "<td>" . htmlspecialchars($participant['dept_name']) . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                        // Download button
                        echo "<form method='POST' action='download_Sports_pdf.php' style='margin-top: 20px;'>";
                        echo "<input type='hidden' name='selected_event_pdf' value='" . htmlspecialchars($event_id) . "'>";
                        echo "<button type='submit' name='download_pdf' class='btn btn-success'><i class='bx bx-download'></i> Download Event Data</button>";
                        echo "</form>";
                        echo "</section>";
                    } else {
                        echo "<div class='empty-message'>No participants found for this event.</div>";
                    }
                }
                ?>
                </div>
            </div>
        </div>

        <!-- Department-wise Content -->
        <div class="content-card" id="departmentWiseContent" style="display:none;">
            <div class="content-header">
                <h2><i class='bx bx-list-ul'></i> Department-wise Participants</h2>
            </div>
            <div class="main-content">
                <form id="departmentWiseForm" method="POST" action="viewsportsevent.php">
                    <label for="dept_select">Select Department:</label>
                    <select class="form-select" name="deptwise_dept_id" id="dept_select">
                        <option value="" selected>-- Select Department --</option>
                        <?php foreach ($all_departments as $dept) { ?>
                            <option value="<?= $dept['dept_id'] ?>">
                                <?= htmlspecialchars($dept['dept_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" id="departmentWiseBtn" style="background-color: #007BFF; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin-top:15px; font-size: 12px;">View Participants</button>
                </form>
                <div id="departmentWiseResults">
                <?php
                if (isset($_POST['deptwise_dept_id']) && $_POST['deptwise_dept_id'] != '') {
                    $dept_id = intval($_POST['deptwise_dept_id']);
                    $dept_query = $dbh->prepare("SELECT dept_name FROM departments WHERE dept_id = :dept_id");
                    $dept_query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
                    $dept_query->execute();
                    $dept_name = $dept_query->fetchColumn();
                    $query = $dbh->prepare("
                        SELECT p.id, p.student_id, s.student_name, e.event_name
                        FROM participants p
                        JOIN events e ON p.event_id = e.id
                        LEFT JOIN student s ON p.student_id = s.student_id
                        WHERE p.dept_id = :dept_id AND e.event_type = 'Sports'
                        ORDER BY e.event_name, s.student_name
                    ");
                    $query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
                    $query->execute();
                    $participants = $query->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($participants)) {
                        echo "<section class='view-admin-details'>";
                        echo "<h2 class='cntr'>Sports Participants for Department: " . htmlspecialchars($dept_name) . "</h2>";
                        echo "<input type='text' id='deptWiseSearch' placeholder='Search participants...' class='form-control mb-3' style='max-width: 300px;'>";
                        echo "<table border='2px' class='cntr table table-bordered table-striped small-table participants-table' id='deptWiseTable'>";
                        echo "<thead><tr><th><center>Participant ID</th><th><center>Student Name</th><th><center>Event Name</th></tr></thead>";
                        echo "<tbody>";
                        foreach ($participants as $participant) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($participant['student_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($participant['student_name'] ?? 'Name not found') . "</td>";
                            echo "<td>" . htmlspecialchars($participant['event_name']) . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                        // Download button
                        echo "<form method='POST' action='download_Sports_pdf.php' style='margin-top: 20px;'>";
                        echo "<input type='hidden' name='download_all_data' value='1'>";
                        echo "<input type='hidden' name='dept_id' value='" . htmlspecialchars($dept_id) . "'>";
                        echo "<button type='submit' name='download_pdf' class='btn btn-success'><i class='bx bx-download'></i> Download Department Data</button>";
                        echo "</form>";
                        echo "</section>";
                    } else {
                        echo "<div class='empty-message'>No sports participants found for this department.</div>";
                    }
                }
                ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Card toggle functionality
    document.getElementById('eventWiseCard').addEventListener('click', function() {
        document.getElementById('eventWiseContent').style.display = 'block';
        document.getElementById('departmentWiseContent').style.display = 'none';
        // Do NOT auto-select or auto-submit!
    });

    document.getElementById('departmentWiseCard').addEventListener('click', function() {
        document.getElementById('eventWiseContent').style.display = 'none';
        document.getElementById('departmentWiseContent').style.display = 'block';
        // Do NOT auto-select or auto-submit!
    });

    // AJAX for Event-wise
    document.getElementById('eventWiseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const resultsDiv = document.getElementById('eventWiseResults');
        fetch('viewsportsevent.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(html => {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newResults = tempDiv.querySelector('#eventWiseResults');
            if (newResults) {
                resultsDiv.innerHTML = newResults.innerHTML;
            }
            // Set the dropdown to the selected value after AJAX
            form.eventwise_event_id.value = formData.get('eventwise_event_id');
        });
    });

    // AJAX for Department-wise
    document.getElementById('departmentWiseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const resultsDiv = document.getElementById('departmentWiseResults');
        fetch('viewsportsevent.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(html => {
            // Extract only the departmentWiseResults div from the response
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newResults = tempDiv.querySelector('#departmentWiseResults');
            if (newResults) {
                resultsDiv.innerHTML = newResults.innerHTML;
            }
        });
    });

    // Live search for event-wise participants
    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'eventWiseSearch') {
            const filter = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#eventWiseTable tbody tr');
            rows.forEach(row => {
                let match = false;
                row.querySelectorAll('td').forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        match = true;
                    }
                });
                row.style.display = match ? '' : 'none';
            });
        }
        if (e.target && e.target.id === 'deptWiseSearch') {
            const filter = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#deptWiseTable tbody tr');
            rows.forEach(row => {
                let match = false;
                row.querySelectorAll('td').forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        match = true;
                    }
                });
                row.style.display = match ? '' : 'none';
            });
        }
    });
});
</script>
<?php include_once('../includes/footer.php'); ?>
</body>
</html> 