<?php
session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login

if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ulsc_login.php");
    exit;
}
// Fetch session data
$admin_username = $_SESSION['login'];
// Initialize variables
$event_id = $event_name = $event_type = $min_participants = $max_participants = "";

// **Fetch ULSC Member's Department ID**
$ulsc_id = $_SESSION['ulsc_id'];
$sql = "SELECT u.dept_id, d.dept_name, u.ulsc_name 
        FROM ulsc u 
        JOIN departments d ON u.dept_id = d.id 
        WHERE u.ulsc_id = :ulsc_id";
$query = $dbh->prepare($sql);
$query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
$query->execute();
$ulsc = $query->fetch(PDO::FETCH_ASSOC);

if (!$ulsc) {
    echo "<script>alert('ULSC member not found'); window.location.href='ulsc_dashboard.php';</script>";
    exit;
}

// Store ULSC name and department name
$ulsc_name = htmlspecialchars($ulsc['ulsc_name']);
$dept_name = htmlspecialchars($ulsc['dept_name']);

$dept_id = $ulsc['dept_id']; // Auto-assign dept_id

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event']; 
    $student_ids = $_POST['student_id'];
    $min_participants = (int) $_POST['minParticipants'];
    $max_participants = (int) $_POST['maxParticipants'];

    // Count number of participants being added
    $num_participants = count($student_ids);

    // Check current participants count in the database
    $sql = "SELECT COUNT(*) FROM participants WHERE event_id = :event_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $query->execute();
    $current_count = (int) $query->fetchColumn();

    // Calculate total participants after adding new ones
    $total_participants = $current_count + $num_participants;

    // Ensure participant count is within limits
    if ($total_participants > $max_participants) {
        echo "<script>alert('Cannot add participants. Exceeds maximum limit.');</script>";
    } else {
        // Insert each participant into the database
        $sql = "INSERT INTO participants (event_id, student_id) VALUES (:event_id, :student_id)";
        $query = $dbh->prepare($sql);

        foreach ($student_ids as $student_id) {
            $query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $query->bindParam(':student_id', $student_id, PDO::PARAM_STR);
            $query->execute();
        }

        echo "<script>alert('Participants added successfully!'); window.location.href='ulscdashboard.php';</script>";
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
        <section class="new-admin">
        <section>
<!-- Add Participant Form -->
<form action="addculturalevent.php" method="POST">
    <label for="eventSelect">Select Event:</label>
    <select id="eventSelect" name="event" onchange="showParticipantsForm()" required>
        <option value="">Select Event...</option>
        <?php foreach ($events as $event) : ?>
            <?php if ($event['event_type'] === 'Cultural') : // Filter only sport events ?>
                <option value="<?= $event['id']; ?>" data-min="<?= $event['min_participants']; ?>" data-max="<?= $event['max_participants']; ?>">
                    <?= htmlspecialchars($event['event_name']); ?>
                </option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>

    <div id="participantsContainer" style="display:none; margin-top: 15px;">
        <input type="hidden" id="minParticipants" name="minParticipants" value="">
        <input type="hidden" id="maxParticipants" name="maxParticipants" value="">
        <label>Enter Participant IDs:</label>
        <div id="participantFields">
            <div class="participant-entry">
                <input type="text" name="student_id[]" placeholder="Enter Student ID" required>
                <button type="button" class="add-btn" onclick="addParticipantField()">+</button><br>
            </div>
        </div>
    </div>

    <button type="submit" class="submit">Add Participants</button>
</form>


        </div>
    </div>
                        </section>
</div>

<?php
                        include_once('../includes/footer.php');
        ?>
        <script>
            function openNav() {
            document.getElementById("mySidenav").classList.add("open");
        }
        function closeNav() {
            document.getElementById("mySidenav").classList.remove("open");
        }
        document.addEventListener("click", function(event) {
            var sidebar = document.getElementById("mySidenav");
            var sidebarButton = document.querySelector("span[onclick='openNav()']");
    
            // Check if the click is outside the sidebar and not on the open button
            if (!sidebar.contains(event.target) && !sidebarButton.contains(event.target)) {
                closeNav();
            }
        });
        
            document.getElementById("searchInput").addEventListener("keyup", function () {
                let filter = this.value.toLowerCase();
                let rows = document.querySelectorAll("#participantsTable tbody tr");
                rows.forEach(row => {
                    let studentID = row.cells[1].textContent.toLowerCase();
                    let eventName = row.cells[2].textContent.toLowerCase();
                    row.style.display = studentID.includes(filter) || eventName.includes(filter) ? "" : "none";
                });
            });
    
            function confirmDelete(id) {
                let confirmationBox = document.createElement("div");
                confirmationBox.innerHTML = `
                    <div class="confirm-box">
                        <p>Are you sure you want to delete this participant?</p>
                        <button onclick="window.location.href='addculturalevent.php?delete_id=${id}'">Yes</button>
                        <button onclick="closeConfirmBox()">No</button>
                    </div>
                `;
                confirmationBox.classList.add("confirm-overlay");
                document.body.appendChild(confirmationBox);
            }
    
            function closeConfirmBox() {
                document.querySelector(".confirm-overlay").remove();
            }
    
        
    
            function showParticipantsForm() {
        var eventSelect = document.getElementById("eventSelect");
        var selectedOption = eventSelect.options[eventSelect.selectedIndex];
        if (selectedOption.value) {
            var min = selectedOption.getAttribute("data-min");
            var max = selectedOption.getAttribute("data-max");

            document.getElementById("minParticipants").value = min;
            document.getElementById("maxParticipants").value = max;
            document.getElementById("participantsContainer").style.display = "block";
        } else {
            document.getElementById("participantsContainer").style.display = "none";
        }
    }

    
    
    function addParticipantField() {
        var container = document.getElementById("participantFields");
    
        var newDiv = document.createElement("div");
        newDiv.classList.add("participant-entry");
    
        var newInput = document.createElement("input");
        newInput.type = "text";
        newInput.name = "student_id[]";
        newInput.placeholder = "Enter Student ID";
        newInput.required = true;
    
        var removeBtn = document.createElement("button");
        removeBtn.type = "button";
        removeBtn.innerHTML = "-";
        removeBtn.classList.add("remove-btn");
        removeBtn.onclick = function () {
            container.removeChild(newDiv);
        };
    
        newDiv.appendChild(newInput);
        newDiv.appendChild(removeBtn);
        container.appendChild(newDiv);
        }
    
        function fetchEventLimits() {
        var eventId = document.getElementById("eventSelect").value;
        if (eventId !== "") {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "fetch_event_limits.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log("Response:", xhr.responseText); // Debugging
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        console.log("Min:", response.minParticipants, "Max:", response.maxParticipants);
                        document.getElementById("minParticipants").value = response.minParticipants;
                        document.getElementById("maxParticipants").value = response.maxParticipants;
                    } else {
                        console.error("Failed to fetch event limits.");
                    }
                }
            };
            xhr.send("event_id=" + eventId);
        }
    }
    
    
    
    
    
    </script>
    </body>

</html>
