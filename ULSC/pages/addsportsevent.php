<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ulsc_login.php");
    exit;
}

// // **Fetch ULSC Member's Department ID**
$ulsc_id = $_SESSION['ulsc_id'];
// Fetch ULSC department details
$sql = "SELECT u.dept_id, d.dept_name, u.ulsc_name 
        FROM ulsc u 
        JOIN departments d ON u.dept_id = d.dept_id 
        WHERE u.ulsc_id = :ulsc_id";
$query = $dbh->prepare($sql);
$query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
$query->execute();
$ulsc = $query->fetch(PDO::FETCH_ASSOC);

if (!$ulsc) {
    die("<script>alert('ULSC member not found. Please check your session.'); window.location.href='ulsc_dashboard.php';</script>");
}

// Store ULSC's department ID safely
$dept_id = $ulsc['dept_id'];
$ulsc_name = htmlspecialchars($ulsc['ulsc_name']);
$dept_name = htmlspecialchars($ulsc['dept_name']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $captain_id = $_POST['captain_id'] ?? null;
    error_log("Captain ID: " . print_r($captain_id, true));
    $event_id = $_POST['event'];
    $student_ids = $_POST['student_id'];
    $min_participants = (int)$_POST['minParticipants'];
    $max_participants = (int)$_POST['maxParticipants'];
    $captain_id = $_POST['captain_id'] ?? null;

    // Validate participant count
    $sql = "SELECT COUNT(*) FROM participants WHERE event_id = :event_id AND dept_id = :dept_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT); // Add department filter
    $query->execute();
    $current_count = (int) $query->fetchColumn();

    $num_participants = count($student_ids);
    if (($current_count + $num_participants) > $max_participants) {
        echo "<script>alert('Cannot add participants. Exceeds maximum limit.'); window.location.href='addsportsevent.php';</script>";
        exit;
    }

    // Validate captain selection
    if (!$captain_id || !in_array($captain_id, $student_ids)) {
        echo "<script>alert('Please select a valid captain from the participants.'); window.location.href='addsportsevent.php';</script>";
        exit;
    }

    // Batch fetch student details
    $placeholders = [];
    $paramValues = [];

    foreach ($student_ids as $index => $student_id) {
        $param = ":student_id" . $index;
        $placeholders[] = $param;
        $paramValues[$param] = $student_id;
    }

    $placeholdersString = implode(',', $placeholders);
    $checkStudentSql = "SELECT student_id, dept_id FROM student WHERE student_id IN ($placeholdersString)";
    $checkStudentQuery = $dbh->prepare($checkStudentSql);
    $checkStudentQuery->execute($paramValues);
    $students = $checkStudentQuery->fetchAll(PDO::FETCH_ASSOC);

    // Map student IDs to their departments
    $studentDeptMap = [];
    foreach ($students as $student) {
        $studentDeptMap[$student['student_id']] = $student['dept_id'];
    }

    $invalidStudents = [];
    foreach ($student_ids as $student_id) {
        if (!isset($studentDeptMap[$student_id])) {
            $invalidStudents[] = $student_id;
        } elseif ($studentDeptMap[$student_id] != $dept_id) {
            echo "<script>alert('Student ID $student_id does not belong to your department.'); window.location.href='addsportsevent.php';</script>";
            exit;
        }
    }

    if (!empty($invalidStudents)) {
        $invalidList = implode(", ", $invalidStudents);
        echo "<script>alert('Invalid Student IDs: $invalidList'); window.location.href='addsportsevent.php';</script>";
        exit;
    }

    // Check if students are already registered
    $placeholders = [];
    $paramValues = [':event_id' => $event_id];

    foreach ($student_ids as $index => $student_id) {
        $param = ":student_id" . $index;
        $placeholders[] = $param;
        $paramValues[$param] = $student_id;
    }

    $placeholdersString = implode(',', $placeholders);
    $checkParticipantSql = "SELECT student_id FROM participants WHERE event_id = :event_id AND student_id IN ($placeholdersString)";
    $checkParticipantQuery = $dbh->prepare($checkParticipantSql);
    $checkParticipantQuery->execute($paramValues);
    $existingParticipants = $checkParticipantQuery->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($existingParticipants)) {
        $existingList = implode(", ", $existingParticipants);
        echo "<script>alert('Students already registered: $existingList'); window.location.href='addsportsevent.php';</script>";
        exit;
    }

    // Ensure only one captain per event
    if ($captain_id) {
        if (!in_array($captain_id, $student_ids)) {
            echo "<script>alert('Captain must be from the selected participants.'); window.location.href='addsportsevent.php';</script>";
            exit;
        }

        $checkCaptainSql = "SELECT student_id FROM participants WHERE event_id = :event_id AND is_captain = 1";
        $checkCaptainQuery = $dbh->prepare($checkCaptainSql);
        $checkCaptainQuery->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $checkCaptainQuery->execute();
        $existingCaptain = $checkCaptainQuery->fetchColumn();

        if ($existingCaptain && $existingCaptain != $captain_id) {
            echo "<script>alert('A different captain is already assigned. Remove them first.'); window.location.href='addsportsevent.php';</script>";
            exit;
        }
    }

    // Insert participants
    $sql = "INSERT INTO participants (event_id, student_id, dept_id, is_captain) VALUES (:event_id, :student_id, :dept_id, :is_captain)";
    $query = $dbh->prepare($sql);

    foreach ($student_ids as $student_id) {
        $is_captain = ($student_id == $captain_id) ? 1 : 0;
        $query->execute([
            ':event_id' => $event_id,
            ':student_id' => $student_id,
            ':dept_id' => $dept_id,
            ':is_captain' => $is_captain
        ]);
    }

    echo "<script>alert('Participants and captain assigned successfully!'); window.location.href='ulscdashboard.php';</script>";
    exit;
}

