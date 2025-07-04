<?php
require_once __DIR__ . '/SimpleXLSXGen.php';
require_once __DIR__ . '/SimpleXLSX.php';
use Shuchkin\SimpleXLSXGen;
use Shuchkin\SimpleXLSX;

include('../includes/session_management.php');
include('../includes/config.php');

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch session data
$admin_username = $_SESSION['login'];

// Initialize variables
$event_id = $event_name = $event_type = $min_participants = $max_participants = $academic_year_id = "";

// Define the correct column headers for Excel import
$expectedColumns = ['event_name', 'event_type', 'min_participants', 'max_participants'];

$message = "";

// Fetch academic years from the database
$academicYears = [];
// Fetch both ID and year for the dropdown and internal use
$yearQuery = $dbh->query("SELECT id, year FROM academic_years ORDER BY year DESC");
if ($yearQuery) {
    $academicYears = $yearQuery->fetchAll(PDO::FETCH_ASSOC);
}

// Determine the currently selected academic year for filtering and form pre-population
$currentAcademicYearId = null;
if (isset($_GET['academic_year_id'])) {
    $currentAcademicYearId = $_GET['academic_year_id'];
} else {
    // Set a default academic year (e.g., the latest one) if no academic_year_id is in GET
    if (!empty($academicYears)) {
        $currentAcademicYearId = $academicYears[0]['id']; 
    }
}


// Handle download template
if (isset($_GET['download_template'])) {
    // The template does not include academic_year_id as it's selected on the form
    $data = [
        ['event_name', 'event_type', 'min_participants', 'max_participants'], // Column headers
    ];
    $xlsx = SimpleXLSXGen::fromArray($data);
    $xlsx->downloadAs('Events_Template.xlsx');
    exit;
}

