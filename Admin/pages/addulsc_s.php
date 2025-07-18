<?php
require_once __DIR__ . '/SimpleXLSXGen.php';
require_once __DIR__ . '/SimpleXLSX.php';
use Shuchkin\SimpleXLSXGen;
use Shuchkin\SimpleXLSX;

include('../includes/session_management.php');
include('../includes/config.php');
include('sendMail.php');

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
$id = $ulsc_id = $ulsc_name = $dept_id = $contact = "";
$message = "";

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch session data
$admin_username = $_SESSION['login'];

// Fetch academic years from the database
$academicYears = [];
$yearQuery = $dbh->query("SELECT year FROM academic_years ORDER BY year DESC");
if ($yearQuery) {
    $academicYears = $yearQuery->fetchAll(PDO::FETCH_COLUMN);
}

// Handle delete operation
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];
        $sql = "DELETE FROM ulsc WHERE id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo "<script>alert('ULSC deleted successfully!'); window.location.href='addulsc_s.php';</script>";
        } else {
            echo "<script>alert('Error deleting ULSC!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle edit operation - fetch ULSC data
if (isset($_GET['edit_id'])) {
    try {
        $edit_id = $_GET['edit_id'];
        $sql = "SELECT * FROM ulsc WHERE id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($ulsc = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $ulsc['id'];
            $ulsc_id = $ulsc['ulsc_id'];
            $ulsc_name = $ulsc['ulsc_name'];
            $dept_id = $ulsc['dept_id'];
            $contact = $ulsc['contact'];
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Function to generate and download the Excel template
if (isset($_GET['download_template'])) {
    // Fetch column names dynamically from the ulsc table using PDO
    $columns = [];
    $stmt = $dbh->query("SHOW COLUMNS FROM ulsc");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    $data = [ $columns ]; // Column headers
    $xlsx = SimpleXLSXGen::fromArray($data);
    $xlsx->downloadAs('ULSC_Template.xlsx');
    exit;
}

// Handle file upload and validation
if (isset($_POST['import'])) {
    if ($_FILES['excel_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];
        
        if ($xlsx = SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            $expectedColumns = ['ulsc_id', 'ulsc_name', 'dept_id', 'contact'];
            
            // Validate column names
            if ($rows[0] !== $expectedColumns) {
                echo "<script>alert('Error: Column names do not match the expected format!'); window.location.href='addulsc_s.php';</script>";
                exit;
            } else {
                try {
                    $dbh->beginTransaction();
                    
                    foreach (array_slice($rows, 1) as $row) {
                        $ulsc_id = $row[0]; 
                        $ulsc_name = $row[1]; 
                        $dept_id = $row[2]; 
                        $contact = $row[3];

                        // Generate email and password
                        $email = $ulsc_id . "@charusat.edu.in";
                        $plain_password = "1234";
                        $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);

                        // Check if ULSC ID already exists
                        $check_sql = "SELECT COUNT(*) FROM ulsc WHERE ulsc_id = :ulsc_id";
                        $check_stmt = $dbh->prepare($check_sql);
                        $check_stmt->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
                        $check_stmt->execute();
                        
                        if ($check_stmt->fetchColumn() > 0) {
                            throw new Exception("ULSC ID $ulsc_id already exists");
                        }

                        $sql = "INSERT INTO ulsc (ulsc_id, ulsc_name, dept_id, contact, email, password) VALUES (:ulsc_id, :ulsc_name, :dept_id, :contact, :email, :password)";
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
                        $stmt->bindParam(':ulsc_name', $ulsc_name, PDO::PARAM_STR);
                        $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
                        $stmt->bindParam(':contact', $contact, PDO::PARAM_STR);
                        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                        $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                        $stmt->execute();
                    }
                    
                    $dbh->commit();
                    echo "<script>alert('Data imported successfully!'); window.location.href='addulsc_s.php';</script>";
                    exit;
                    
                } catch (Exception $e) {
                    $dbh->rollBack();
                    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='addulsc_s.php';</script>";
                    exit;
                }
            }
        } else {
            echo "<script>alert('Failed to parse Excel file!'); window.location.href='addulsc_s.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Error uploading file!'); window.location.href='addulsc_s.php';</script>";
        exit;
    }
}

// Handle form submission
if (isset($_POST['save_ulsc'])) {
    error_log("Form submitted. POST data: " . print_r($_POST, true));
    
    try {
        // Get form data
        $ulsc_id = trim($_POST['ulsc_id']);
        $ulsc_name = trim($_POST['ulsc_name']);
        $dept_id = trim($_POST['dept_id']);
        $contact = trim($_POST['contact']);
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';

        // Validate form data
        if (empty($ulsc_id) || empty($ulsc_name) || empty($dept_id) || empty($contact)) {
            throw new Exception("All fields are required");
        }

        // Generate email and password
        $email = $ulsc_id . "@charusat.edu.in";
        $plain_password = "1234";
        $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);

        // Begin transaction
        $dbh->beginTransaction();

        if (!empty($id)) {
            // Update existing ULSC
            $sql = "UPDATE ulsc SET ulsc_id = :ulsc_id, ulsc_name = :ulsc_name, dept_id = :dept_id, contact = :contact WHERE id = :id";
            error_log("Update SQL: " . $sql);
            
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
            $stmt->bindParam(':ulsc_name', $ulsc_name, PDO::PARAM_STR);
            $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
            $stmt->bindParam(':contact', $contact, PDO::PARAM_STR);
        } else {
            // Check if ULSC ID already exists
            $check_sql = "SELECT COUNT(*) FROM ulsc WHERE ulsc_id = :ulsc_id";
            $check_stmt = $dbh->prepare($check_sql);
            $check_stmt->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("ULSC ID already exists");
            }

            // Insert new ULSC
            $sql = "INSERT INTO ulsc (ulsc_id, ulsc_name, dept_id, contact, email, password) VALUES (:ulsc_id, :ulsc_name, :dept_id, :contact, :email, :password)";
            error_log("Insert SQL: " . $sql);
            
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
            $stmt->bindParam(':ulsc_name', $ulsc_name, PDO::PARAM_STR);
            $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
            $stmt->bindParam(':contact', $contact, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        }

        // Execute the query
        $result = $stmt->execute();
        error_log("Query execution result: " . ($result ? "Success" : "Failed"));
        
        if ($result) {
            $dbh->commit();
            if (empty($id)) {
                // Send email for new entries
                if (sendULSCEmail($ulsc_name, $email, $plain_password)) {
                    $message = "ULSC added successfully with email: $email and default password: 1234";
                } else {
                    $message = "ULSC added successfully with email: $email and default password: 1234. Email sending failed.";
                }
            } else {
                $message = "ULSC updated successfully!";
            }
            echo "<script>alert('$message'); window.location.href='addulsc_s.php';</script>";
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
                    <h2><i class='bx bx-football'></i> ULSC</h2>

                    <div style="margin-top: 10px;">
                        <label for="academicYear">Academic Year: </label>
                        <select id="academicYear" name="academicYear">
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Move View ULSC Details table here -->
                <section class="ulsc-table">
                    <h3>View ULSC Details</h3>
                    <input type="text" id="ulscSearch" placeholder="Search ULSC..." class="form-control mb-3" style="max-width: 300px;">
                    <div class="table-scroll" style="max-height: 400px; overflow-y: auto;">
                        <table border='2px' class='cntr table table-bordered table-striped small-table participants-table' id="ulscTable">
                            <thead>
                                <tr>
                                    <th>Sr.no</th>
                                    <th>ULSC ID</th>
                                    <th>ULSC Name</th>
                                    <th>Department</th>
                                    <th>Contact Number</th>
                                    <th>Academic Year</th>
                                    <th>Edit</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sql = "SELECT ulsc.*, departments.dept_name, ay.year AS academic_year FROM ulsc JOIN departments ON ulsc.dept_id = departments.dept_id LEFT JOIN academic_years ay ON ulsc.academic_year_id = ay.id";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $results = $query->fetchAll(PDO::FETCH_ASSOC);
                                $sr = 1;
                                foreach ($results as $row) { 
                                ?>
                                <tr>
                                    <td><?= $sr ?></td>
                                    <td><?= htmlspecialchars($row['ulsc_id']) ?></td>
                                    <td><?= htmlspecialchars($row['ulsc_name']) ?></td>
                                    <td><?= htmlspecialchars($row['dept_name']) ?></td>
                                    <td><?= htmlspecialchars($row['contact']) ?></td>
                                    <td><?= htmlspecialchars($row['academic_year'] ?? '-') ?></td>
                                    <td>
                                        <button type="button" 
                                                class="edit-ulsc btn btn-sm btn-primary"
                                                data-id="<?= $row['id'] ?>"
                                                data-ulsc-id="<?= htmlspecialchars($row['ulsc_id']) ?>"
                                                data-ulsc-name="<?= htmlspecialchars($row['ulsc_name']) ?>"
                                                data-dept-id="<?= $row['dept_id'] ?>"
                                                data-contact="<?= htmlspecialchars($row['contact']) ?>">
                                            <i class='bx bx-edit'></i> Edit
                                        </button>
                                    </td>
                                    <td>
                                        <a href="addulsc_s.php?delete_id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this ULSC member?')">
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

                <!-- Card UI for upload options -->
                <div class="card-container">
                    <div class="upload-card" id="singleULSCCard">
                        <div class="icon-title-row">
                            <i class='bx bx-detail'></i>
                            <span class="card-title">ADD Single ULSC</span>
                        </div>
                        <p class="card-subtitle">Add Single ulsc for a specific department</p>
                    </div>
                    <div class="upload-card" id="multipleULSCCard">
                        <div class="icon-title-row">
                            <i class='bx bx-list-ul'></i>
                            <span class="card-title">Upload Multiple ULSC</span>
                        </div>
                        <p class="card-subtitle">Add Multiple ulsc for a Multiple department</p>
                    </div>
                </div>
            </div>

            <div class="content-card" id="singleULSCContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-detail'></i> Single ULSC Management</h2>
                </div>
                <div class="main-content">
                    <section class="ulsc-form">
                        <h3><?= empty($id) ? 'New ULSC' : 'Edit ULSC' ?></h3>
                        <form method="post" class="ulsc-input-form">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                            
                            <div class="form-group">
                                <label>ULSC ID:</label>
                                <input type="text" name="ulsc_id" class="input-field" value="<?= htmlspecialchars($ulsc_id) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>ULSC Name:</label>
                                <input type="text" name="ulsc_name" class="input-field" value="<?= htmlspecialchars($ulsc_name) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Department:</label>
                                <select name="dept_id" class="input-field" required>
                                    <option value="">Select Department</option>
                                    <?php 
                                    $dept_sql = "SELECT dept_id, dept_name FROM departments";
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
                                <label>Contact Number:</label>
                                <input type="number" name="contact" class="input-field" value="<?= htmlspecialchars($contact) ?>" required>
                            </div>

                            <button type="submit" name="save_ulsc" class="submit-button">
                                <?= empty($id) ? 'Submit' : 'Update' ?>
                            </button>
                        </form>
                    </section>
                    <br><br>
                    <!-- <section class="ulsc-table">
                        <h3>View ULSC Details</h3>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Sr.no</th>
                                    <th>ULSC ID</th>
                                    <th>ULSC Name</th>
                                    <th>Department</th>
                                    <th>Contact Number</th>
                                    <th>Edit</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sql = "SELECT ulsc.*, departments.dept_name FROM ulsc JOIN departments ON ulsc.dept_id = departments.dept_id";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $results = $query->fetchAll(PDO::FETCH_ASSOC);
                                $sr = 1;
                                foreach ($results as $row) { 
                                ?>
                                <tr>
                                    <td><?= $sr ?></td>
                                    <td><?= htmlspecialchars($row['ulsc_id']) ?></td>
                                    <td><?= htmlspecialchars($row['ulsc_name']) ?></td>
                                    <td><?= htmlspecialchars($row['dept_name']) ?></td>
                                    <td><?= htmlspecialchars($row['contact']) ?></td>
                                    <td>
                                        <button type="button" 
                                                class="edit-ulsc btn btn-sm btn-primary"
                                                data-id="<?= $row['id'] ?>"
                                                data-ulsc-id="<?= htmlspecialchars($row['ulsc_id']) ?>"
                                                data-ulsc-name="<?= htmlspecialchars($row['ulsc_name']) ?>"
                                                data-dept-id="<?= $row['dept_id'] ?>"
                                                data-contact="<?= htmlspecialchars($row['contact']) ?>">
                                            <i class='bx bx-edit'></i> Edit
                                        </button>
                                    </td>
                                    <td>
                                        <a href="addulsc_s.php?delete_id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this ULSC member?')">
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
                    </section> -->
                </div>
            </div>

            <div class="content-card" id="multipleULSCContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-list-ul'></i> Multiple ULSC Management</h2>
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

            <!-- <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-info-circle'></i> Sports Events Information</h2>
                </div>
                <div class="card-content">
                    <p>Use the options above to view your department's participation in various sports events. You can:</p>
                    <ul>
                        <li>View the list of students registered for a specific sports event</li>
                        <li>Manage captains and participants for each event</li>
                        <li>View all sports event registrations at once</li>
                    </ul>
                </div>
            </div> -->
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

        document.getElementById('singleULSCBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('singleULSCContent');
        });

        document.getElementById('multipleULSCBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('multipleULSCContent');
        });

        document.querySelectorAll('.edit-ulsc').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-id');
                const ulscId = this.getAttribute('data-ulsc-id');
                const ulscName = this.getAttribute('data-ulsc-name');
                const deptId = this.getAttribute('data-dept-id');
                const contact = this.getAttribute('data-contact');

                document.querySelector('input[name="id"]').value = id;
                document.querySelector('input[name="ulsc_id"]').value = ulscId;
                document.querySelector('input[name="ulsc_name"]').value = ulscName;
                document.querySelector('select[name="dept_id"]').value = deptId;
                document.querySelector('input[name="contact"]').value = contact;

                document.querySelector('.ulsc-form h3').textContent = 'Edit ULSC';
                document.querySelector('button[name="save_ulsc"]').textContent = 'Update';

                document.getElementById('singleULSCContent').style.display = 'block';
                document.getElementById('multipleULSCContent').style.display = 'none';

                document.querySelector('.ulsc-form').scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('singleULSCCard').addEventListener('click', function() {
        document.getElementById('singleULSCContent').style.display = 'block';
        document.getElementById('multipleULSCContent').style.display = 'none';
    });
    document.getElementById('multipleULSCCard').addEventListener('click', function() {
        document.getElementById('singleULSCContent').style.display = 'none';
        document.getElementById('multipleULSCContent').style.display = 'block';
    });
});
</script>
    <script>
        // Live search for ULSC table
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('ulscSearch');
            const table = document.getElementById('ulscTable');
            searchInput.addEventListener('input', function() {
                const filter = searchInput.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    let match = false;
                    row.querySelectorAll('td').forEach(cell => {
                        if (cell.textContent.toLowerCase().includes(filter)) {
                            match = true;
                        }
                    });
                    row.style.display = match ? '' : 'none';
                });
            });
        });
    </script>
</body>

</html>
