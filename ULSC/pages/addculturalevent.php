<?php
include('../includes/session_management.php');
include('../includes/config.php');

if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ../index.php");
    exit;
}

// Initialize academic_year_id to handle initial page load and form submissions
$academic_year_id = 0;
// Check if academic_year_id was submitted via POST (from the first dropdown change)
if (isset($_POST['academic_year_id'])) {
    $academic_year_id = (int)$_POST['academic_year_id'];
}
// Also check if academic_year_id was submitted via GET (e.g., if you manually add it to the URL)
else if (isset($_GET['academic_year_id'])) {
    $academic_year_id = (int)$_GET['academic_year_id'];
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

// Store ULSC's department ID and name safely
$dept_id = $ulsc['dept_id'];
$ulsc_name = htmlspecialchars($ulsc['ulsc_name']);
$dept_name = htmlspecialchars($ulsc['dept_name']);

// Extract department code from ULSC ID (e.g., "23CE001" -> "CE") for client-side validation
$ulscDeptCode = '';
if (preg_match('/\d{2}([A-Za-z]{2,3})\d{3}/', $ulsc_id, $matches)) {
    $ulscDeptCode = strtoupper($matches[1]);
} else {
    // Fallback or error handling if ULSC ID format is unexpected
    error_log("Error: Could not extract department code from ULSC ID: " . $ulsc_id);
    // You might want to set a default or show an error to the user
}

// Fetch academic years from the database
$academicYears = [];
$yearQuery = $dbh->query("SELECT id, year FROM academic_years ORDER BY year DESC");
if ($yearQuery) {
    $academicYears = $yearQuery->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch events based on the selected academic year (or initially, no year selected)
$events = [];
if ($academic_year_id > 0) {
    $query = $dbh->prepare("
        SELECT e.*
        FROM events e
        WHERE e.event_type = 'Cultural'
        AND e.academic_year_id = :academic_year_id
        AND NOT EXISTS (
            SELECT 1 FROM participants p
            WHERE p.event_id = e.id
            AND p.dept_id = :dept_id
            AND p.academic_year_id = :academic_year_id
        )
        ORDER BY e.event_name ASC
    ");
    $query->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
    $query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
    $query->execute();
    $events = $query->fetchAll(PDO::FETCH_ASSOC);
}

// Determine if an event was selected in the previous POST (for retaining dropdown state and showing participant fields)
// This is crucial for the JavaScript to correctly re-render the participant fields after an academic year/event selection.
$selectedEventId = isset($_POST['event']) ? (int)$_POST['event'] : 0;
// Also, if the page is loaded with an event selected from a GET parameter (less common for this flow, but handles direct links)
if ($selectedEventId === 0 && isset($_GET['event'])) {
    $selectedEventId = (int)$_GET['event'];
}


// --- Participant Submission Logic ---
// This block will execute when the main form (with participants) is submitted
if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_POST['event']) &&
    isset($_POST['student_id']) &&
    is_array($_POST['student_id']) &&
    isset($_POST['academic_year_id_for_submission']) // Hidden field to pass academic year on submission
) {
    $event_id = (int)$_POST['event'];
    // Filter out empty student IDs from the array
    $student_ids = array_filter(array_map('trim', $_POST['student_id']));
    $min_participants = (int)$_POST['minParticipants'];
    $max_participants = (int)$_POST['maxParticipants'];
    $captain_id = $_POST['captain_id'] ?? null;
    $academic_year_id_for_submission = (int)$_POST['academic_year_id_for_submission'];

    // 1. Check if department has already registered for this event in this academic year
    $checkEventSql = "SELECT COUNT(*) FROM participants WHERE event_id = :event_id AND dept_id = :dept_id AND academic_year_id = :academic_year_id";
    $checkEventQuery = $dbh->prepare($checkEventSql);
    $checkEventQuery->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $checkEventQuery->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
    $checkEventQuery->bindParam(':academic_year_id', $academic_year_id_for_submission, PDO::PARAM_INT);
    $checkEventQuery->execute();
    $hasRegistered = (int)$checkEventQuery->fetchColumn() > 0;

    if ($hasRegistered) {
        echo "<script>alert('Your department has already registered for this event in the selected academic year.'); window.location.href='addculturalevent.php';</script>";
        exit;
    }

    // 2. Validate participant count
    // The previous check for count() on null is handled by array_filter and is_array check above.
    if (count($student_ids) < $min_participants || count($student_ids) > $max_participants) {
        echo "<script>alert('Number of participants must be between $min_participants and $max_participants.'); window.location.href='addculturalevent.php';</script>";
        exit;
    }

    // 3. Validate captain selection
    if (!$captain_id || !in_array($captain_id, $student_ids)) {
        echo "<script>alert('Please select a valid captain from the participants.'); window.location.href='addculturalevent.php';</script>";
        exit;
    }

    // 4. Batch fetch student details and validate department affiliation
    // Ensure student_ids array is not empty before proceeding to avoid issues with IN clause
    if (!empty($student_ids)) {
        $placeholders = [];
        $paramValues = [];
        foreach ($student_ids as $index => $student_id) {
            // Sanitize student_id before using in SQL (basic trim and uppercase)
            $sanitized_student_id = trim(strtoupper($student_id));
            if (empty($sanitized_student_id)) {
                echo "<script>alert('Empty student ID submitted.'); window.location.href='addculturalevent.php';</script>";
                exit;
            }
            $param = ":student_id" . $index;
            $placeholders[] = $param;
            $paramValues[$param] = $sanitized_student_id;
        }

        $placeholdersString = implode(',', $placeholders);
        $checkStudentSql = "SELECT student_id, dept_id FROM student WHERE student_id IN ($placeholdersString)";
        $checkStudentQuery = $dbh->prepare($checkStudentSql);
        $checkStudentQuery->execute($paramValues);
        $students = $checkStudentQuery->fetchAll(PDO::FETCH_ASSOC);

        $studentDeptMap = [];
        foreach ($students as $student) {
            $studentDeptMap[$student['student_id']] = $student['dept_id'];
        }

        foreach ($student_ids as $student_id) {
            // Use the sanitized student ID for checks
            $sanitized_student_id = trim(strtoupper($student_id));
            if (!isset($studentDeptMap[$sanitized_student_id])) {
                echo "<script>alert('Student ID $sanitized_student_id not found in the student database.'); window.location.href='addculturalevent.php';</script>";
                exit;
            } elseif ($studentDeptMap[$sanitized_student_id] != $dept_id) {
                echo "<script>alert('Student ID $sanitized_student_id does not belong to your department.'); window.location.href='addculturalevent.php';</script>";
                exit;
            }
        }
    } else {
        // This case should ideally be caught by $num_participants < $min_participants earlier,
        // but as a fallback for robustness.
        echo "<script>alert('No valid student IDs submitted.'); window.location.href='addculturalevent.php';</script>";
        exit;
    }


    // 5. Check for duplicate student IDs within the submitted list (redundant after client-side, but good for server-side safety)
    if (count(array_unique($student_ids)) !== count($student_ids)) {
        echo "<script>alert('Duplicate student IDs found in your submission.'); window.location.href='addculturalevent.php';</script>";
        exit;
    }

    // 6. Check if any submitted students are already registered for this event and academic year
    if (!empty($student_ids)) { // Ensure student_ids array is not empty
        $placeholders = [];
        $paramValues = [':event_id' => $event_id, ':academic_year_id' => $academic_year_id_for_submission];

        foreach ($student_ids as $index => $student_id) {
            $param = ":student_id" . $index;
            $placeholders[] = $param;
            $paramValues[$param] = $student_id;
        }

        $placeholdersString = implode(',', $placeholders);
        $checkParticipantSql = "SELECT student_id FROM participants WHERE event_id = :event_id AND academic_year_id = :academic_year_id AND student_id IN ($placeholdersString)";
        $checkParticipantQuery = $dbh->prepare($checkParticipantSql);
        $checkParticipantQuery->execute($paramValues);
        $existingParticipants = $checkParticipantQuery->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($existingParticipants)) {
            $existingList = implode(", ", $existingParticipants);
            echo "<script>alert('Students already registered for this event in the selected academic year: $existingList'); window.location.href='addculturalevent.php';</script>";
            exit;
        }
    }

    // Start a transaction for atomicity
    $dbh->beginTransaction();
    try {
        // Insert participants
        $sql = "INSERT INTO participants (event_id, student_id, dept_id, is_captain, academic_year_id)
                VALUES (:event_id, :student_id, :dept_id, :is_captain, :academic_year_id)";
        $query = $dbh->prepare($sql);

        foreach ($student_ids as $student_id) {
            $is_captain = ($student_id == $captain_id) ? 1 : 0;
            $query->execute([
                ':event_id' => $event_id,
                ':student_id' => $student_id,
                ':dept_id' => $dept_id,
                ':is_captain' => $is_captain,
                ':academic_year_id' => $academic_year_id_for_submission
            ]);
        }
        $dbh->commit();
        echo "<script>alert('Participants and captain assigned successfully!'); window.location.href='ulscdashboard.php';</script>";
        exit;
    } catch (PDOException $e) {
        $dbh->rollBack();
        error_log("Database Error: " . $e->getMessage()); // Log the actual error
        echo "<script>alert('An error occurred during registration. Please try again. Error: " . $e->getMessage() . "'); window.location.href='addculturalevent.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cultural Event Entry - Spoural Management System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    </head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>

    <div class="home-content">
        <div class="participant-entry-container">
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-palette'></i> Cultural Event Entry</h2>
                    <div>
                        <span class="dept-badge"><?php echo htmlspecialchars($dept_name); ?></span>
                    </div>
                </div>

                <form action="addculturalevent.php" method="POST" class="participant-form" id="mainEntryForm">
                    <div class="form-group">
                        <div style="margin-top: 10px;">
                            <label for="academicYear" class="form-label">Academic Year: </label>

                            <select id="academicYear" name="academic_year_id" class="form-select" onchange="this.form.submit();">
                                <option value="">Select Academic year</option>
                                <?php foreach ($academicYears as $year): ?>
                                    <option value="<?= $year['id'] ?>" <?= ($academic_year_id == $year['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($year['year']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <br>
                        <label for="eventSelect" class="form-label">Select Cultural Event:</label>
                        <select id="eventSelect" name="event" class="form-select" onchange="showParticipantsForm();" required>
                            <option value="">Select Event...</option>
                            <?php
                            foreach ($events as $event) :
                            ?>
                                <option value="<?= $event['id']; ?>"
                                        data-min="<?= $event['min_participants']; ?>"
                                        data-max="<?= $event['max_participants']; ?>"
                                        <?= ($selectedEventId === $event['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($event['event_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="participantsContainer" style="display:none; margin-top: 25px;">
                        <input type="hidden" id="minParticipants" name="minParticipants" value="">
                        <input type="hidden" id="maxParticipants" name="maxParticipants" value="">
                        <input type="hidden" id="captain_id" name="captain_id">
                        <input type="hidden" id="academic_year_id_for_submission" name="academic_year_id_for_submission" value="<?= $academic_year_id ?>">

                        <div class="participants-info">
                            <div class="alert alert-info">
                                <i class='bx bx-info-circle'></i> Please enter <span id="requiredCount"></span> participants. Select one participant as team captain.
                            </div>
                        </div>

                        <table class="participants-table">
                            <thead>
                                <tr>
                                    <th>Participant ID</th>
                                    <th width="100">Captain</th>
                                    <th width="80">Action</th>
                                </tr>
                            </thead>
                            <tbody id="participantFields">
                                </tbody>
                        </table>

                        <div class="add-participant-btn" onclick="addNewParticipantRow()">
                            <i class='bx bx-plus-circle'></i> Add Participant
                        </div>
                    </div>

                    <div class="submit-container">
                        <button type="submit" class="btn btn-primary" id="submit_button" disabled>Submit Entry</button>
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

        // Function to display the participants form when an event is selected
        function showParticipantsForm() {
            let select = document.getElementById("eventSelect");
            let participantsContainer = document.getElementById("participantsContainer");

            if (select.value) {
                let selectedOption = select.options[select.selectedIndex];
                let minParticipants = parseInt(selectedOption.getAttribute("data-min"));
                let maxParticipants = parseInt(selectedOption.getAttribute("data-max"));

                document.getElementById("minParticipants").value = minParticipants;
                document.getElementById("maxParticipants").value = maxParticipants;
                document.getElementById("requiredCount").textContent = `${minParticipants}-${maxParticipants}`; // Updates info message

                participantsContainer.style.display = "block";

                // Clear existing rows before adding new ones
                let tableBody = document.getElementById("participantFields");
                tableBody.innerHTML = "";

                // Add minimum number of rows
                for (let i = 0; i < minParticipants; i++) {
                    addNewParticipantRow();
                }

                // Update button visibility (primarily for the Add Participant button and individual delete buttons)
                updateButtonVisibility();

                // Attempt to auto-select the first participant as captain if possible
                checkAndAutoSelectCaptain();
                validateAllParticipants(); // Re-validate after adding initial rows
            } else {
                participantsContainer.style.display = "none";
                document.getElementById("submit_button").disabled = true; // Disable submit if no event is selected
            }
        }

        // Function to add a new participant row
        function addNewParticipantRow() {
            let tableBody = document.getElementById("participantFields");
            let rowCount = tableBody.children.length;
            let maxParticipants = parseInt(document.getElementById("maxParticipants").value);

            // Prevent adding more rows than maxParticipants
            if (rowCount >= maxParticipants) {
                alert(`Maximum ${maxParticipants} participants allowed.`);
                return;
            }

            let newRow = document.createElement("tr");
            newRow.innerHTML = `
                <td>
                    <input type="text" name="student_id[]" class="student-id-input" placeholder="Enter Student ID" required
                           oninput="updateCaptainRadio(this); validateAllParticipants();"
                           onblur="checkDuplicateID(this); validateParticipantID(this, '<?= $ulscDeptCode ?>'); validateAllParticipants();">
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

            // Update the add button visibility and ensure remove buttons are visible for removable rows
            updateButtonVisibility();

            // If this is the first row being added and no captain is set, auto-select it once ID is entered
            // This makes the initial UX smoother.
            if (rowCount === 0 && !document.querySelector(".captain-radio:checked")) {
                let input = newRow.querySelector(".student-id-input");
                let radio = newRow.querySelector("input[type='radio']");
                // Add a small delay or check for input value to ensure radio is set only if ID is entered
                input.addEventListener("input", function() {
                    if (this.value.trim() !== "" && !document.querySelector(".captain-radio:checked")) { // Re-check to avoid overwriting a manually selected captain
                        radio.checked = true;
                        setCaptain(radio);
                    }
                });
            }
            validateAllParticipants(); // Re-validate after adding a new row
        }

        // Function to check for duplicate student IDs within the current form
        function checkDuplicateID(inputField) {
            let studentID = inputField.value.trim().toUpperCase();
            if (!studentID) return;

            let inputs = document.querySelectorAll(".student-id-input");
            let count = 0;

            for (let input of inputs) {
                if (input !== inputField && input.value.trim().toUpperCase() === studentID) {
                    count++;
                }
            }

            if (count > 0) {
                inputField.style.border = "2px solid red";
                inputField.setCustomValidity("This student ID has already been added!");
                inputField.reportValidity();
            } else {
                // Clear custom validity if no duplicate (don't clear if other validations apply)
                if (!inputField.validationMessage || inputField.validationMessage === "This student ID has already been added!") {
                     inputField.setCustomValidity("");
                }
            }
        }

        // Function to update the captain radio button's value attribute with the student ID
        function updateCaptainRadio(inputField) {
            let row = inputField.closest("tr");
            let radioButton = row.querySelector(".captain-radio");
            radioButton.value = inputField.value.trim();
        }

        // Function to auto-select the first valid participant as captain
        function checkAndAutoSelectCaptain() {
            let tableBody = document.getElementById("participantFields");
            let rows = tableBody.getElementsByTagName("tr");

            let currentCaptainRadio = document.querySelector(".captain-radio:checked");
            let currentCaptainId = currentCaptainRadio ? currentCaptainRadio.value : '';

            // If no captain is currently selected OR the current captain's input is empty/invalid
            if (!currentCaptainRadio || currentCaptainId === '') {
                if (rows.length > 0) {
                    let firstStudentInput = rows[0].querySelector(".student-id-input");
                    let firstRadioButton = rows[0].querySelector(".captain-radio");

                    if (firstStudentInput && firstStudentInput.value.trim() !== "") {
                        firstRadioButton.checked = true;
                        setCaptain(firstRadioButton); // Update hidden field via setCaptain
                    } else {
                        document.getElementById("captain_id").value = ""; // Clear if first input is empty
                    }
                } else {
                    document.getElementById("captain_id").value = ""; // No participants, no captain
                }
            }
        }

        // Function to remove a participant row (RESTORED)
        function removeRow(button) {
            let row = button.closest("tr");
            let tableBody = document.getElementById("participantFields");
            let minParticipants = parseInt(document.getElementById("minParticipants").value, 10);
            let currentCount = tableBody.getElementsByTagName("tr").length;
            let removedStudentId = row.querySelector(".student-id-input").value.trim();
            let captainIdInput = document.getElementById("captain_id");

            // Allow removal only if current count is greater than minimum participants
            if (currentCount > minParticipants) {
                // If the removed row's student was the captain, clear captain_id
                if (removedStudentId === captainIdInput.value) {
                    captainIdInput.value = ""; // Clear the hidden captain ID
                }
                row.remove();
                updateButtonVisibility(); // Update button visibility after removal
                // Re-evaluate captain selection if the removed participant was the captain, or if no captain is selected
                if (removedStudentId === captainIdInput.value || captainIdInput.value === "") {
                    checkAndAutoSelectCaptain();
                }
                validateAllParticipants(); // Validate after removing a row
            } else {
                alert(`You cannot remove participants below the minimum required (${minParticipants}).`);
            }
        }

        // Function to set the captain based on radio button click
        function setCaptain(radio) {
            let row = radio.closest("tr");
            let studentInput = row.querySelector(".student-id-input");

            if (studentInput.value.trim() === "") {
                alert("Captain must have a valid Student ID.");
                radio.checked = false;
                document.getElementById("captain_id").value = ""; // Ensure hidden captain_id is cleared
                return;
            }

            // Uncheck all other captain radios
            document.querySelectorAll(".captain-radio").forEach(r => r.checked = false);
            radio.checked = true; // Check the clicked one

            document.getElementById("captain_id").value = studentInput.value.trim(); // Assign captain ID to hidden field
            validateAllParticipants(); // Re-validate after setting captain
        }

        // Function to update the visibility of add/remove buttons (MODIFIED TO SHOW/HIDE REMOVE BUTTONS)
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
                    // Hide remove button if current count is at or below minimum required participants
                    removeBtn.style.display = (rowCount <= minParticipants) ? "none" : "inline-block";
                }
            }
        }

        // Client-side Student ID format and department validation
        function validateParticipantID(inputField, ulscDept) {
            let studentID = inputField.value.toUpperCase().trim();
            let studentMatch = studentID.match(/^\d{2}([A-Z]{2,3})\d{3}$/); // Regex for YYDEPTNNN (2 or 3 letters for DEPT)

            if (!studentMatch) {
                inputField.style.border = "2px solid red";
                inputField.setCustomValidity("Invalid Student ID format! (e.g., YYDEPTNNN)");
            } else {
                let studentDept = studentMatch[1]; // Extracted department code

                if (studentDept !== ulscDept.toUpperCase()) {
                    inputField.style.border = "2px solid red";
                    inputField.setCustomValidity(
                        `This student ID (${studentID}) does not belong to your department (${ulscDept.toUpperCase()})!`
                    );
                } else {
                    inputField.style.border = "2px solid green"; // Indicate valid format and department
                    inputField.setCustomValidity(""); // Clear custom validity if valid
                }
            }
            inputField.reportValidity(); // Show validity message to the user
            validateAllParticipants(); // Re-validate entire form after this input changes
        }

        // Validates all participant inputs and controls the submit button's disabled state
        function validateAllParticipants() {
            let participantInputs = document.querySelectorAll(".student-id-input");
            let submitButton = document.getElementById("submit_button");
            let minParticipants = parseInt(document.getElementById("minParticipants").value, 10);
            let maxParticipants = parseInt(document.getElementById("maxParticipants").value, 10);

            let hasError = false;
            let filledCount = 0;
            let studentIds = []; // To check for duplicates during this validation (case-insensitive)

            participantInputs.forEach(input => {
                let id = input.value.trim().toUpperCase(); // Convert to uppercase for consistent checking
                if (id === "") {
                    hasError = true; // Mark error for empty required fields
                } else {
                    filledCount++;
                    if (studentIds.includes(id)) {
                        hasError = true; // Mark error for duplicate student IDs
                        input.style.border = "2px solid red"; // Highlight duplicate
                        input.setCustomValidity("Duplicate Student ID in form!");
                    } else {
                        studentIds.push(id);
                        // If it was a duplicate and now unique, clear custom validity (unless other errors exist)
                        if (input.validationMessage === "Duplicate Student ID in form!") {
                            input.setCustomValidity("");
                            if (!input.validationMessage) { // Only clear border if no other validity issues
                                input.style.border = "2px solid green";
                            }
                        }
                    }
                }
                // Check for any existing custom validation error (e.g., from validateParticipantID)
                if (input.validationMessage) {
                    hasError = true;
                }
            });

            // Check if a captain is selected and is one of the entered participants
            let captainId = document.getElementById("captain_id").value.toUpperCase(); // Convert to uppercase for consistent checking
            if (!captainId || !studentIds.includes(captainId)) {
                hasError = true; // No captain or invalid captain
            }

            // Check if minimum number of participants are entered/filled
            if (filledCount < minParticipants || filledCount > maxParticipants) {
                hasError = true;
            }

            if (submitButton) {
                submitButton.disabled = hasError;
            }
        }

        // Event listener for DOMContentLoaded to set up initial state and event handlers
        document.addEventListener("DOMContentLoaded", function() {
            // Extract ULSC Department Code from PHP variable
            const ulscDeptCode = '<?= $ulscDeptCode ?>'; // Using the PHP variable directly

            // Add form submission validation for the main form
            document.getElementById("mainEntryForm").addEventListener("submit", function(event) {
                validateAllParticipants(); // Re-run the comprehensive validation just before submission
                if (document.getElementById("submit_button").disabled) {
                    alert("Please correct all validation errors before submitting.");
                    event.preventDefault(); // Prevent form submission if there are errors
                    return false;
                }

                // Final check for empty student IDs that might pass through other validations
                let inputs = document.querySelectorAll(".student-id-input");
                for (let input of inputs) {
                    if (input.value.trim() === "") {
                        alert("All participant ID fields must be filled.");
                        input.focus();
                        event.preventDefault();
                        return false;
                    }
                }
                return true; // Allow form submission
            });

            // Initial setup for the form if an academic year or event is pre-selected (e.g., after a POST)
            let academicYearSelect = document.getElementById("academicYear");
            let eventSelect = document.getElementById("eventSelect");

            // If an academic year is selected AND an event is pre-selected (from PHP via $selectedEventId)
            if (academicYearSelect.value && eventSelect.value == '<?= $selectedEventId ?>' && '<?= $selectedEventId ?>' !== '0') {
                showParticipantsForm(); // Trigger the function to display participant fields
            } else if (academicYearSelect.value) {
                // If only academic year is selected, but no event is pre-selected
                // Ensure participant container is hidden and submit button is disabled.
                document.getElementById("participantsContainer").style.display = "none";
                document.getElementById("submit_button").disabled = true;
            } else {
                // If no academic year is selected, hide participants and disable submit
                document.getElementById("participantsContainer").style.display = "none";
                document.getElementById("submit_button").disabled = true;
            }

            // Call initial validation to set the submit button state correctly on page load
            validateAllParticipants();
        });
    </script>
</body>
</html> 