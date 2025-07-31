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
    <title>View Cultural Events - Spoural</title>
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
    <div class="home-content">
        <div class="participant-entry-container">
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-music'></i> View Cultural Events</h2>
                </div>
                <div class="card-container">
                    <div class="upload-card" id="singleEventCard">
                        <div class="icon-title-row">
                            <i class='bx bx-detail'></i>
                            <span class="card-title">View Single Event</span>
                        </div>
                        <p class="card-subtitle">View participants for a specific cultural event</p>
                    </div>
                    <div class="upload-card" id="multipleEventCard">
                        <div class="icon-title-row">
                            <i class='bx bx-list-ul'></i>
                            <span class="card-title">View All Events</span>
                        </div>
                        <p class="card-subtitle">See all cultural events and participants</p>
                    </div>
                </div>
            </div>
            <div id="singleEventContent" style="display:none;"></div>
            <div id="multipleEventContent" style="display:none;"></div>
        </div>
    </div>
    <script>
function attachCulturalFormAJAX() {
    const form = document.querySelector('#singleEventContent form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('singleviewcultural.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('singleEventContent').innerHTML = html;
            attachCulturalFormAJAX(); // Re-attach after content update
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('singleEventCard').addEventListener('click', function() {
        document.getElementById('singleEventContent').style.display = 'block';
        document.getElementById('multipleEventContent').style.display = 'none';
        fetch('singleviewcultural.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('singleEventContent').innerHTML = html;
                attachCulturalFormAJAX();
            });
    });
    document.getElementById('multipleEventCard').addEventListener('click', function() {
        document.getElementById('singleEventContent').style.display = 'none';
        document.getElementById('multipleEventContent').style.display = 'block';
        fetch('allviewcultural.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('multipleEventContent').innerHTML = html;
            });
    });
});
</script>
    <?php include_once('../includes/footer.php'); ?>
</body>
</html>