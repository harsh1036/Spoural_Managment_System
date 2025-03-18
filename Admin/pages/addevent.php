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

$query = $dbh->prepare("SELECT * FROM events ORDER BY id DESC");
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
        <?php
        include_once('../includes/sidebar.php');
        ?>

    <div class="home-content">
        <div class="home-page">
        <div class="main-content">
            <section class="event-form">
            <h2><?= !empty($event_id) ? 'Edit Event' : 'New Event' ?></h2>
            <form method="post" action="addevent.php" class="event-input-form">
                <input type="hidden" name="event_id" value="<?= htmlspecialchars($event_id) ?>">

                <label>Event Name:</label>
                <input type="text" name="event_name" class="input-field" value="<?= htmlspecialchars($event_name) ?>" required><br><br>

                <label>Event Type:</label>
                <input type="radio" name="event_type" value="Sports" <?= ($event_type == 'Sports') ? 'checked' : '' ?> required> Sports
                <input type="radio" name="event_type" value="Cultural" <?= ($event_type == 'Cultural') ? 'checked' : '' ?> required> Cultural<br><br>

                <label>Min Participants:</label>
                <input type="number" name="min_participants" class="input-field" value="<?= htmlspecialchars($min_participants) ?>" required><br><br>

                <label>Max Participants:</label>
                <input type="number" name="max_participants" class="input-field" value="<?= htmlspecialchars($max_participants) ?>" required>

                <button type="submit" name="save_event" class="submit-button"><?= !empty($event_id) ? 'Update' : 'Submit' ?></button>
            </form>
            </section>

            <section class="event-table">
                <h2>View Events</h2>
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
                        <?php foreach ($events as $event) { ?>
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
    </div>
                        </section>
</div>

<?php
                        include_once('../includes/footer.php');
        ?>

</body>

</html>
