<?php
require_once __DIR__ . '/SimpleXLSXGen.php';
require_once __DIR__ . '/SimpleXLSX.php';
use Shuchkin\SimpleXLSXGen;
use Shuchkin\SimpleXLSX;

include('../includes/session_management.php');
include('../includes/config.php');

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch session data
$admin_username = $_SESSION['login'];

// Initialize variables
$dept_id = $dept_name = "";

// Handle download template
if (isset($_GET['download_template'])) {
    // Fetch column names dynamically from the departments table using PDO
    $columns = [];
    $stmt = $dbh->query("SHOW COLUMNS FROM departments");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    $data = [ $columns ]; // Column headers
    $xlsx = SimpleXLSXGen::fromArray($data);
    $xlsx->downloadAs('Departments_Template.xlsx');
    exit;
}

// Handle delete operation
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];
        $sql = "DELETE FROM departments WHERE dept_id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo "<script>alert('Department deleted successfully!'); window.location.href='adddepartment.php';</script>";
        } else {
            echo "<script>alert('Error deleting department!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
    }
}

// Handle edit operation - fetch department data
if (isset($_GET['edit_id'])) {
    try {
        $edit_id = $_GET['edit_id'];
        $sql = "SELECT * FROM departments WHERE dept_id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($department = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dept_id = $department['dept_id'];
            $dept_name = $department['dept_name'];
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
            $expectedColumns = ['dept_id', 'dept_name'];
            
            // Validate column names
            if ($rows[0] !== $expectedColumns) {
                echo "<script>alert('Error: Column names do not match the expected format!'); window.location.href='adddepartment.php';</script>";
                exit;
            } else {
                try {
                    $dbh->beginTransaction();
                    
                    foreach (array_slice($rows, 1) as $row) {
                        $dept_id = $row[0]; 
                        $dept_name = $row[1];

                        // Check if department ID already exists
                        $check_sql = "SELECT COUNT(*) FROM departments WHERE dept_id = :dept_id";
                        $check_stmt = $dbh->prepare($check_sql);
                        $check_stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
                        $check_stmt->execute();
                        
                        if ($check_stmt->fetchColumn() > 0) {
                            throw new Exception("Department ID $dept_id already exists");
                        }

                        $sql = "INSERT INTO departments (dept_id, dept_name) VALUES (:dept_id, :dept_name)";
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
                        $stmt->bindParam(':dept_name', $dept_name, PDO::PARAM_STR);
                        $stmt->execute();
                    }
                    
                    $dbh->commit();
                    echo "<script>alert('Data imported successfully!'); window.location.href='adddepartment.php';</script>";
                    exit;
                    
                } catch (Exception $e) {
                    $dbh->rollBack();
                    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='adddepartment.php';</script>";
                    exit;
                }
            }
        } else {
            echo "<script>alert('Failed to parse Excel file!'); window.location.href='adddepartment.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Error uploading file!'); window.location.href='adddepartment.php';</script>";
        exit;
    }
}

