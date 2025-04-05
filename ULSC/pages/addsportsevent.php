<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch ULSC Member's Department ID
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
    session_destroy();
    header("Location: ../index.php?error=invalid_session");
    exit();
}

// Store ULSC's department ID safely
$dept_id = $ulsc['dept_id'];
$ulsc_name = htmlspecialchars($ulsc['ulsc_name']);
$dept_name = htmlspecialchars($ulsc['dept_name']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event'];
    $student_ids = $_POST['student_id'];
    $min_participants = (int)$_POST['minParticipants'];
    $max_participants = (int)$_POST['maxParticipants'];
    $captain_id = $_POST['captain_id'] ?? null;

    // Check if department has already registered for this event
    $checkEventSql = "SELECT COUNT(*) FROM participants WHERE event_id = :event_id AND dept_id = :dept_id";
    $checkEventQuery = $dbh->prepare($checkEventSql);
    $checkEventQuery->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $checkEventQuery->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
    $checkEventQuery->execute();
    $hasRegistered = (int)$checkEventQuery->fetchColumn() > 0;

    if ($hasRegistered) {
        echo "<script>alert('Your department has already registered for this event.'); window.location.href='addsportsevent.php';</script>";
        exit;
    }

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
    
    // Ensure only one captain per event per department
    if ($captain_id) {
        if (!in_array($captain_id, $student_ids)) {
            echo "<script>alert('Captain must be from the selected participants.'); window.location.href='addsportsevent.php';</script>";
            exit;
        }

        $checkCaptainSql = "SELECT student_id FROM participants WHERE event_id = :event_id AND dept_id = :dept_id AND is_captain = 1";
        $checkCaptainQuery = $dbh->prepare($checkCaptainSql);
        $checkCaptainQuery->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $checkCaptainQuery->bindParam(':dept_id', $dept_id, PDO::PARAM_INT); // Add department filter
        $checkCaptainQuery->execute();
        $existingCaptain = $checkCaptainQuery->fetchColumn();

        if ($existingCaptain && $existingCaptain != $captain_id) {
            echo "<script>alert('A different captain is already assigned for your department. Remove them first.'); window.location.href='addsportsevent.php';</script>";
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
    AND NOT EXISTS (
        SELECT 1 FROM participants p 
        WHERE p.event_id = e.id 
        AND p.dept_id = :dept_id
    )
    ORDER BY e.event_name ASC
");

$query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
$query->execute();
$events = $query->fetchAll(PDO::FETCH_ASSOC);

// Debug event data
echo "<!-- DEBUG: Found " . count($events) . " available sports events -->";
if (count($events) == 0) {
    echo "<script>console.log('No available sports events found for your department!');</script>";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sports Event Entry - Spoural Management System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>
        
    <div class="home-content">
        <div class="participant-entry-container">
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-football'></i> Sports Event Entry</h2>
                    <div>
                        <span class="dept-badge"><?php echo htmlspecialchars($dept_name); ?></span>
                    </div>
                </div>
                
                <form action="addsportsevent.php" method="POST" class="participant-form">
                    <div class="form-group">
                        <label for="eventSelect" class="form-label">Select Sports Event:</label>
                        <select id="eventSelect" name="event" class="form-select" onchange="showParticipantsForm(); console.log('Event changed');" required>
                            <option value="">Select Event...</option>
                            <?php foreach ($events as $event) : ?>
                                <option value="<?= $event['id']; ?>" 
                                        data-min="<?= $event['min_participants']; ?>" 
                                        data-max="<?= $event['max_participants']; ?>">
                                    <?= htmlspecialchars($event['event_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="participantsContainer" style="display:none; margin-top: 25px;">
                        <input type="hidden" id="minParticipants" name="minParticipants" value="">
                        <input type="hidden" id="maxParticipants" name="maxParticipants" value="">
                        <input type="hidden" id="captain_id" name="captain_id">
                        
                        <div class="participants-info">
                            <div class="alert alert-info">
                                <i class='bx bx-info-circle'></i> Please enter <span id="requiredCount"></span> participants. Select one participant as team captain.
                            </div>
                        </div>

                        <!-- Table Structure for Participant List -->
                        <table class="participants-table">
                            <thead>
                                <tr>
                                    <th>Participant ID</th>
                                    <th width="100">Captain</th>
                                    <th width="80">Action</th>
                                </tr>
                            </thead>
                            <tbody id="participantFields">
                                <!-- Participant rows will be added here -->
                            </tbody>
                        </table>

                        <!-- Add Participant Button -->
                        <div class="add-participant-btn" onclick="addNewParticipantRow()">
                            <i class='bx bx-plus-circle'></i> Add Participant
                        </div>
                    </div>
                    
                    <div class="submit-container">
                        <button type="submit" class="btn btn-primary">Submit Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include_once('../includes/footer.php'); ?>

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
    
function showParticipantsForm() {
    let select = document.getElementById("eventSelect");
    let participantsContainer = document.getElementById("participantsContainer");
    
    console.log("showParticipantsForm called, select value:", select.value);
    
    if (select.value) {
        let selectedOption = select.options[select.selectedIndex];
        let minParticipants = selectedOption.getAttribute("data-min");
        let maxParticipants = selectedOption.getAttribute("data-max");
        
        console.log("Selected event:", select.options[select.selectedIndex].text);
        console.log("Min participants:", minParticipants);
        console.log("Max participants:", maxParticipants);
        
        document.getElementById("minParticipants").value = minParticipants;
        document.getElementById("maxParticipants").value = maxParticipants;
        document.getElementById("requiredCount").textContent = `${minParticipants}-${maxParticipants}`;
        
        participantsContainer.style.display = "block";
        
        // Clear existing rows
        let tableBody = document.getElementById("participantFields");
        tableBody.innerHTML = "";
        
        // Add minimum number of rows
        for (let i = 0; i < minParticipants; i++) {
            addNewParticipantRow();
        }
        
        // Update button visibility after adding rows
        updateButtonVisibility();
        
        // Select first participant as captain by default
        checkAndAutoSelectCaptain();
    } else {
        participantsContainer.style.display = "none";
    }
}

function addNewParticipantRow() {
    let tableBody = document.getElementById("participantFields");
    let rowCount = tableBody.children.length;
    let maxParticipants = parseInt(document.getElementById("maxParticipants").value);

    if (rowCount >= maxParticipants) {
        alert(`Maximum ${maxParticipants} participants allowed.`);
        return;
    }

    let newRow = document.createElement("tr");
    newRow.innerHTML = `
        <td>
            <input type="text" name="student_id[]" class="student-id-input" placeholder="Enter Student ID" required oninput="updateCaptainRadio(this)" onblur="checkDuplicateID(this)">
        </td>
        <td>
            <label class="custom-radio">
                <input type="radio" name="captain_id" value="" onclick="setCaptain(this)" class="captain-radio">
                <span class="radio-checkmark"></span>
            </label>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-round" onclick="removeRow(this)">
                <i class='bx bx-trash'></i>
            </button>
        </td>
    `;

    tableBody.appendChild(newRow);

    // Update the add button visibility
    updateButtonVisibility();

    // If this is the first row being added, ensure it will be selected as captain once data is entered
    if (rowCount === 0) {
        let input = newRow.querySelector(".student-id-input");
        let radio = newRow.querySelector("input[type='radio']");
        input.addEventListener("input", function() {
            if (this.value.trim() !== "") {
                radio.checked = true;
                setCaptain(radio);
            }
        });
    }
}

function checkDuplicateID(inputField) {
    let studentID = inputField.value.trim();
    if (!studentID) return;
    
    let inputs = document.querySelectorAll(".student-id-input");
    let count = 0;
    
    for (let input of inputs) {
        if (input.value.trim() === studentID) {
            count++;
            if (count > 1) {
                alert("This student ID has already been added!");
                inputField.value = "";
                return;
            }
        }
    }
}

function updateCaptainRadio(inputField) {
    let row = inputField.closest("tr");
    let radioButton = row.querySelector(".captain-radio");
    radioButton.value = inputField.value.trim(); // Set the value of the radio button to the entered student ID
    
    let tableBody = document.getElementById("participantFields");
    let rows = tableBody.getElementsByTagName("tr");
    
    // If this is the first participant and no captain is selected yet, select as captain
    if (row === rows[0] && !document.querySelector(".captain-radio:checked") && inputField.value.trim() !== "") {
        radioButton.checked = true;
        document.getElementById("captain_id").value = inputField.value.trim();
    }
    
    // If only one participant, always auto-select as captain
    if (rows.length === 1 && inputField.value.trim() !== "") {
        radioButton.checked = true;
        document.getElementById("captain_id").value = inputField.value.trim();
    }
}

// Add this function to your existing script
function checkAndAutoSelectCaptain() {
    let tableBody = document.getElementById("participantFields");
    let rows = tableBody.getElementsByTagName("tr");
    
    if (rows.length > 0) {
        // Always select the first participant as captain
        let radioButton = rows[0].querySelector(".captain-radio");
        let studentInput = rows[0].querySelector(".student-id-input");
        
        if (studentInput.value.trim() !== "") {
            radioButton.checked = true;
            document.getElementById("captain_id").value = studentInput.value.trim();
        } else {
            // Set up an event listener to assign captain when ID is entered
            studentInput.addEventListener("input", function() {
                if (this.value.trim() !== "") {
                    radioButton.checked = true;
                    document.getElementById("captain_id").value = this.value.trim();
                }
            });
        }
    }
}

function removeRow(button) {
    let row = button.closest("tr");
    let tableBody = document.getElementById("participantFields");
    let minParticipants = parseInt(document.getElementById("minParticipants").value, 10);
    let currentCount = tableBody.getElementsByTagName("tr").length;

    if (currentCount > minParticipants) {
        row.remove();
        updateButtonVisibility();
    } else {
        alert(`You cannot remove participants below the minimum required (${minParticipants}).`);
    }
}

function setCaptain(radio) {
    let row = radio.closest("tr");
    let studentInput = row.querySelector(".student-id-input");

    if (studentInput.value.trim() === "") {
        alert("Captain must have a valid Student ID.");
        radio.checked = false;
        return;
    }

    document.querySelectorAll(".captain-radio").forEach(r => r.checked = false);
    radio.checked = true;

    document.getElementById("captain_id").value = studentInput.value.trim(); // Assign captain ID
}

function generateParticipantFields(min, max) {
    var container = document.getElementById("participantFields");
    container.innerHTML = "";

    for (let i = 0; i < min; i++) {
        addNewParticipantRow();
    }
    
    // After generating initial fields, update button visibility
    updateButtonVisibility();
    
    // Always select first participant as captain
    checkAndAutoSelectCaptain();
}

function updateButtonVisibility() {
    let tableBody = document.getElementById("participantFields");
    let rows = tableBody.getElementsByTagName("tr");
    let rowCount = rows.length;
    let minParticipants = parseInt(document.getElementById("minParticipants").value, 10);
    let maxParticipants = parseInt(document.getElementById("maxParticipants").value, 10);
    
    // Update add button visibility
    let addButton = document.querySelector(".add-participant-btn");
    if (addButton) {
        addButton.style.display = rowCount >= maxParticipants ? "none" : "inline-block";
    }
    
    // Update remove buttons visibility
    for (let i = 0; i < rowCount; i++) {
        let removeBtn = rows[i].querySelector(".btn-danger");
        if (removeBtn) {
            // Hide remove button for the first 'minParticipants' rows
            removeBtn.style.display = (rowCount <= minParticipants || i < minParticipants) ? "none" : "inline-block";
        }
    }
}

function validateParticipantID(inputField, ulscDept) {
    let studentID = inputField.value.toUpperCase().trim(); // Convert to uppercase for consistency
    let studentMatch = studentID.match(/\d{2}([A-Z]{2,3})\d{3}/);

    if (!studentMatch) {
        inputField.style.border = "2px solid red";
        inputField.setCustomValidity("Invalid Student ID format!");
        inputField.reportValidity();
    } else {
        let studentDept = studentMatch[1]; // Extracted department code

        if (studentDept !== ulscDept.toUpperCase()) {
            inputField.style.border = "2px solid red";
            inputField.setCustomValidity(
                `This student ID (${studentID}) does not belong to your department (${ulscDept.toUpperCase()})!`
            );
            inputField.reportValidity();
            setTimeout(() => alert(`This student ID (${studentID}) does not belong to your department (${ulscDept.toUpperCase()})!`), 100);
            inputField.value = ""; // Clear invalid entry
        } else {
            inputField.style.border = "2px solid green";
            inputField.setCustomValidity("");
        }
    }
    validateAllParticipants();
}

function validateAllParticipants() {
    let participantInputs = document.getElementsByClassName("participant_id");
    let hasError = false;

    for (let input of participantInputs) {
        if (input.style.border === "2px solid red" || input.value.trim() === "") {
            hasError = true;
            break;
        }
    }

    let errorMessage = document.getElementById("error_message");
    let submitButton = document.getElementById("submit_button");
    
    if (errorMessage && submitButton) {
        if (hasError) {
            errorMessage.innerText = "One or more participant IDs have an incorrect department!";
            submitButton.disabled = true;
        } else {
            errorMessage.innerText = "";
            submitButton.disabled = false;
        }
    }
}

function isDuplicateStudentID(studentID) {
    if (!studentID) return false;
    
    let inputs = document.querySelectorAll(".student-id-input");
    let count = 0;
    
    for (let input of inputs) {
        if (input.value.trim() === studentID) {
            count++;
            if (count > 1) return true;
        }
    }
    
    return false;
}

// Add a function to validate the entire form before submission
document.addEventListener("DOMContentLoaded", function() {
    // Add form submission validation
    document.querySelector("form.participant-form").addEventListener("submit", function(event) {
        // Check for duplicate student IDs
        let studentIds = [];
        let hasDuplicates = false;
        let inputs = document.querySelectorAll(".student-id-input");
        
        inputs.forEach(input => {
            let id = input.value.trim();
            if (id !== "" && studentIds.includes(id)) {
                hasDuplicates = true;
                input.style.border = "2px solid red";
            } else if (id !== "") {
                studentIds.push(id);
                input.style.border = "";
            }
        });
        
        if (hasDuplicates) {
            alert("Please remove duplicate student IDs before submitting.");
            event.preventDefault();
            return false;
        }
        
        // Ensure captain is selected
        let captainId = document.getElementById("captain_id").value;
        if (!captainId || !studentIds.includes(captainId)) {
            alert("Please select a valid captain from the participants.");
            event.preventDefault();
            return false;
        }
        
        return true;
    });
    
    let ulscID = <?php echo json_encode($ulsc_id); ?>.toLowerCase().trim();
    let ulscDeptMatch = ulscID.match(/\d{2}([a-z]{2})\d{3}/);

    if (!ulscDeptMatch) {
        console.error("Invalid ULSC ID format!");
        return;
    }

    let ulscDept = ulscDeptMatch[1].toLowerCase(); // Extract department code

    let participantContainer = document.getElementById("participantFields");
    let submitButton = document.getElementById("submit_button");
    let errorMessage = document.getElementById("error_message");

    if (!participantContainer) {
        console.error("Participant fields container not found!");
        return;
    }

    // Event listener for participant ID validation
    participantContainer.addEventListener("input", function (event) {
        if (event.target && event.target.classList.contains("participant_id")) {
            validateParticipantID(event.target, ulscDept);
        }
    });
});

// Add this to ensure the event handlers are properly set up
document.addEventListener("DOMContentLoaded", function() {
    // Make sure event handlers are properly attached
    let eventSelect = document.getElementById("eventSelect");
    if (eventSelect) {
        console.log("Event select found, attaching onchange handler");
        eventSelect.addEventListener("change", showParticipantsForm);
    } else {
        console.error("Event select element not found!");
    }
});

// Force display participants section if an event is selected
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        let eventSelect = document.getElementById("eventSelect");
        if (eventSelect && eventSelect.value) {
            console.log("Force showing participant form for value:", eventSelect.value);
            
            // Get the participants container
            let participantsContainer = document.getElementById("participantsContainer");
            if (participantsContainer) {
                // Get min/max from selected option
                let selectedOption = eventSelect.options[eventSelect.selectedIndex];
                let minParticipants = selectedOption.getAttribute("data-min");
                let maxParticipants = selectedOption.getAttribute("data-max");
                
                if (minParticipants && maxParticipants) {
                    // Set the values
                    document.getElementById("minParticipants").value = minParticipants;
                    document.getElementById("maxParticipants").value = maxParticipants;
                    document.getElementById("requiredCount").textContent = `${minParticipants}-${maxParticipants}`;
                    
                    // Show the container
                    participantsContainer.style.display = "block";
                    
                    // Generate rows
                    let tableBody = document.getElementById("participantFields");
                    tableBody.innerHTML = "";
                    
                    // Force add the minimum participant rows
                    for (let i = 0; i < parseInt(minParticipants); i++) {
                        console.log("Adding row", i + 1);
                        addNewParticipantRow();
                    }
                }
            }
        }
    }, 500); // Delay to ensure DOM is fully loaded
});
    </script>
    </body>

</html>
