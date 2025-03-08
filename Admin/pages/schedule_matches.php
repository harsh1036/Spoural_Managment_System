<?php


// Check if user is logged in, else redirect to login

session_start();
// Fetch session data
$admin_username = $_SESSION['login'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPORUAL Event Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   
</head>

<body>
<div class="home-content">
        <?php
        include_once('../includes/sidebar.php');
        ?>

    <div class="home-content">
        <div class="home-page">
        <section class="new-admin">
        <div class="container mt-5 main-content">
        <h4 class="mb-4">Schedule Matches</h4>
        <form class="mb-4">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Match Type</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="matchType" value="sports" checked>
                        <label class="form-check-label">Sports</label>
                    
                        
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="matchType" value="cultural" checked>
                        <label class="form-check-label">Cultural</label>
                    
                        
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sport Name</label>
                    <input type="text" class="form-control" placeholder="Enter Sport Name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Team 1 Name</label>
                    <input type="text" class="form-control" placeholder="Enter Team 1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Team 2 Name</label>
                    <input type="text" class="form-control" placeholder="Enter Team 2">
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Match Date</label>
                    <input type="date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Match Time</label>
                    <input type="time" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" placeholder="Enter Location">
                </div>
                <center>
                    <div class="col-md-2">
                        <br><br><br>
                        <button class="btn btn-green w-100">Add Match</button>
                    </div>
                </center>
            </div>
        </form>
    </div> </section>

                        </section>
</div>

<?php
                        include_once('../includes/footer.php');
        ?>
</body>