// Handle delete operation
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];
        $current_year_for_redirect = isset($_GET['academic_year_id']) ? $_GET['academic_year_id'] : $currentAcademicYearId;

        $sql = "DELETE FROM events WHERE id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo "<script>alert('Event deleted successfully!'); window.location.href='addevent.php?academic_year_id=" . htmlspecialchars($current_year_for_redirect) . "';</script>";
        } else {
            echo "<script>alert('Error deleting event!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle edit operation - fetch event data
if (isset($_GET['edit_id'])) {
    try {
        $edit_id = $_GET['edit_id'];
        $sql = "SELECT * FROM events WHERE id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($event = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $event_id = $event['id'];
            $event_name = $event['event_name'];
            $event_type = $event['event_type'];
            $min_participants = $event['min_participants'];
            $max_participants = $event['max_participants'];
            $academic_year_id = $event['academic_year_id']; // Fetch academic_year_id
            $currentAcademicYearId = $academic_year_id; // Set current selected year to the event's year for the dropdown
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle form submission (Add/Update Event)
if (isset($_POST['save_event'])) {
    $event_name = $_POST['event_name'];
    $event_type = $_POST['event_type'];
    $min_participants = $_POST['min_participants'];
    $max_participants = $_POST['max_participants'];
    $event_id = $_POST['event_id'];
    $academic_year_id = $_POST['academic_year_id']; // Get academic year from form

    try {
        if (!empty($event_id)) {
            // Update existing event
            $sql = "UPDATE events SET event_name = :name, event_type = :type, min_participants = :min, max_participants = :max, academic_year_id = :academic_year_id WHERE id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
        } else {
            // Insert new event
            $sql = "INSERT INTO events (event_name, event_type, min_participants, max_participants, academic_year_id) VALUES (:name, :type, :min, :max, :academic_year_id)";
            $stmt = $dbh->prepare($sql);
        }

        $stmt->bindParam(':name', $event_name, PDO::PARAM_STR);
        $stmt->bindParam(':type', $event_type, PDO::PARAM_STR);
        $stmt->bindParam(':min', $min_participants, PDO::PARAM_INT);
        $stmt->bindParam(':max', $max_participants, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "<script>alert('Event saved successfully!'); window.location.href='addevent.php?academic_year_id=" . htmlspecialchars($academic_year_id) . "';</script>";
        } else {
            echo "<script>alert('Error saving event!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle file upload and validation for multiple events
if (isset($_POST['import'])) {
    // Get the selected academic year for imported events
    $selected_academic_year_id = $_POST['import_academic_year_id'];

    if ($_FILES['excel_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];
        
        if ($xlsx = SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            
            // Validate column names
            if (empty($rows) || $rows[0] !== $expectedColumns) { // Added empty($rows) check
                echo "<script>alert('Error: Column names in the Excel file do not match the expected format (event_name, event_type, min_participants, max_participants) or file is empty!'); window.location.href='addevent.php?academic_year_id=" . htmlspecialchars($selected_academic_year_id) . "';</script>";
                exit;
            } else {
                try {
                    $dbh->beginTransaction(); // Start transaction for bulk insert
                    foreach (array_slice($rows, 1) as $row) {
                        // Basic validation for row data (e.g., check if values are present)
                        if (count($row) < 4 || empty($row[0])) { // Ensure enough columns and event_name is not empty
                            continue; // Skip invalid rows
                        }

                        $event_name = $row[0]; 
                        $event_type = $row[1]; 
                        $min_participants = $row[2]; 
                        $max_participants = $row[3];

                        $sql = "INSERT INTO events (event_name, event_type, min_participants, max_participants, academic_year_id) VALUES (:name, :type, :min, :max, :academic_year_id)";
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindParam(':name', $event_name, PDO::PARAM_STR);
                        $stmt->bindParam(':type', $event_type, PDO::PARAM_STR);
                        $stmt->bindParam(':min', $min_participants, PDO::PARAM_INT);
                        $stmt->bindParam(':max', $max_participants, PDO::PARAM_INT);
                        $stmt->bindParam(':academic_year_id', $selected_academic_year_id, PDO::PARAM_INT); // Use the selected academic year ID
                        $stmt->execute();
                    }
                    $dbh->commit(); // Commit transaction
                    echo "<script>alert('Data imported successfully!'); window.location.href='addevent.php?academic_year_id=" . htmlspecialchars($selected_academic_year_id) . "';</script>";
                    exit;
                } catch (PDOException $e) {
                    $dbh->rollBack(); // Rollback on error
                    echo "<script>alert('Database error during import: " . addslashes($e->getMessage()) . "'); window.location.href='addevent.php?academic_year_id=" . htmlspecialchars($selected_academic_year_id) . "';</script>";
                    exit;
                }
            }
        } else {
            echo "<script>alert('Failed to parse Excel file! Please ensure it's a valid .xlsx file.'); window.location.href='addevent.php?academic_year_id=" . htmlspecialchars($selected_academic_year_id) . "';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Error uploading file! Please try again. Error code: " . $_FILES['excel_file']['error'] . "'); window.location.href='addevent.php?academic_year_id=" . htmlspecialchars($selected_academic_year_id) . "';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Spoural</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>

    <div class="home-content">
        <div class="participant-entry-container">
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-calendar-event'></i> Events</h2>

                    <div style="margin-top: 10px;">
                        <label for="academicYearSelect">Academic Year: </label>
                        <select id="academicYearSelect" name="academicYearSelect" onchange="filterEventsByAcademicYear()">
                            <?php foreach ($academicYears as $yearData): ?>
                                <option value="<?= htmlspecialchars($yearData['id']) ?>" <?= ($currentAcademicYearId == $yearData['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($yearData['year']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="quick-access-grid">
                    <a href="#" class="quick-access-card" id="singleEventBtn">
                        <i class='bx bx-detail'></i>
                        <h3>Upload Single Event</h3>
                        <p>Add a single event</p>
                    </a>

                    <a href="#" class="quick-access-card" id="multipleEventBtn">
                        <i class='bx bx-list-ul'></i>
                        <h3>Upload Multiple Events</h3>
                        <p>Add multiple events</p>
                    </a>
                </div>
            </div>

            <div class="content-card" id="singleEventContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-detail'></i> Single Event Management</h2>
                </div>
                <div class="main-content">
                    <section class="event-form">
                        <h3 class="mb-4"><?= isset($_GET['edit_id']) ? 'Edit Event' : 'New Event' ?></h3>
                        <form method="post" action="addevent.php" class="event-input-form">
                            <input type="hidden" name="event_id" value="<?= htmlspecialchars($event_id) ?>">
                            <input type="hidden" name="academic_year_id" id="formAcademicYearId" value="<?= htmlspecialchars($currentAcademicYearId) ?>">

                            <div class="form-group mb-3">
                                <label class="form-label">Event Name:</label>
                                <input type="text" name="event_name" class="form-control" value="<?= htmlspecialchars($event_name) ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Event Type:</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input type="radio" name="event_type" value="Sports" class="form-check-input" <?= ($event_type === 'Sports') ? 'checked' : '' ?> required>
                                        <label class="form-check-label">Sports</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="event_type" value="Cultural" class="form-check-input" <?= ($event_type === 'Cultural') ? 'checked' : '' ?> required>
                                        <label class="form-check-label">Cultural</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Min Participants:</label>
                                <input type="number" name="min_participants" class="form-control" value="<?= htmlspecialchars($min_participants) ?>" required>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label class="form-label">Max Participants:</label>
                                <input type="number" name="max_participants" class="form-control" value="<?= htmlspecialchars($max_participants) ?>" required>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="save_event" class="btn btn-primary px-4"><?= isset($_GET['edit_id']) ? 'Update' : 'Submit' ?></button>
                            </div>
                        </form>
                    </section>
                    <br><br>
                    <section class="event-table">
                        <h3>View Events for Selected Academic Year</h3>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Event ID</th>
                                    <th>Event Name</th>
                                    <th>Event Type</th>
                                    <th>Min Participants</th>
                                    <th>Max Participants</th>
                                    <th>Edit</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Fetch events for the selected academic year
                                $events = [];
                                if ($currentAcademicYearId) {
                                    $query = $dbh->prepare("SELECT id, event_name, event_type, min_participants, max_participants FROM events WHERE academic_year_id = :academic_year_id ORDER BY id DESC");
                                    $query->bindParam(':academic_year_id', $currentAcademicYearId, PDO::PARAM_INT);
                                    $query->execute();
                                    $events = $query->fetchAll(PDO::FETCH_ASSOC);
                                }
                                
                                if (empty($events)) {
                                    echo "<tr><td colspan='7'>No events found for this academic year.</td></tr>";
                                } else {
                                    foreach ($events as $event) { 
                                    ?>
                                    <tr>
                                        <td><?= $event['id'] ?></td>
                                        <td><?= htmlspecialchars($event['event_name']) ?></td>
                                        <td><?= htmlspecialchars($event['event_type']) ?></td>
                                        <td><?= htmlspecialchars($event['min_participants']) ?></td>
                                        <td><?= htmlspecialchars($event['max_participants']) ?></td>
                                        <td>
                                            <a href="#" class="edit-event btn btn-sm btn-primary" 
                                               data-id="<?= $event['id'] ?>"
                                               data-name="<?= htmlspecialchars($event['event_name']) ?>"
                                               data-type="<?= htmlspecialchars($event['event_type']) ?>"
                                               data-min="<?= htmlspecialchars($event['min_participants']) ?>"
                                               data-max="<?= htmlspecialchars($event['max_participants']) ?>"
                                               data-academic-year-id="<?= htmlspecialchars($currentAcademicYearId) ?>">
                                                <i class='bx bx-edit'></i> Edit
                                            </a>
                                        </td>
                                        <td>
                                            <a href="addevent.php?delete_id=<?= $event['id'] ?>&academic_year_id=<?= htmlspecialchars($currentAcademicYearId) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class='bx bx-trash'></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php 
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </section>
                </div>
            </div>

            <div class="content-card" id="multipleEventContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-list-ul'></i> Multiple Events Management</h2>
                </div>
                <div class="main-content">
                    <div class="container mt-3">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="card shadow-lg border-0 rounded-3">
                                    <div class="card-body">
                                        <?php if (isset($message)) echo $message; ?>
                                        <a href="?download_template=1" class="btn btn-info w-100 mb-3">
                                            <i class='bx bx-download'></i> Download Template
                                        </a>
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="import_academic_year_id" id="importAcademicYearId" value="<?= htmlspecialchars($currentAcademicYearId) ?>">

                                            <div class="mb-3">
                                                <label for="excel_file" class="form-label">Upload Excel File (.xlsx)</label>
                                                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx" required>
                                            </div>
                                            <div class="d-grid">
                                                <button type="submit" name="import" class="btn btn-success">
                                                    <i class='bx bx-upload'></i> Import Data
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once('../includes/footer.php'); ?>

    <script>
        // Function to filter events by academic year
        function filterEventsByAcademicYear() {
            const selectedAcademicYearId = document.getElementById('academicYearSelect').value;
            window.location.href = `addevent.php?academic_year_id=${selectedAcademicYearId}`;
        }

        function toggleContent(contentId) {
            const content = document.getElementById(contentId);
            const otherContent = contentId === 'singleEventContent' ? 
                document.getElementById('multipleEventContent') : 
                document.getElementById('singleEventContent');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                otherContent.style.display = 'none';
            } else {
                // If it's already visible, you might want to hide it, or do nothing.
                // For this scenario, we want to always show the selected content.
                // If you intend to toggle off if clicked again, change this else block.
                // For now, it will just ensure the other is hidden.
                otherContent.style.display = 'none'; 
            }
        }

        document.getElementById('singleEventBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('singleEventContent');
            // Ensure the hidden academic_year_id in the single event form is updated
            document.getElementById('formAcademicYearId').value = document.getElementById('academicYearSelect').value;

            // Reset single event form fields when switching to it, unless in edit mode
            if (document.querySelector('input[name="event_id"]').value === '') {
                document.querySelector('input[name="event_name"]').value = '';
                document.querySelectorAll('input[name="event_type"]').forEach(radio => radio.checked = false);
                document.querySelector('input[name="min_participants"]').value = '';
                document.querySelector('input[name="max_participants"]').value = '';
                document.querySelector('.event-form h3').textContent = 'New Event';
                document.querySelector('button[name="save_event"]').textContent = 'Submit';
            }
        });

        document.getElementById('multipleEventBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('multipleEventContent');
            // Ensure the hidden academic_year_id for import is updated
            document.getElementById('importAcademicYearId').value = document.getElementById('academicYearSelect').value;
        });

        // Handle edit button clicks
        document.querySelectorAll('.edit-event').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get the data from the clicked button
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const type = this.getAttribute('data-type');
                const min = this.getAttribute('data-min');
                const max = this.getAttribute('data-max');
                const academicYearId = this.getAttribute('data-academic-year-id'); // Get academic year ID

                // Populate the form fields
                document.querySelector('input[name="event_id"]').value = id;
                document.querySelector('input[name="event_name"]').value = name;
                
                // Set the radio button for event type
                document.querySelectorAll('input[name="event_type"]').forEach(radio => {
                    if (radio.value === type) {
                        radio.checked = true;
                    }
                });

                document.querySelector('input[name="min_participants"]').value = min;
                document.querySelector('input[name="max_participants"]').value = max;
                document.getElementById('formAcademicYearId').value = academicYearId; // Set hidden academic year ID

                // Update form title and button
                document.querySelector('.event-form h3').textContent = 'Edit Event';
                document.querySelector('button[name="save_event"]').textContent = 'Update';

                // Show the form section
                document.getElementById('singleEventContent').style.display = 'block';
                document.getElementById('multipleEventContent').style.display = 'none';

                // Ensure the academic year dropdown also reflects the event's academic year when editing
                document.getElementById('academicYearSelect').value = academicYearId;

                // Scroll to form
                document.querySelector('.event-form').scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Set the academic year dropdown based on URL parameter or default on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const academicYearIdParam = urlParams.get('academic_year_id');
            const academicYearSelect = document.getElementById('academicYearSelect');
            
            if (academicYearIdParam) {
                academicYearSelect.value = academicYearIdParam;
            } else if (academicYearSelect.options.length > 0 && academicYearSelect.value === '') {
                // If no param and no default value set by PHP (unlikely with $currentAcademicYearId),
                // select the first academic year by default.
                academicYearSelect.value = academicYearSelect.options[0].value;
            }

            // Initially set the hidden academic year IDs for the forms
            document.getElementById('formAcademicYearId').value = academicYearSelect.value;
            document.getElementById('importAcademicYearId').value = academicYearSelect.value;

            // Determine which content section to show on page load
            if (urlParams.has('edit_id')) {
                // If an edit ID is present, always show the single event form
                document.getElementById('singleEventContent').style.display = 'block';
                document.getElementById('multipleEventContent').style.display = 'none';
            } else {
                // By default, hide both until a quick access card is clicked
                document.getElementById('singleEventContent').style.display = 'none';
                document.getElementById('multipleEventContent').style.display = 'none';
            }
        });
    </script>
</body>

</html>