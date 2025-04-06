<?php
session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch session data
$admin_username = $_SESSION['login'];
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
                        <h3><?= isset($_GET['edit_id']) ? 'Edit Event' : 'New Event' ?></h3>
                        <form method="post" action="addevent.php" class="event-input-form">
                            <input type="hidden" name="event_id" value="<?= isset($_GET['edit_id']) ? htmlspecialchars($_GET['edit_id']) : '' ?>">

                            <label>Event Name:</label>
                            <input type="text" name="event_name" class="input-field" value="<?= isset($event_name) ? htmlspecialchars($event_name) : '' ?>" required>

                            <label>Event Type: </label>
                            <br>
                            <input type="radio" name="event_type" value="Sports" <?= (isset($event_type) && $event_type == 'Sports') ? 'checked' : '' ?> required> Sports
                            <input type="radio" name="event_type" value="Cultural" <?= (isset($event_type) && $event_type == 'Cultural') ? 'checked' : '' ?> required> Cultural

                            <label>Min Participants:</label>
                            <input type="number" name="min_participants" class="input-field" value="<?= isset($min_participants) ? htmlspecialchars($min_participants) : '' ?>" required>
                            
                            <label>Max Participants:</label>
                            <input type="number" name="max_participants" class="input-field" value="<?= isset($max_participants) ? htmlspecialchars($max_participants) : '' ?>" required>

                            <button type="submit" name="save_event" class="submit-button"><?= isset($_GET['edit_id']) ? 'Update' : 'Submit' ?></button>
                        </form>
                    </section>
                    <br><br>
                    <section class="event-table">
                        <h3>View Events</h3>
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
                                $query = $dbh->prepare("SELECT * FROM events ORDER BY id DESC");
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
                                    <td>
                                        <a href="addevent.php?edit_id=<?= $event['id'] ?>">
                                            <img src="../assets/images/edit.jpg" alt="Edit" width="20" height="20">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="addevent.php?delete_id=<?= $event['id'] ?>" onclick="return confirm('Are you sure?')">
                                            <img src="../assets/images/delete.jpg" alt="Delete" width="20" height="20">
                                        </a>
                                    </td>
                                </tr>
                                <?php } ?>
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
                                        <a href="?download_template=1" class="btn btn-info w-100 mb-3">📥 Download Template</a>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="excel_file" class="form-label">Upload Excel File (.xlsx)</label>
                                                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx" required>
                                            </div>
                                            <div class="d-grid">
                                                <button type="submit" name="import" class="btn btn-success">📥 Import Data</button>
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

        document.getElementById('singleEventBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('singleEventContent');
        });

        document.getElementById('multipleEventBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('multipleEventContent');
        });
    </script>
</body>

</html>
