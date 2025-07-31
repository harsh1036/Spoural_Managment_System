<?php
require_once __DIR__ . '/SimpleXLSXGen.php';
require_once __DIR__ . '/SimpleXLSX.php';
use Shuchkin\SimpleXLSXGen;
use Shuchkin\SimpleXLSX;

include('../includes/session_management.php');
include('../includes/config.php');
include('sendMail.php'); // Assuming sendMail.php contains the sendULSCEmail function

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

// Initialize variables
$id = $fullname = $dept_id = $contact_no = ""; // Changed ulsc_id to id, ulsc_name to fullname, contact to contact_no
$message = "";

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch session data
$admin_username = $_SESSION['login'];

// Academic years are not in ulsc_f table, so this block might be irrelevant for ULSC_F.
// Keeping it for now if it's used elsewhere in the page.
// $academicYears = [];
// $yearQuery = $dbh->query("SELECT year, id FROM academic_years ORDER BY year DESC");
// if ($yearQuery) {
//     $academicYears = $yearQuery->fetchAll(PDO::FETCH_ASSOC);
// }
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
        $sql = "DELETE FROM ulsc_f WHERE id = :id"; // Changed table to ulsc_f
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo "<script>alert('ULSC Faculty deleted successfully!'); window.location.href='addulsc_f.php';</script>";
        } else {
            echo "<script>alert('Error deleting ULSC Faculty!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle edit operation - fetch ULSC Faculty data
if (isset($_GET['edit_id'])) {
    try {
        $edit_id = $_GET['edit_id'];
        $sql = "SELECT * FROM ulsc_f WHERE id = :id"; // Changed table to ulsc_f
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($ulsc_f_data = $stmt->fetch(PDO::FETCH_ASSOC)) { // Renamed variable
            $id = $ulsc_f_data['id'];
            $fullname = $ulsc_f_data['fullname']; // Changed ulsc_name to fullname
            $dept_id = $ulsc_f_data['dept_id'];
            $contact_no = $ulsc_f_data['contact_no']; // Changed contact to contact_no
            $academic_year_id = $ulsc_f_data['academic_year_id']; // Get academic year ID
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Function to generate and download the Excel template
if (isset($_GET['download_template'])) {
    // Fetch column names dynamically from the ulsc_f table
    $columns = [];
    $stmt = $dbh->query("SHOW COLUMNS FROM ulsc_f");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!in_array($row['Field'], ['password', 'status'])) {
            $columns[] = $row['Field'];
        }
    }
    $data = [ $columns ]; // Column headers
    $xlsx = SimpleXLSXGen::fromArray($data);
    $xlsx->downloadAs('ULSC_Faculty_Template.xlsx');
    exit;
}

// Handle file upload and validation
if (isset($_POST['import'])) {
    if ($_FILES['excel_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];

        if ($xlsx = SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            $expectedColumns = ['id', 'fullname', 'dept_id', 'contact_no', 'email','password','status', 'academic_year_id'];

            if ($rows[0] !== $expectedColumns) {
                echo "<script>alert('Error: Column names do not match the expected format!'); window.location.href='addulsc_f.php';</script>";
                exit;
            } else {
                try {
                    $dbh->beginTransaction();

                    // Fetch valid academic year IDs from the database
                    $validYearIds = [];
                    $yearQuery = $dbh->query("SELECT id FROM academic_years");
                    if ($yearQuery) {
                        $validYearIds = array_column($yearQuery->fetchAll(PDO::FETCH_ASSOC), 'id');
                    }

                    foreach (array_slice($rows, 1) as $rowIndex => $row) {
                        $id_excel = $row[0];
                        $fullname = $row[1];
                        $dept_id = $row[2];
                        $contact_no = $row[3];
                        $email = $row[4];
                        $academic_year_id = $row[5]; // Academic year ID from Excel

                        // Check if the academic year ID is valid
                        if (!in_array($academic_year_id, $validYearIds)) {
                            throw new Exception("Academic year ID \"$academic_year_id\" not found in database. Valid IDs: " . implode(', ', $validYearIds) . ". Please check your Excel file row " . ($rowIndex + 2));
                        }

                        // Generate password
                        $plain_password = "1234";
                        $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);

                        // Check if ULSC Faculty ID already exists
                        $check_sql = "SELECT COUNT(*) FROM ulsc_f WHERE id = :id";
                        $check_stmt = $dbh->prepare($check_sql);
                        $check_stmt->bindParam(':id', $id_excel, PDO::PARAM_INT);
                        $check_stmt->execute();

                        if ($check_stmt->fetchColumn() > 0) {
                            throw new Exception("ULSC Faculty ID $id_excel already exists");
                        }

                        $sql = "INSERT INTO ulsc_f (id, fullname, dept_id, contact_no, email, academic_year_id, password, status) VALUES (:id, :fullname, :dept_id, :contact_no, :email, :academic_year_id, :password, 1)";
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindParam(':id', $id_excel, PDO::PARAM_INT);
                        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
                        $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
                        $stmt->bindParam(':contact_no', $contact_no, PDO::PARAM_STR);
                        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
                        $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                        $stmt->execute();
                    }

                    $dbh->commit();
                    echo "<script>alert('Data imported successfully!'); window.location.href='addulsc_f.php';</script>";
                    exit;

                } catch (Exception $e) {
                    $dbh->rollBack();
                    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='addulsc_f.php';</script>";
                    exit;
                }
            }
        } else {
            echo "<script>alert('Failed to parse Excel file!'); window.location.href='addulsc_f.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Error uploading file!'); window.location.href='addulsc_f.php';</script>";
        exit;
    }
}

// Handle form submission
if (isset($_POST['save_ulsc'])) {
    error_log("Form submitted. POST data: " . print_r($_POST, true));
    
    try {
        // Get form data
        $id_form = trim($_POST['id']); // This is the ULSC Faculty ID (primary key)
        $fullname = trim($_POST['fullname']); // Changed to fullname
        $dept_id = trim($_POST['dept_id']);
        $contact_no = trim($_POST['contact_no']); // Changed to contact_no
        $email = trim($_POST['email']); // Get email from POST
        $current_id = isset($_POST['current_id']) ? trim($_POST['current_id']) : ''; // Use a hidden field for original ID during edit
        $academic_year_id = isset($_POST['academic_year_id']) ? trim($_POST['academic_year_id']) : ''; // Get academic year ID from POST

        // Validate form data
        if (empty($id_form) || empty($fullname) || empty($dept_id) || empty($contact_no)) {
            throw new Exception("All fields are required");
        }

        // Generate password (only for new entries, or if you intend to re-set on edit)
        $plain_password = "1234";
        $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);

        // Begin transaction
        $dbh->beginTransaction();

        if (!empty($current_id)) { // If current_id is present, it's an update
            // Update existing ULSC Faculty
            $sql = "UPDATE ulsc_f SET id = :new_id, fullname = :fullname, dept_id = :dept_id, contact_no = :contact_no, email = :email, academic_year_id = :academic_year_id WHERE id = :current_id";
            error_log("Update SQL: " . $sql);
            
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':new_id', $id_form, PDO::PARAM_INT); // New ID for update
            $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
            $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
            $stmt->bindParam(':contact_no', $contact_no, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT); // Bind academic_year_id
            $stmt->bindParam(':current_id', $current_id, PDO::PARAM_INT); // Original ID
        } else {
            // Check if ULSC Faculty ID already exists for new entry
            $check_sql = "SELECT COUNT(*) FROM ulsc_f WHERE id = :id"; // Changed table to ulsc_f, column to id
            $check_stmt = $dbh->prepare($check_sql);
            $check_stmt->bindParam(':id', $id_form, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("ULSC Faculty ID already exists");
            }

            // Insert new ULSC Faculty
            $sql = "INSERT INTO ulsc_f (id, fullname, dept_id, contact_no, email, academic_year_id, password, status) VALUES (:id, :fullname, :dept_id, :contact_no, :email, :academic_year_id, :password, 1)"; // Changed table to ulsc_f, columns, added status
            error_log("Insert SQL: " . $sql);
            
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $id_form, PDO::PARAM_INT);
            $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
            $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
            $stmt->bindParam(':contact_no', $contact_no, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT); // Bind academic_year_id
            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        }

        // Execute the query
        $result = $stmt->execute();
        error_log("Query execution result: " . ($result ? "Success" : "Failed"));
        
        if ($result) {
            $dbh->commit();
            if (empty($current_id)) { // Check for empty current_id for new entry
                // Send email for new entries
                // Consider if you want to send email with plain password for faculties.
                // Assuming sendULSCEmail function exists and accepts these parameters.
                if (sendULSCEmail($fullname, $email, $plain_password)) { // Pass fullname instead of ulsc_name
                    $message = "ULSC Faculty added successfully with email: $email and default password: 1234";
                } else {
                    $message = "ULSC Faculty added successfully with email: $email and default password: 1234. Email sending failed.";
                }
            } else {
                $message = "ULSC Faculty updated successfully!";
            }
            echo "<script>alert('$message'); window.location.href='addulsc_f.php';</script>";
        } else {
            throw new Exception("Error executing query: " . print_r($stmt->errorInfo(), true));
        }

    } catch (Exception $e) {
        $dbh->rollBack();
        error_log("Error: " . $e->getMessage());
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sports Events - Spoural</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: #4a90e2;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
        }
        
        .form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
        }
        
        .btn-info {
            background-color: #4a90e2;
            border: none;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #357abd;
            color: white;
        }
        
        .btn-success {
            background-color: #28a745;
            border: none;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        
        .d-grid {
            margin-top: 20px;
        }
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
                    <h2><i class='bx bx-group'></i> ULSC Faculty</h2> <div style="margin-top: 10px;">
                        <label for="academicYear">Academic Year: </label>
                        <select id="academicYear" name="academicYear_display" disabled>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?= htmlspecialchars($year['id']) ?>" <?= (isset($academic_year_id) && $academic_year_id == $year['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year['year']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <section class="ulsc-table">
                    <h3>View ULSC Faculty Details</h3>
                    <div class="table-scroll" style="max-height: 400px; overflow-y: auto;">
                        <table border='2px' class='cntr table table-bordered table-striped small-table participants-table' id="ulscTable">
                            <thead>
                                <tr>
                                    <th>Sr.no</th>
                                    <th>ULSC Faculty ID</th>
                                    <th>Full Name</th>
                                    <th>Department</th>
                                    <th>Contact Number</th>
                                    <th>Email</th>
                                    <!-- <th>Status</th> -->
                                    <th>Edit</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // SQL query adapted for ulsc_f table and its columns
                                $sql = "SELECT ulsc_f.id, ulsc_f.fullname, ulsc_f.dept_id, departments.dept_name, ulsc_f.contact_no, ulsc_f.email, ulsc_f.status 
                                        FROM ulsc_f 
                                        JOIN departments ON ulsc_f.dept_id = departments.dept_id";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $results = $query->fetchAll(PDO::FETCH_ASSOC);
                                $sr = 1;
                                foreach ($results as $row) { 
                                ?>
                                <tr>
                                    <td><?= $sr ?></td>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['dept_name']) ?></td>
                                    <td><?= htmlspecialchars($row['contact_no']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <!-- <td><?= ($row['status'] == 1) ? 'Active' : 'Inactive' ?></td> -->
                                    <td>
                                        <button type="button" 
                                                class="edit-ulsc btn btn-sm btn-primary"
                                                data-id="<?= $row['id'] ?>"
                                                data-fullname="<?= htmlspecialchars($row['fullname']) ?>"
                                                data-dept-id="<?= $row['dept_id'] ?>"
                                                data-contact-no="<?= htmlspecialchars($row['contact_no']) ?>"
                                                data-email="<?= htmlspecialchars($row['email']) ?>">
                                            <i class='bx bx-edit'></i> Edit
                                        </button>
                                    </td>
                                    <td>
                                        <a href="addulsc_f.php?delete_id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this ULSC Faculty member?')">
                                            <i class='bx bx-trash'></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                $sr++;
                                } 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="card-container">
                    <div class="upload-card" id="singleULSCCard">
                        <div class="icon-title-row">
                            <i class='bx bx-detail'></i>
                            <span class="card-title">ADD Single ULSC Faculty</span> </div>
                        <p class="card-subtitle">Add Single ULSC Faculty for a specific department</p> </div>
                    <div class="upload-card" id="multipleULSCCard">
                        <div class="icon-title-row">
                            <i class='bx bx-list-ul'></i>
                            <span class="card-title">Upload Multiple ULSC Faculty</span> </div>
                        <p class="card-subtitle">Add Multiple ULSC Faculty for multiple departments</p> </div>
                </div>
            </div>

            <div class="content-card" id="singleULSCContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-detail'></i> Single ULSC Faculty Management</h2> </div>
                <div class="main-content">
                    
                    <section class="ulsc-form">
                        <h3><?= empty($id) ? 'New ULSC Faculty' : 'Edit ULSC Faculty' ?></h3> <form method="post" class="ulsc-input-form">
                            <input type="hidden" name="current_id" value="<?= htmlspecialchars($id) ?>"> 
                            
                            <div class="form-group">
                                <label>ULSC Faculty ID:</label> <input type="text" name="id" class="input-field" value="<?= htmlspecialchars($id) ?>" required> </div>

                            <div class="form-group">
                                <label>Full Name:</label> <input type="text" name="fullname" class="input-field" value="<?= htmlspecialchars($fullname) ?>" required> </div>

                            <div class="form-group">
                                <label>Department:</label>
                                <select name="dept_id" class="input-field" required>
                                    <option value="">Select Department</option>
                                    <?php 
                                    $dept_sql = "SELECT dept_id, dept_name FROM departments ";
                                    $dept_stmt = $dbh->prepare($dept_sql);
                                    $dept_stmt->execute();
                                    while ($dept = $dept_stmt->fetch(PDO::FETCH_ASSOC)): 
                                    ?>
                                        <option value="<?= $dept['dept_id'] ?>" <?= ($dept_id == $dept['dept_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['dept_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Contact Number:</label> <input type="number" name="contact_no" class="input-field" value="<?= htmlspecialchars($contact_no) ?>" required> </div>

                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($email ?? '') ?>" required>
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
                            <button type="submit" name="save_ulsc" class="submit-button">
                                <?= empty($id) ? 'Submit' : 'Update' ?>
                            </button>
                        </form>
                    </section>
                    <br><br>
                </div>
            </div>

            <div class="content-card" id="multipleULSCContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-list-ul'></i> Multiple ULSC Faculty Management</h2> </div>
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
            const otherContent = contentId === 'singleULSCContent' ? 
                document.getElementById('multipleULSCContent') : 
                document.getElementById('singleULSCContent');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                otherContent.style.display = 'none';
            } else {
                content.style.display = 'none';
            }
        }

        document.getElementById('singleULSCCard').addEventListener('click', function() {
            document.getElementById('singleULSCContent').style.display = 'block';
            document.getElementById('multipleULSCContent').style.display = 'none';
            // Clear form fields when switching to add new
            document.querySelector('input[name="id"]').value = '';
            document.querySelector('input[name="fullname"]').value = '';
            document.querySelector('select[name="dept_id"]').value = '';
            document.querySelector('input[name="contact_no"]').value = '';
            document.querySelector('input[name="email"]').value = ''; // Clear email field for new entry
            document.querySelector('input[name="current_id"]').value = ''; // Clear hidden ID for new entry
            document.querySelector('.ulsc-form h3').textContent = 'New ULSC Faculty';
            document.querySelector('button[name="save_ulsc"]').textContent = 'Submit';
        });

        document.getElementById('multipleULSCCard').addEventListener('click', function() {
            document.getElementById('singleULSCContent').style.display = 'none';
            document.getElementById('multipleULSCContent').style.display = 'block';
        });

        document.querySelectorAll('.edit-ulsc').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-id');
                const fullname = this.getAttribute('data-fullname');
                const deptId = this.getAttribute('data-dept-id');
                const contactNo = this.getAttribute('data-contact-no');
                const email = this.getAttribute('data-email');

                document.querySelector('input[name="id"]').value = id;
                document.querySelector('input[name="current_id"]').value = id;
                document.querySelector('input[name="fullname"]').value = fullname;
                document.querySelector('select[name="dept_id"]').value = deptId;
                document.querySelector('input[name="contact_no"]').value = contactNo;
                document.querySelector('input[name="email"]').value = email;

                document.querySelector('.ulsc-form h3').textContent = 'Edit ULSC Faculty';
                document.querySelector('button[name="save_ulsc"]').textContent = 'Update';

                document.getElementById('singleULSCContent').style.display = 'block';
                document.getElementById('multipleULSCContent').style.display = 'none';

                document.querySelector('.ulsc-form').scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Initially hide content cards
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('singleULSCContent').style.display = 'none';
            document.getElementById('multipleULSCContent').style.display = 'none';
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