// Handle form submission
if (isset($_POST['save_department'])) {
    $dept_name = $_POST['dept_name'];
    $dept_id = $_POST['dept_id'];

    try {
        if (!empty($dept_id)) {
            // Update existing department
            $sql = "UPDATE departments SET dept_name = :name WHERE dept_id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $dept_id, PDO::PARAM_INT);
        } else {
            // Insert new department
            $sql = "INSERT INTO departments (dept_name) VALUES (:name)";
            $stmt = $dbh->prepare($sql);
        }

        $stmt->bindParam(':name', $dept_name, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo "<script>alert('Department saved successfully!'); window.location.href='adddepartment.php';</script>";
        } else {
            echo "<script>alert('Error saving department!');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
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
                    <h2><i class='bx bx-user-circle'></i> Departments</h2>
                </div>

                <!-- Move View Departments table here -->
                <section class="department-table">
                    <h3>View Departments</h3>
                    <div class="table-scroll" style="max-height: 400px; overflow-y: auto;">
                        <table border='2px' class='cntr table table-bordered table-striped small-table participants-table'>
                            <thead>
                                <tr>
                                    <th>Department ID</th>
                                    <th>Department Name</th>
                                    <th>Academic Year</th>
                                    <th>Edit</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $query = $dbh->prepare("SELECT d.*, ay.year AS academic_year FROM departments d LEFT JOIN academic_years ay ON d.academic_year_id = ay.id ORDER BY d.dept_id DESC");
                                $query = $dbh->prepare("SELECT d.*, ay.year AS academic_year FROM departments d LEFT JOIN academic_years ay ON d.academic_year_id = ay.id ORDER BY d.dept_id DESC");
                                $query->execute();
                                $departments = $query->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($departments as $dept) { 
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($dept['dept_id']) ?></td>
                                    <td><?= htmlspecialchars($dept['dept_name']) ?></td>
                                    <td><?= htmlspecialchars($dept['academic_year'] ?? '-') ?></td>
                                    <td>
                                        <a href="#" class="edit-dept btn btn-sm btn-primary" data-id="<?= $dept['dept_id'] ?>" data-name="<?= htmlspecialchars($dept['dept_name']) ?>">
                                            <i class='bx bx-edit'></i> Edit
                                        </a>
                                    </td>
                                    <td>
                                        <a href="adddepartment.php?delete_id=<?= $dept['dept_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                            <i class='bx bx-trash'></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Card UI for upload options -->
                <div class="card-container">
                    <div class="upload-card" id="singleDeptCard">
                        <div class="icon-title-row">
                            <i class='bx bx-detail'></i>
                            <span class="card-title">Upload Single Department</span>
                        </div>
                        <p class="card-subtitle">Add a single department</p>
                    </div>
                    <div class="upload-card" id="multipleDeptCard">
                        <div class="icon-title-row">
                            <i class='bx bx-list-ul'></i>
                            <span class="card-title">Upload Multiple Departments</span>
                        </div>
                        <p class="card-subtitle">Add multiple departments</p>
                    </div>
                </div>
            </div>

            <div class="content-card" id="singleDeptContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-detail'></i> Single Department Management</h2>
                </div>
                <div class="main-content">
                    <section class="department-form">
                        <h3><?= isset($_GET['edit_id']) ? 'Edit Department' : 'New Department' ?></h3>
                        <form method="post" action="adddepartment.php" class="department-input-form">
                            <input type="hidden" name="dept_id" value="<?= isset($_GET['edit_id']) ? htmlspecialchars($_GET['edit_id']) : '' ?>">

                            <label>Department Name:</label>
                            <input type="text" name="dept_name" class="input-field" value="<?= isset($dept_name) ? htmlspecialchars($dept_name) : '' ?>" required>

                            <button type="submit" name="save_department" class="submit-button"><?= isset($_GET['edit_id']) ? 'Update' : 'Submit' ?></button>
                        </form>
                    </section>
                </div>
            </div>

            <div class="content-card" id="multipleDeptContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-list-ul'></i> Multiple Departments Management</h2>
                </div>
                <div class="main-content">
                    <div class="container mt-3">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="card shadow-lg border-0 rounded-3">
                                    <div class="card-body">
                                        <a href="?download_template=1" class="btn btn-info w-100 mb-3">
                                            <i class='bx bx-download'></i> Download Template
                                        </a>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="excel_file" class="form-label">Upload Excel File (.xlsx)</label>
                                                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx" required>
                                            </div>
                                            <div class="d-grid">
                                                <button type="submit" name="import" class="btn btn-success">
                                                    <i class='bx bx-upload'></i> Import Data
                                                </button>
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
                    <h2><i class='bx bx-info-circle'></i> Department Information</h2>
                </div>
                <div class="card-content">
                    <p>Use the options above to manage departments. You can:</p>
                    <ul>
                        <li>View the list of departments</li>
                        <li>Manage department details</li>
                        <li>View all department information at once</li>
                    </ul>
                </div>
            </div> -->
        </div>
    </div>

    <?php include_once('../includes/footer.php'); ?>

    <script>
        function toggleContent(contentId) {
            const content = document.getElementById(contentId);
            const otherContent = contentId === 'singleDeptContent' ? 
                document.getElementById('multipleDeptContent') : 
                document.getElementById('singleDeptContent');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                otherContent.style.display = 'none';
            } else {
                content.style.display = 'none';
            }
        }

        document.getElementById('singleDeptBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('singleDeptContent');
        });

        document.getElementById('multipleDeptBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('multipleDeptContent');
        });

        document.querySelectorAll('.edit-dept').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                document.querySelector('input[name="dept_id"]').value = id;
                document.querySelector('input[name="dept_name"]').value = name;

                document.querySelector('.department-form h3').textContent = 'Edit Department';

                document.querySelector('button[name="save_department"]').textContent = 'Update';

                document.getElementById('singleDeptContent').style.display = 'block';
                document.getElementById('multipleDeptContent').style.display = 'none';

                document.querySelector('.department-form').scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('singleDeptCard').addEventListener('click', function() {
        document.getElementById('singleDeptContent').style.display = 'block';
        document.getElementById('multipleDeptContent').style.display = 'none';
    });
    document.getElementById('multipleDeptCard').addEventListener('click', function() {
        document.getElementById('singleDeptContent').style.display = 'none';
        document.getElementById('multipleDeptContent').style.display = 'block';
    });
});
</script>
</body>

</html>