$query = $dbh->prepare("
    SELECT e.*
    FROM events e
    WHERE e.event_type = 'Sports'
    AND e.id NOT IN (
        SELECT event_id FROM participants 
        WHERE dept_id = :dept_id
    )
    ORDER BY e.event_name ASC
");

$query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
</head>

<body>
    <div class="home-content">
        <?php include_once('../includes/sidebar.php'); ?>
        <div class="home-content">
            <div class="home-page">
                <section class="new-admin">
                    <section>
                        <!-- Add Participant Form -->
                        <form action="addsportsevent.php" method="POST">
                            <label for="eventSelect"><strong>Select Sports Event:</strong></label>
                            <select id="eventSelect" name="event" onchange="showParticipantsForm()" required>
                                <option value="">Select Event...</option>
                                <?php foreach ($events as $event) : ?>
                                    <?php if ($event['event_type'] === 'Sports') : ?>
                                        <option value="<?= $event['id']; ?>" data-min="<?= $event['min_participants']; ?>" data-max="<?= $event['max_participants']; ?>">
                                            <?= htmlspecialchars($event['event_name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>

                            <div id="participantsContainer" style="display:none; margin-top: 15px;">
                                <input type="hidden" id="minParticipants" name="minParticipants" value="">
                                <input type="hidden" id="maxParticipants" name="maxParticipants" value="">
                                <input type="hidden" id="captain_id" name="captain_id">

                                <!-- Table Structure for Participant List -->
                                <table class="participant-table">
                                    <thead>
                                        <tr>
                                            <th>Participant ID</th>
                                            <th>Captain</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="participantFields">
                                        <!-- Participant rows will be added here -->
                                    </tbody>
                                </table>

                                <!-- Add Participant Button -->
                                <button type="button" class="add-btn" onclick="addNewParticipantRow()">+</button>
                            </div>
                            <button type="submit" class="submit-btn">Submit</button>
                        </form>
                    </section>
                </section>
            </div>
        </div>
    </div>

    <?php include_once('../includes/footer.php'); ?>

    <script>
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
            container.innerHTML = "";

            for (let i = 0; i < min; i++) {
                addParticipantField();
            }

            updateAddButtonState(min);
        }
        function addParticipantField() {
    var container = document.getElementById("participantFields");
    var currentCount = container.getElementsByClassName("participant-entry").length;
    var maxParticipants = parseInt(document.getElementById("maxParticipants").value, 10);

    if (currentCount >= maxParticipants) {
        alert(`You cannot add more than ${maxParticipants} participants.`);
        return;
    }

    var newDiv = document.createElement("div");
    newDiv.classList.add("participant-entry");
    newDiv.style.display = "flex";  // Align in row format

    var newInput = document.createElement("input");
    newInput.type = "text";
    newInput.name = "student_id[]";
    newInput.placeholder = "Enter Student ID";
    newInput.classList.add("participant-input");
    newInput.required = true;

    newInput.addEventListener("change", function () {
        if (isDuplicateStudentID(newInput.value)) {
            alert("This student ID has already been added!");
            newInput.value = "";
        } else {
            updateCaptainRadio(newInput);
        }
    });

    var captainContainer = document.createElement("div");
    captainContainer.style.textAlign = "center"; 
    var captainRadio = document.createElement("input");
    captainRadio.type = "radio";
    captainRadio.name = "captain";
    captainRadio.classList.add("captain-radio");

    captainRadio.onclick = function () {
        if (newInput.value.trim() !== "") {
            let existingCaptain = document.querySelector(".captain-radio:checked");
            if (existingCaptain && existingCaptain !== captainRadio) {
                existingCaptain.checked = false;
            }
            document.getElementById("captain_id").value = newInput.value;
        } else {
            alert("Captain must have a valid Student ID.");
            captainRadio.checked = false;
        }
    };
    captainContainer.appendChild(captainRadio);

    var removeButton = document.createElement("button");
    removeButton.innerHTML = "-";
    removeButton.classList.add("remove-btn");
    removeButton.onclick = function () {
        removeParticipantField(newDiv);
    };

    newDiv.appendChild(newInput);
    newDiv.appendChild(captainContainer);
    newDiv.appendChild(removeButton);
    container.appendChild(newDiv);

    updateAddButtonState();
}


function updateCaptainRadio(inputField) {
    var captainRadio = inputField.parentElement.parentElement.querySelector(".captain-radio");
    captainRadio.value = inputField.value.trim(); // Update the radio button's value
}

function addNewParticipantRow() {
    let tableBody = document.getElementById("participantFields");
    let rowCount = tableBody.getElementsByTagName("tr").length;
    let newRow = document.createElement("tr");

    newRow.innerHTML = `
        <td>
            <input type="text" name="student_id[]" class="participant-input" placeholder="Enter Student ID" required oninput="updateCaptainRadio(this)">
        </td>
        <td>
            <input type="radio" name="captain_id" class="captain-radio" value="" onclick="setCaptain(this)">
        </td>
        <td>
            <button type="button" class="remove-btn" onclick="removeRow(this)">-</button>
        </td>
    `;

    tableBody.appendChild(newRow);
}

function isDuplicateStudentID(studentID) {
    if (!studentID.trim()) return false; // Ignore empty values

    let inputs = document.querySelectorAll("input[name='student_id[]']");
    let count = Array.from(inputs).filter(input => input.value === studentID).length;

    return count > 1; // **Return true if duplicate exists, but don't show an alert here**
}

function removeParticipantField(element) {
    var container = document.getElementById("participantFields");
    var minParticipants = parseInt(document.getElementById("minParticipants").value, 10);
    var currentCount = container.getElementsByClassName("participant-entry").length;

    if (currentCount > minParticipants) {
        element.remove();
    } else {
        alert(`You cannot remove participants below the minimum required (${minParticipants}).`);
    }

    updateAddButtonState();
}

        function updateAddButtonState() {
            var container = document.getElementById("participantFields");
            var currentCount = container.getElementsByClassName("participant-entry").length;
            var maxParticipants = parseInt(document.getElementById("maxParticipants").value, 10);
            var addButton = document.querySelector(".add-btn");

            addButton.disabled = currentCount >= maxParticipants;
        }
    </script>
</body>

</html>
