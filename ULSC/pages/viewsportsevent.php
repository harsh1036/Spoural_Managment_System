<?php
include('../includes/session_management.php');
include('../includes/config.php');

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch ULSC details
$ulsc_id = $_SESSION['ulsc_id'];
$query = $dbh->prepare("SELECT u.*, d.dept_name FROM ulsc u JOIN departments d ON u.dept_id = d.dept_id WHERE u.ulsc_id = :ulsc_id");
$query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
$query->execute();
$ulsc = $query->fetch(PDO::FETCH_ASSOC);

if (!$ulsc) {
    session_destroy();
    header("Location: ../index.php?error=invalid_session");
    exit;
}

$dept_id = $ulsc['dept_id'];
$ulsc_name = $ulsc['ulsc_name'];
$dept_name = $ulsc['dept_name'];
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
    .download-btn {
    background-color: #A8C9FF; /* light blue */
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.download-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.download-btn .pdf-icon {
    width: 20px;
    height: 20px;
    filter: brightness(0) invert(1); /* make icon white */
}
    </style>
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>
    <div class="home-content">
        <div class="participant-entry-container">
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-football'></i> View Sports Events</h2>
                </div>
                <div class="card-container">
                    <div class="upload-card" id="singleEventCard">
                        <div class="icon-title-row">
                            <i class='bx bx-detail'></i>
                            <span class="card-title">View Single Event</span>
                        </div>
                        <p class="card-subtitle">View participants for a specific sports event</p>
                    </div>
                    <div class="upload-card" id="multipleEventCard">
                        <div class="icon-title-row">
                            <i class='bx bx-list-ul'></i>
                            <span class="card-title">View All Events</span>
                        </div>
                        <p class="card-subtitle">See all sports events and participants</p>
                    </div>
                </div>
            </div>

            <!-- Single Event Content -->
            <div class="content-card" id="singleEventContent" style="display:none;">
                <div class="content-header">
                    <h2><i class='bx bx-detail'></i> Single Event Participants</h2>
                </div>
                <div class="main-content">
                    <form id="viewParticipantsForm" method="POST" action="viewsportsevent.php" style="padding-top:30px">
                        <label for="event_select">Select Sports Event:</label>
                        <select class="form-select" name="selected_event" id="event_select">
                            <option value="">-- Select Event --</option>
                            <?php 
                            $all_events = $dbh->query("SELECT * FROM events WHERE event_type = 'Sports' ORDER BY event_name")->fetchAll(PDO::FETCH_ASSOC);
                            $selected_event_id = isset($_POST['selected_event']) ? intval($_POST['selected_event']) : '';
                            foreach ($all_events as $event) { ?>
                            
                                <option value="<?= $event['id'] ?>" <?= ($selected_event_id == $event['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($event['event_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <button type="submit" id="viewParticipantsBtn" style="background-color: #007BFF; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin-top:15px; font-size: 12px;">View Participants</button>
                    </form>
                    <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
                        <form method="POST" action="download_Sports_pdf.php">
                            <input type="hidden" name="selected_event_pdf" value="<?= htmlspecialchars($selected_event_id) ?>">
                            <input type="hidden" name="dept_id" value="<?= htmlspecialchars($dept_id ?? '') ?>">

                            <button type="submit" name="download_pdf" class="download-btn" 
                                <?= empty($selected_event_id) || empty($participants) ? 'disabled' : '' ?>>
                                <img src="../assets/images/pdf-icon.png" alt="PDF Icon" class="pdf-icon">
                                Download Event Data
                            </button>
                        </form>
                    </div>
                    <div id="participantsContainer">
                        <?php
                        $participants = [];
                        $selected_event_name = "";
                        if (!empty($selected_event_id)) {
                            $ulsc_id = $_SESSION['ulsc_id'];
                            $ulsc_user = $dbh->prepare("SELECT dept_id FROM ulsc WHERE ulsc_id = :ulsc_id");
                            $ulsc_user->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
                            $ulsc_user->execute();
                            $dept_id = $ulsc_user->fetchColumn();
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
                            $event = $dbh->prepare("SELECT event_name FROM events WHERE id = :event_id");
                            $event->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
                            $event->execute();
                            $event_row = $event->fetch(PDO::FETCH_ASSOC);
                            if ($event_row) {
                                $selected_event_name = htmlspecialchars($event_row['event_name']);
                            }
                        }
                        ?>
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
                                    <p style="text-align: center; color: red; font-size: 16px;">No participants found</p>
                                </section>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Multiple Events Content -->
            <div class="content-card" id="multipleEventContent" style="display:none;">
                <div class="content-header">
                    <h2><i class='bx bx-list-ul'></i> All Sports Events Participants</h2>
                </div>
                <div class="main-content">
                    <form method="POST" action="download_Sports_pdf.php">
                        <input type="hidden" name="download_all_data" value="1">
                        <input type="hidden" name="dept_id" value="<?= htmlspecialchars($dept_id ?? '') ?>">
                        <button type="submit" name="download_pdf" class="download-btn" style="background-color: #007BFF;
                            style="<?= empty($participants) ? 'opacity: 0.5; pointer-events: none;' : '' ?>">
                            <img src="../assets/images/pdf-icon.png" alt="PDF Icon" class="pdf-icon">
                            Download <?= htmlspecialchars($dept_name ?? '') ?> Department Data
                        </button>
                    </form>
                    <div id="allParticipantsContainer">
                        <?php
                        $ulsc_id = $_SESSION['ulsc_id'];
                        $ulsc_user = $dbh->prepare("SELECT dept_id FROM ulsc WHERE ulsc_id = :ulsc_id");
                        $ulsc_user->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
                        $ulsc_user->execute();
                        $dept_id = $ulsc_user->fetchColumn();
                        $query = $dbh->prepare("
                            SELECT p.id, p.student_id, s.student_name, d.dept_name, e.event_name 
                            FROM participants p 
                            JOIN departments d ON p.dept_id = d.dept_id 
                            JOIN events e ON p.event_id = e.id
                            LEFT JOIN student s ON p.student_id = s.student_id
                            WHERE e.event_type = 'Sports'
                            AND p.dept_id = :dept_id
                            ORDER BY e.event_name ASC
                        ");
                        $query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
                        $query->execute();
                        $participants = $query->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if (!empty($participants)) { ?>
                            <section class="view-admin-details">
                                <h2 class="section-title">All Participants for Sports Events - <?= htmlspecialchars($dept_name ?? '') ?> Department</h2>
                                <table border="2px" class="cntr table table-bordered table-striped small-table participants-table">
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
                                No Sports event participants found for <?= htmlspecialchars($dept_name ?? '') ?> department.
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Card toggle functionality (modern card UI)
        document.getElementById('singleEventCard').addEventListener('click', function() {
            document.getElementById('singleEventContent').style.display = 'block';
            document.getElementById('multipleEventContent').style.display = 'none';
        });
        document.getElementById('multipleEventCard').addEventListener('click', function() {
            document.getElementById('singleEventContent').style.display = 'none';
            document.getElementById('multipleEventContent').style.display = 'block';
        });

        // AJAX form submission for viewing single event participants
        document.getElementById('viewParticipantsForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent normal form submission
            
            const form = e.target;
            const formData = new FormData(form);
            const button = document.getElementById('viewParticipantsBtn');
            const container = document.getElementById('participantsContainer');
            
            // Show loading state
            button.textContent = 'Loading...';
            button.disabled = true;
            
            fetch('viewsportsevent.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Create a temporary div to parse the HTML response
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Extract the participants container from the response
                const responseContainer = tempDiv.querySelector('#participantsContainer');
                if (responseContainer) {
                    container.innerHTML = responseContainer.innerHTML;
                }
                
                // Update the download button state
                updateSingleDownloadButtonState();
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = '<p style="text-align: center; color: red; font-size: 16px;">Error loading participants. Please try again.</p>';
            })
            .finally(() => {
                // Reset button state
                button.textContent = 'View Participants';
                button.disabled = false;
            });
        });

        // AJAX button click for viewing all participants
        // document.getElementById('viewAllParticipantsBtn').addEventListener('click', function() {
        //     const button = this;
        //     const container = document.getElementById('allParticipantsContainer');
            
        //     // Show loading state
        //     button.textContent = 'Loading...';
        //     button.disabled = true;
            
        //     fetch('viewsportsevent.php', {
        //         method: 'POST',
        //         headers: {
        //             'Content-Type': 'application/x-www-form-urlencoded',
        //         },
        //         body: 'view_all_participants=1'
        //     })
        //     .then(response => response.text())
        //     .then(html => {
        //         // Create a temporary div to parse the HTML response
        //         const tempDiv = document.createElement('div');
        //         tempDiv.innerHTML = html;
                
        //         // Extract the all participants container from the response
        //         const responseContainer = tempDiv.querySelector('#allParticipantsContainer');
        //         if (responseContainer) {
        //             container.innerHTML = responseContainer.innerHTML;
        //         }
                
        //         // Update the download button state
        //         updateAllDownloadButtonState();
        //     })
        //     .catch(error => {
        //         console.error('Error:', error);
        //         container.innerHTML = '<p style="text-align: center; color: red; font-size: 16px;">Error loading all participants. Please try again.</p>';
        //     })
        //     .finally(() => {
        //         // Reset button state
        //         button.textContent = 'View All Participants';
        //         button.disabled = false;
        //     });
        // });

        // Function to update single event download button state
        function updateSingleDownloadButtonState() {
            const participantsContainer = document.getElementById('participantsContainer');
            const downloadBtn = document.querySelector('#singleEventContent button[name="download_pdf"]');
            
            if (downloadBtn) {
                const hasParticipants = participantsContainer.querySelectorAll('.participants-table tbody tr').length > 0;
                if (hasParticipants) {
                    downloadBtn.style.opacity = '1';
                    downloadBtn.style.pointerEvents = 'auto';
                    downloadBtn.disabled = false;
                } else {
                    downloadBtn.style.opacity = '0.5';
                    downloadBtn.style.pointerEvents = 'none';
                    downloadBtn.disabled = true;
                }
            }
        }

        // Function to update all events download button state
        function updateAllDownloadButtonState() {
            const participantsContainer = document.getElementById('allParticipantsContainer');
            const downloadBtn = document.getElementById('downloadAllBtn');
            
            if (downloadBtn) {
                const hasParticipants = participantsContainer.querySelectorAll('.participants-table tbody tr').length > 0;
                if (hasParticipants) {
                    downloadBtn.style.opacity = '1';
                    downloadBtn.style.pointerEvents = 'auto';
                    downloadBtn.disabled = false;
                } else {
                    downloadBtn.style.opacity = '0.5';
                    downloadBtn.style.pointerEvents = 'none';
                    downloadBtn.disabled = true;
                }
            }
        }

        // Auto-submit the form via AJAX if an event is selected by default
        var eventSelect = document.getElementById('event_select');
        if (eventSelect && eventSelect.value) {
            setTimeout(function() {
                document.getElementById('viewParticipantsForm').dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
            }, 100); // slight delay to ensure DOM is ready
        }

        // Auto-load all participants for all events view on page load
        // if (document.getElementById('multipleEventContent')) {
        //     document.getElementById('multipleEventContent').style.display = 'block';
        //     document.getElementById('singleEventContent').style.display = 'none';
        //     updateAllDownloadButtonState();
        // }
    });
    </script>
    <?php include_once('../includes/footer.php'); ?>
</body>
</html> 