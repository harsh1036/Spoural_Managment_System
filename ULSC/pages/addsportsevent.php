<?php
session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login

if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ../index.php");
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
        JOIN departments d ON u.dept_id = d.dept_id 
        WHERE u.ulsc_id = :ulsc_id";

$query = $dbh->prepare($sql);
$query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
$query->execute();
$ulsc = $query->fetch(PDO::FETCH_ASSOC);

// **Check if query returned data**
if (!$ulsc) {
    die("<script>alert('ULSC member not found. Please check your session data.'); window.location.href='ulsc_dashboard.php';</script>");
}

// Store ULSC's department ID safely
$dept_id = $ulsc['dept_id'];
$ulsc_name = htmlspecialchars($ulsc['ulsc_name']);
$dept_name = htmlspecialchars($ulsc['dept_name']);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event']; 
    $student_ids = $_POST['student_id'];
    $min_participants = (int) $_POST['minParticipants'];
    $max_participants = (int) $_POST['maxParticipants'];
    $captain_id = $_POST['captain_id'] ?? null;

    // Validate participant count
    $sql = "SELECT COUNT(*) FROM participants WHERE event_id = :event_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $query->execute();
    $current_count = (int) $query->fetchColumn();

    $num_participants = count($student_ids);
    if (($current_count + $num_participants) > $max_participants) {
        echo "<script>alert('Cannot add participants. Exceeds maximum limit.'); window.location.href='addculturalevent.php';</script>";
        exit;
    }

    // Ensure students are not already registered
    $errorFound = false;
    foreach ($student_ids as $student_id) {
        $checkSql = "SELECT COUNT(*) FROM participants WHERE event_id = :event_id AND student_id = :student_id";
        $checkQuery = $dbh->prepare($checkSql);
        $checkQuery->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $checkQuery->bindParam(':student_id', $student_id, PDO::PARAM_STR);
        $checkQuery->execute();
        if ((int) $checkQuery->fetchColumn() > 0) {
            echo "<script>alert('Student ID $student_id is already registered.');</script>";
            $errorFound = true;
        }
    }

    if ($errorFound) {
        echo "<script>alert('Some students are already registered. Please check and try again.'); window.location.href='addculturalevent.php';</script>";
        exit;
    }

    // Validate captain selection
    if (!$captain_id || !in_array($captain_id, $student_ids)) {
        echo "<script>alert('Please select a valid captain from the participants.'); window.location.href='addculturalevent.php';</script>";
        exit;
    }

    // Ensure no existing captain
    $checkCaptainSql = "SELECT COUNT(*) FROM participants WHERE event_id = :event_id AND is_captain = 1";
    $checkCaptainQuery = $dbh->prepare($checkCaptainSql);
    $checkCaptainQuery->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $checkCaptainQuery->execute();
    if ((int) $checkCaptainQuery->fetchColumn() > 0) {
        echo "<script>alert('A captain has already been assigned for this event.'); window.location.href='addculturalevent.php';</script>";
        exit;
    }

    // Insert Participants and Assign Captain
    $sql = "INSERT INTO participants (event_id, student_id, dept_id, is_captain) VALUES (:event_id, :student_id, :dept_id, :is_captain)";
    $query = $dbh->prepare($sql);
    
    foreach ($student_ids as $student_id) {
        $is_captain = ($student_id == $captain_id) ? 1 : 0;
        $query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $query->bindParam(':student_id', $student_id, PDO::PARAM_STR);
        $query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
        $query->bindParam(':is_captain', $is_captain, PDO::PARAM_INT);
        $query->execute();
    }

    echo "<script>alert('Participants and captain assigned successfully!'); window.location.href='ulscdashboard.php';</script>";
    exit;
}



