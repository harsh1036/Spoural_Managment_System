<?php
require_once __DIR__ . '/SimpleXLSXGen.php';
require_once __DIR__ . '/SimpleXLSX.php';
use Shuchkin\SimpleXLSXGen;
use Shuchkin\SimpleXLSX;

include('../includes/session_management.php');
include('../includes/config.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check database connection
try {
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connected successfully");
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch session data
$admin_username = $_SESSION['login'];

// Initialize variables
$student_id = $student_name = $contact = $dept_id = "";
// Fetch academic years from the database
$academicYears = [];
$yearQuery = $dbh->query("SELECT id, year FROM academic_years ORDER BY year DESC");
if ($yearQuery) {
    $academicYears = $yearQuery->fetchAll(PDO::FETCH_ASSOC); // Now each $year has ['id' => ..., 'year' => ...]
}
// Handle delete operation
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];
        $sql = "DELETE FROM student WHERE student_id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo "<script>alert('Student deleted successfully!'); window.location.href='addstudents.php';</script>";
        } else {
            echo "<script>alert('Error deleting student!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle edit operation - fetch student data
if (isset($_GET['edit_id'])) {
    try {
        $edit_id = $_GET['edit_id'];
        $sql = "SELECT * FROM student WHERE student_id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($student = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $student_id = $student['student_id'];
            $student_name = $student['student_name'];
            $contact = $student['contact'];
            $dept_id = $student['dept_id'];
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle download template
if (isset($_GET['download_template'])) {
    // Create template with expected columns and sample data
    // Note: status field has default value 1 and is not required in import
    // Academic year can be either ID (number) or year name (e.g., "2024-25")
    $data = [
        ['student_id', 'student_name', 'contact', 'dept_id', 'academic_year_id'],
        ['2021001', 'John Doe', '9876543210', '1', '2024-25'],
        ['CS2021002', 'Jane Smith', '9876543211', '2', '1'],
        ['IT2021003', 'Bob Wilson', '9876543212', '3', '2024-25']
    ];
    $xlsx = SimpleXLSXGen::fromArray($data);
    $xlsx->downloadAs('Students_Template.xlsx');
    exit;
}

// Fetch academic_year_id from POST
$academic_year_id = isset($_POST['academic_year_id']) ? trim($_POST['academic_year_id']) : '';

// Handle form submission
// if (isset($_POST['submit'])) {
//     $student_id = $_POST['student_id'];
//     $name = $_POST['name'];
//     $contact = $_POST['contact'];
//     $dept_id = $_POST['department'];
//     $academic_year_id = isset($_POST['academic_year_id']) ? trim($_POST['academic_year_id']) : '';

//     try {
//         // Check if student ID already exists
//         $check_sql = "SELECT student_id FROM student WHERE student_id = :student_id";
//         $check_stmt = $dbh->prepare($check_sql);
//         $check_stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
//         $check_stmt->execute();
        
//         $exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
//         if ($exists) {
//             // Update existing student
//             $sql = "UPDATE student SET student_name = :name, contact = :contact, dept_id = :dept_id, academic_year_id = :academic_year_id WHERE student_id = :student_id";
//             $stmt = $dbh->prepare($sql);
//             $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
//         } else {
//             // Insert new student
//             $sql = "INSERT INTO student (student_id, student_name, contact, dept_id, academic_year_id) VALUES (:student_id, :name, :contact, :dept_id, :academic_year_id)";
//             $stmt = $dbh->prepare($sql);
//             $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
//         }

//         $stmt->bindParam(':name', $name, PDO::PARAM_STR);
//         $stmt->bindParam(':contact', $contact, PDO::PARAM_STR);
//         $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
//         $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);

//         if ($stmt->execute()) {
//             echo "<script>alert('Student saved successfully!'); window.location.href='addstudents.php';</script>";
//         } else {
//             echo "<script>alert('Error saving student!');</script>";
//         }
//     } catch (PDOException $e) {
//         echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
//     }
// }
if (isset($_POST['submit'])) {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $dept_id = $_POST['department'];
    $academic_year_id = $_POST['academic_year_id']; // Get academic year ID from POST

    try {
        // Check if student ID already exists
        $check_sql = "SELECT student_id FROM student WHERE student_id = :student_id";
        $check_stmt = $dbh->prepare($check_sql);
        $check_stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
        $check_stmt->execute();

        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            // Update existing student
            $sql = "UPDATE student SET student_name = :name, contact = :contact, dept_id = :dept_id, academic_year_id = :academic_year_id WHERE student_id = :student_id";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
        } else {
            // Insert new student
            $sql = "INSERT INTO student (student_id, student_name, contact, dept_id, academic_year_id) VALUES (:student_id, :name, :contact, :dept_id, :academic_year_id)";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
        }
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':contact', $contact, PDO::PARAM_STR);
        $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "<script>alert('Student saved successfully!'); window.location.href='addstudents.php';</script>";
        } else {
            echo "<script>alert('Error saving student!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle file import
if (isset($_POST['import'])) {
    if ($_FILES['excel_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];

        if ($xlsx = SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            $expectedColumns = ['student_id', 'student_name', 'contact', 'dept_id', 'status', 'academic_year_id'];

            if ($rows[0] !== $expectedColumns) {
                echo "<script>alert('Error: Column names do not match the expected format! Expected: " . implode(', ', $expectedColumns) . "'); window.location.href='addstudents.php';</script>";
                exit;
            }

            try {
                $dbh->beginTransaction();

                // Fetch valid departments
                $validDeptIds = [];
                $deptQuery = $dbh->query("SELECT dept_id FROM departments");
                while ($dept = $deptQuery->fetch(PDO::FETCH_ASSOC)) {
                    $validDeptIds[] = $dept['dept_id'];
                }

                // Fetch academic years
                $validAcademicYears = [];
                $yearQuery = $dbh->query("SELECT id, year FROM academic_years");
                while ($year = $yearQuery->fetch(PDO::FETCH_ASSOC)) {
                    $validAcademicYears[$year['year']] = $year['id'];
                }

                $errors = [];
                $validRows = [];

                foreach (array_slice($rows, 1) as $index => $row) {
                    if (empty(array_filter($row))) continue;

                    $rowNumber = $index + 2;
                    list($student_id, $student_name, $contact, $dept_id, $status, $academic_year_value) = $row;

                    // Validate required fields
                    if (empty($student_id) || empty($student_name) || empty($dept_id)) {
                        $errors[] = "Row $rowNumber: Required fields missing.";
                        continue;
                    }

                    // Validate student ID length
                    if (strlen($student_id) > 10) {
                        $errors[] = "Row $rowNumber: Student ID must be 10 characters or less.";
                        continue;
                    }

                    // Validate contact number
                    if (!empty($contact) && !preg_match('/^\d{10}$/', $contact)) {
                        $errors[] = "Row $rowNumber: Contact must be 10 digits.";
                        continue;
                    }

                    // Validate department ID
                    if (!in_array($dept_id, $validDeptIds)) {
                        $errors[] = "Row $rowNumber: Invalid department ID $dept_id.";
                        continue;
                    }

                    // Handle status (default = 1)
                    $status = ($status === '' || $status === null) ? 1 : (int)$status;

                    // Validate academic year
                    $academic_year_id = null;
                    if ($academic_year_value !== '' && $academic_year_value !== null) {
                        if (is_numeric($academic_year_value)) {
                            $stmt = $dbh->prepare("SELECT id FROM academic_years WHERE id = ?");
                            $stmt->execute([$academic_year_value]);
                            if ($stmt->fetch()) {
                                $academic_year_id = $academic_year_value;
                            } else {
                                $errors[] = "Row $rowNumber: Invalid academic year ID $academic_year_value.";
                                continue;
                            }
                        } else {
                            if (isset($validAcademicYears[$academic_year_value])) {
                                $academic_year_id = $validAcademicYears[$academic_year_value];
                            } else {
                                $errors[] = "Row $rowNumber: Invalid academic year name $academic_year_value.";
                                continue;
                            }
                        }
                    }

                    // Check duplicate
                    $check_stmt = $dbh->prepare("SELECT COUNT(*) FROM student WHERE student_id = ?");
                    $check_stmt->execute([$student_id]);
                    if ($check_stmt->fetchColumn() > 0) {
                        $errors[] = "Row $rowNumber: Student ID $student_id already exists.";
                        continue;
                    }

                    $validRows[] = [
                        'student_id' => $student_id,
                        'student_name' => $student_name,
                        'contact' => $contact,
                        'dept_id' => $dept_id,
                        'status' => $status,
                        'academic_year_id' => $academic_year_id
                    ];
                }

                // Show validation errors
                if (!empty($errors)) {
                    $dbh->rollBack();
                    echo "<script>alert('Validation errors: " . addslashes(implode('; ', $errors)) . "'); window.location.href='addstudents.php';</script>";
                    exit;
                }

                // Insert all valid records
                foreach ($validRows as $data) {
                    $stmt = $dbh->prepare("INSERT INTO student (student_id, student_name, contact, dept_id, status, academic_year_id)
                        VALUES (:student_id, :student_name, :contact, :dept_id, :status, :academic_year_id)");
                    $stmt->execute([
                        ':student_id' => $data['student_id'],
                        ':student_name' => $data['student_name'],
                        ':contact' => $data['contact'],
                        ':dept_id' => $data['dept_id'],
                        ':status' => $data['status'],
                        ':academic_year_id' => $data['academic_year_id']
                    ]);
                }

                $dbh->commit();
                echo "<script>alert('Data imported successfully! " . count($validRows) . " records inserted.'); window.location.href='addstudents.php';</script>";
                exit;

            } catch (Exception $e) {
                $dbh->rollBack();
                echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='addstudents.php';</script>";
                exit;
            }

        } else {
            echo "<script>alert('Failed to parse Excel file!'); window.location.href='addstudents.php';</script>";
            exit;
        }

    } else {
        echo "<script>alert('Error uploading file!'); window.location.href='addstudents.php';</script>";
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spoural Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
                    <h2><i class='bx bx-user-circle'></i> Students</h2>
                    <div style="margin-top: 10px;">
                        <label for="academicYear">Academic Year: </label>
                        <select id="academicYear" name="academicYear_display" disabled>
                            <?php foreach (
                                $academicYears as $year): ?>
                                <option value="<?= htmlspecialchars($year['id']) ?>" <?= (isset($academic_year_id) && $academic_year_id == $year['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['year']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Hidden field to actually submit the value -->
                        <input type="hidden" name="academic_year_id" value="<?= isset($academic_year_id) ? htmlspecialchars($academic_year_id) : (isset($academicYears[0]['id']) ? htmlspecialchars($academicYears[0]['id']) : '') ?>">
                    </div>
                </div>

                <div class="card-container">
                    <div class="upload-card" id="singleStudentCard">
                        <div class="icon-title-row">
                            <i class='bx bx-detail'></i>
                            <span class="card-title">Upload Single Student</span>
                        </div>
                        <p class="card-subtitle">Add a single student</p>
                    </div>
                    <div class="upload-card" id="multipleStudentCard">
                        <div class="icon-title-row">
                            <i class='bx bx-list-ul'></i>
                            <span class="card-title">Upload Multiple Students</span>
                        </div>
                        <p class="card-subtitle">Add multiple students</p>
                    </div>
                </div>
            </div>

            <div class="content-card" id="singleStudentContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-detail'></i> Single Student Management</h2>
                </div>
                <div class="main-content">
                    <div class="form-container">
                        <h3><?= isset($_GET['edit_id']) ? 'Edit Student' : 'Add New Student' ?></h3>

                        <form method="post" action="addstudents.php">
                            <?php if (isset($_GET['edit_id'])): ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['edit_id']) ?>">
                            <?php endif; ?>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Student ID</label>
                                    <input type="text" name="student_id" class="form-control" value="<?= isset($student_id) ? htmlspecialchars($student_id) : '' ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Student Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= isset($student_name) ? htmlspecialchars($student_name) : '' ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Contact</label>
                                    <input type="text" name="contact" class="form-control" value="<?= isset($contact) ? htmlspecialchars($contact) : '' ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Academic Year:</label>
                                <select name="academic_year_id_display" class="input-field" required disabled>
                                    <?php foreach (
                                        $academicYears as $year): ?>
                                        <option value="<?= htmlspecialchars($year['id']) ?>" <?= (isset($academic_year_id) && $academic_year_id == $year['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($year['year']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <!-- Hidden field to actually submit the value -->
                                <input type="hidden" name="academic_year_id" value="<?= isset($academic_year_id) ? htmlspecialchars($academic_year_id) : (isset($academicYears[0]['id']) ? htmlspecialchars($academicYears[0]['id']) : '') ?>">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Department</label>
                                    <select name="department" class="form-select">
                                        <option value="">Select Department</option>
                                        <?php 
                                        $query = $dbh->prepare("SELECT dept_id, dept_name FROM departments");
                                        $query->execute();
                                        $departments = $query->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($departments as $dept): 
                                        ?>
                                            <option value="<?= htmlspecialchars($dept['dept_id']) ?>" <?= (isset($dept_id) && $dept_id == $dept['dept_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dept['dept_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <?= isset($_GET['edit_id']) ? 'Update Student' : 'Add Student' ?>
                                </button>
                            </div>
                        </form>
                    </div>

                                            
                    </div>
                </div>
            </div>

            <div class="content-card" id="multipleStudentContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-list-ul'></i> Multiple Students Management</h2>
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
        </div>
    </div>

    <?php include_once('../includes/footer.php'); ?>

    <script>
        function toggleContent(contentId) {
            const content = document.getElementById(contentId);
            const otherContent = contentId === 'singleStudentContent' ? 
                document.getElementById('multipleStudentContent') : 
                document.getElementById('singleStudentContent');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                otherContent.style.display = 'none';
            } else {
                content.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('singleStudentCard').addEventListener('click', function() {
                document.getElementById('singleStudentContent').style.display = 'block';
                document.getElementById('multipleStudentContent').style.display = 'none';
            });
            document.getElementById('multipleStudentCard').addEventListener('click', function() {
                document.getElementById('singleStudentContent').style.display = 'none';
                document.getElementById('multipleStudentContent').style.display = 'block';
            });
        });

        // Handle edit button clicks
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-student').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const contact = this.getAttribute('data-contact');
                    const deptId = this.getAttribute('data-dept-id');
                    let academicYearId = this.getAttribute('data-academic-year-id');
                    // If academicYearId is empty, try to get it from the visible text in the table row
                    if (!academicYearId) {
                        const tr = this.closest('tr');
                        const yearText = tr ? tr.querySelector('td:nth-child(4)')?.textContent.trim() : '';
                        if (yearText && window.academicYearsList) {
                            for (const y of window.academicYearsList) {
                                if (y.year === yearText) {
                                    academicYearId = y.id;
                                    break;
                                }
                            }
                        }
                    }
                    document.getElementById('singleStudentContent').style.display = 'block';
                    document.getElementById('multipleStudentContent').style.display = 'none';
                    document.querySelector('input[name="student_id"]').value = id;
                    document.querySelector('input[name="name"]').value = name;
                    document.querySelector('input[name="contact"]').value = contact;
                    document.querySelector('select[name="department"]').value = deptId;
                    document.querySelector('input[name="academic_year_id"]').value = academicYearId;
                    document.querySelector('.form-container h3').textContent = 'Edit Student';
                    document.querySelector('button[name="submit"]').textContent = 'Update Student';
                    document.getElementById('singleStudentContent').scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    </script>
    <script>
window.academicYearsList = <?php echo json_encode($academicYears); ?>;
// Academic year textbox to hidden id sync
const yearTextBox = document.getElementById('academicYearText');
const yearIdHidden = document.getElementById('academicYearIdHidden');
yearTextBox && yearTextBox.addEventListener('input', function() {
    const val = this.value.trim();
    let found = '';
    if (window.academicYearsList) {
        for (const y of window.academicYearsList) {
            if (y.year === val) {
                found = y.id;
                break;
            }
        }
    }
    yearIdHidden.value = found;
});
</script>
</body>

</html>
