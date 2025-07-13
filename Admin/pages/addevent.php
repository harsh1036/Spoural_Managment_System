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
$event_id = $event_name = $event_type = $min_participants = $max_participants = "";

// Define the correct column headers
$expectedColumns = ['event_name', 'event_type', 'min_participants', 'max_participants'];

$message = "";

// Fetch academic years from the database
$academicYears = [];
$yearQuery = $dbh->query("SELECT year FROM academic_years ORDER BY year DESC");
if ($yearQuery) {
    $academicYears = $yearQuery->fetchAll(PDO::FETCH_COLUMN);
}

// Handle download template
if (isset($_GET['download_template'])) {
    // Fetch column names dynamically from the events table using PDO
    $columns = [];
    $stmt = $dbh->query("SHOW COLUMNS FROM events");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    $data = [ $columns ]; // Column headers
    $xlsx = SimpleXLSXGen::fromArray($data);
    $xlsx->downloadAs('Events_Template.xlsx');
    exit;
}

// Handle delete operation (SOFT DELETE IMPLEMENTATION)
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];
        // Change: Update the 'status' column to 0 instead of deleting the row
        $sql = "UPDATE events SET status = 0 WHERE id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo "<script>alert('Event Deleted successfully!'); window.location.href='addevent.php';</script>";
        } else {
            echo "<script>alert('Error Deleting event!');</script>";
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
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle form submission
if (isset($_POST['save_event'])) {
    $event_name = $_POST['event_name'];
    $event_type = $_POST['event_type'];
    $min_participants = $_POST['min_participants'];
    $max_participants = $_POST['max_participants'];
    $event_id = $_POST['event_id'];

    try {
        if (!empty($event_id)) {
            // Update existing event
            $sql = "UPDATE events SET event_name = :name, event_type = :type, min_participants = :min, max_participants = :max WHERE id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
        } else {
            // Insert new event (assuming default status is 1 for active)
            $sql = "INSERT INTO events (event_name, event_type, min_participants, max_participants, status) VALUES (:name, :type, :min, :max, 1)";
            $stmt = $dbh->prepare($sql);
        }

        $stmt->bindParam(':name', $event_name, PDO::PARAM_STR);
        $stmt->bindParam(':type', $event_type, PDO::PARAM_STR);
        $stmt->bindParam(':min', $min_participants, PDO::PARAM_INT);
        $stmt->bindParam(':max', $max_participants, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "<script>alert('Event saved successfully!'); window.location.href='addevent.php';</script>";
        } else {
            echo "<script>alert('Error saving event!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle file upload and validation
if (isset($_POST['import'])) {
    if ($_FILES['excel_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];
        
        if ($xlsx = SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            
            // Validate column names
            if ($rows[0] !== $expectedColumns) {
                echo "<script>alert('Error: Column names do not match the expected format!'); window.location.href='addevent.php';</script>";
                exit;
            } else {
                try {
                    foreach (array_slice($rows, 1) as $row) {
                        $event_name = $row[0]; 
                        $event_type = $row[1]; 
                        $min_participants = $row[2]; 
                        $max_participants = $row[3];

                        // Insert new event with status = 1
                        $sql = "INSERT INTO events (event_name, event_type, min_participants, max_participants, status) VALUES (:name, :type, :min, :max, 1)";
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindParam(':name', $event_name, PDO::PARAM_STR);
                        $stmt->bindParam(':type', $event_type, PDO::PARAM_STR);
                        $stmt->bindParam(':min', $min_participants, PDO::PARAM_INT);
                        $stmt->bindParam(':max', $max_participants, PDO::PARAM_INT);
                        $stmt->execute();
                    }
                    echo "<script>alert('Data imported successfully!'); window.location.href='addevent.php';</script>";
                    exit;
                } catch (PDOException $e) {
                    echo "<script>alert('Database error: " . addslashes($e->getMessage()) . "'); window.location.href='addevent.php';</script>";
                    exit;
                }
            }
        } else {
            echo "<script>alert('Failed to parse Excel file!'); window.location.href='addevent.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Error uploading file!'); window.location.href='addevent.php';</script>";
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

    <div class="home-content">
        <div class="participant-entry-container">
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-calendar-event'></i> Events</h2>
                    <div style="margin-top: 10px;">
                        <label for="academicYear">Academic Year: </label>
                        <select id="academicYear" name="academicYear">
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Move View Events table here -->
                <section class="event-table">
                    <h3>View Events</h3>
                    <div class="table-scroll" style="max-height: 400px; overflow-y: auto;">
                        <table border='2px' class='cntr table table-bordered table-striped small-table participants-table'>
                            <thead>
                                <tr>
                                    <th>Event ID</th>
                                    <th>Event Name</th>
                                    <th>Event Type</th>
                                    <th>Min Participants</th>
                                    <th>Max Participants</th>
                                    <th>Academic Year</th>
                                    <th>Status</th> <!-- Added Status Column -->
                                    <th>Edit</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Fetch events for display (including status)
                                $query = $dbh->prepare("SELECT e.*, ay.year AS academic_year FROM events e LEFT JOIN academic_years ay ON e.academic_year_id = ay.id ORDER BY e.id DESC");
                                $query->execute();
                                $events = $query->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($events as $event) { 
                                ?>
                                <tr>
                                    <td><?= $event['id'] ?></td>
                                    <td><?= htmlspecialchars($event['event_name']) ?></td>
                                    <td><?= htmlspecialchars($event['event_type']) ?></td>
                                    <td><?= htmlspecialchars($event['min_participants']) ?></td>
                                    <td><?= htmlspecialchars($event['max_participants']) ?></td>
                                    <td><?= htmlspecialchars($event['academic_year'] ?? '-') ?></td>
                                    <td><?= ($event['status'] == 1) ? 'Active' : 'Inactive'; ?></td> <!-- Display Status -->
                                    <td>
                                        <a href="#" class="edit-event btn btn-sm btn-primary" 
                                           data-id="<?= $event['id'] ?>"
                                           data-name="<?= htmlspecialchars($event['event_name']) ?>"
                                           data-type="<?= htmlspecialchars($event['event_type']) ?>"
                                           data-min="<?= htmlspecialchars($event['min_participants']) ?>"
                                           data-max="<?= htmlspecialchars($event['max_participants']) ?>">
                                            <i class='bx bx-edit'></i> Edit
                                        </a>
                                    </td>
                                    <td>
                                        <a href="addevent.php?delete_id=<?= $event['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this event?');"> <!-- Updated confirmation message -->
                                            <i class='bx bx-trash'></i> Delete <!-- Changed text to Delete -->
                                        </a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Card UI for upload options -->
                <div class="card-container">
                    <div class="upload-card" id="singleEventCard">
                        <div class="icon-title-row">
                            <i class='bx bx-detail'></i>
                            <span class="card-title">Upload Single Event</span>
                        </div>
                        <p class="card-subtitle">Add a single event</p>
                    </div>
                    <div class="upload-card" id="multipleEventCard">
                        <div class="icon-title-row">
                            <i class='bx bx-list-ul'></i>
                            <span class="card-title">Upload Multiple Events</span>
                        </div>
                        <p class="card-subtitle">Add multiple events</p>
                    </div>
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
                            <input type="hidden" name="event_id" value="<?= isset($_GET['edit_id']) ? htmlspecialchars($_GET['edit_id']) : '' ?>">

                            <div class="form-group mb-3">
                                <label class="form-label">Event Name:</label>
                                <input type="text" name="event_name" class="form-control" value="" required>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Event Type:</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input type="radio" name="event_type" value="Sports" class="form-check-input" required>
                                        <label class="form-check-label">Sports</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="event_type" value="Cultural" class="form-check-input" required>
                                        <label class="form-check-label">Cultural</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Min Participants:</label>
                                <input type="number" name="min_participants" class="form-control" value="" required>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label class="form-label">Max Participants:</label>
                                <input type="number" name="max_participants" class="form-control" value="" required>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="save_event" class="btn btn-primary px-4"><?= isset($_GET['edit_id']) ? 'Update' : 'Submit' ?></button>
                            </div>
                        </form>
                    </section>
                    <br><br>
                    
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

            <!-- <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-info-circle'></i> Event Information</h2>
                </div>
                <div class="card-content">
                    <p>Use the options above to manage events. You can:</p>
                    <ul>
                        <li>View the list of events</li>
                        <li>Manage event details</li>
                        <li>View all event information at once</li>
                    </ul>
                </div>
            </div> -->
        </div>
    </div>

    <?php include_once('../includes/footer.php'); ?>

    <script>
        function toggleContent(contentId) {
            const content = document.getElementById(contentId);
            const otherContent = contentId === 'singleEventContent' ? 
                document.getElementById('multipleEventContent') : 
                document.getElementById('singleEventContent');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                otherContent.style.display = 'none';
            } else {
                content.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('singleEventCard').addEventListener('click', function() {
                document.getElementById('singleEventContent').style.display = 'block';
                document.getElementById('multipleEventContent').style.display = 'none';
            });
            document.getElementById('multipleEventCard').addEventListener('click', function() {
                document.getElementById('singleEventContent').style.display = 'none';
                document.getElementById('multipleEventContent').style.display = 'block';
            });
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

                // Update form title and button
                document.querySelector('.event-form h3').textContent = 'Edit Event';
                document.querySelector('button[name="save_event"]').textContent = 'Update';

                // Show the form section
                document.getElementById('singleEventContent').style.display = 'block';
                document.getElementById('multipleEventContent').style.display = 'none';

                // Scroll to form
                document.querySelector('.event-form').scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>

</html>