$query = $dbh->prepare("
    SELECT e.*
    FROM events e
    WHERE NOT EXISTS (
        SELECT 1 FROM participants p WHERE p.event_id = e.id
    )
    ORDER BY e.id DESC
");

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
<form action="addsportsevent.php" method="POST">
    <label for="eventSelect">Select Sport Event:</label>
    <select id="eventSelect" name="event" onchange="showParticipantsForm()" required>
        <option value="">Select Event...</option>
        <?php foreach ($events as $event) : ?>
            <?php if ($event['event_type'] === 'Sports') : // Filter only sport events ?>
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
        <div id="participantFields"></div> 
        <input type="hidden" id="captain_id" name="captain_id" value="">
        <!-- Add Button Outside of the Participant Fields -->
        <button type="button" class="add-btn" onclick="addParticipantField()">+</button>

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
                        <button onclick="window.location.href='addsportsevent.php?delete_id=${id}'">Yes</button>
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
                var min = parseInt(selectedOption.getAttribute("data-min"), 10);
                var max = parseInt(selectedOption.getAttribute("data-max"), 10);

                document.getElementById("minParticipants").value = min;
                document.getElementById("maxParticipants").value = max;
                document.getElementById("participantsContainer").style.display = "block";

                generateParticipantFields(min, max);
            } else {
                document.getElementById("participantsContainer").style.display = "none";
            }
        }

        function generateParticipantFields(min, max) {
        var container = document.getElementById("participantFields");
        container.innerHTML = ""; // Clear previous fields

        for (let i = 0; i < min; i++) {
            addParticipantField();
        }

        updateAddButtonState(min);
    }


    function removeParticipantField(button) {
    var container = document.getElementById("participantFields");
    var currentCount = container.getElementsByClassName("participant-entry").length;
    var minParticipants = parseInt(document.getElementById("minParticipants").value, 10);

    if (currentCount > minParticipants) {
        container.removeChild(button.parentElement);
        updateAddButtonState(currentCount - 1);
    } else {
        alert("You cannot remove participants below the minimum required.");
    }
}


function updateAddButtonState(currentCount) {
        var maxParticipants = parseInt(document.getElementById("maxParticipants").value, 10);
        var addButton = document.querySelector(".add-btn");
        addButton.disabled = currentCount >= maxParticipants;
    }

    function addParticipantField() {
    var container = document.getElementById("participantFields");
    var currentCount = container.getElementsByClassName("participant-entry").length;
    var maxParticipants = parseInt(document.getElementById("maxParticipants").value, 10);

    if (currentCount >= maxParticipants) {
        alert("You cannot add more participants than the allowed limit.");
        return;
    }

    // Create new input field
    var newDiv = document.createElement("div");
    newDiv.classList.add("participant-entry");

    var newInput = document.createElement("input");
    newInput.type = "text";
    newInput.name = "student_id[]";
    newInput.placeholder = "Enter Student ID";
    newInput.required = true;

    // **Check for duplicate entry on input change**
    newInput.addEventListener("change", function () {
        if (isDuplicateStudentID(newInput.value)) {
            alert("This student ID has already been added!");
            newInput.value = ""; // Clear duplicate entry
        }
    });

    // **Radio button for captain selection**
    var captainRadio = document.createElement("input");
    captainRadio.type = "radio";
    captainRadio.name = "captain";
    captainRadio.value = currentCount + 1; // Unique value
    captainRadio.onclick = function () {
        setCaptain(this);
    };

    var captainLabel = document.createElement("label");
    captainLabel.textContent = "Captain";

    var removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.innerHTML = "-";
    removeBtn.classList.add("remove-btn");
    removeBtn.onclick = function () {
        removeParticipantField(this);
    };

    newDiv.appendChild(newInput);
    newDiv.appendChild(captainRadio);
    newDiv.appendChild(captainLabel);
    newDiv.appendChild(removeBtn);
    container.appendChild(newDiv);

    updateAddButtonState();
}

function setCaptain(radio) {
    let inputs = document.getElementsByName("student_id[]");
    if (inputs[radio.value - 1].value.trim() !== "") {
        document.getElementById("captain_id").value = inputs[radio.value - 1].value;
    } else {
        alert("Captain must have a valid Student ID.");
        radio.checked = false;
    }
}



// **Function to Check for Duplicate Student ID**
function isDuplicateStudentID(studentID) {
    var inputs = document.querySelectorAll("input[name='student_id[]']");
    var count = 0;

    inputs.forEach(input => {
        if (input.value === studentID) {
            count++;
        }
    });

    return count > 1; // If count > 1, then it's a duplicate
}

    </script>
    </body>

</html>
