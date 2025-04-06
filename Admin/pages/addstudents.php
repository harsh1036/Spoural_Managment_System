<?php
session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

// Fetch session data
$admin_username = $_SESSION['login'];

// Initialize variables
$student_id = $student_name = $contact = $dept_id = "";

// Handle form submission
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $dept_id = $_POST['department'];

    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update existing student
            $id = $_POST['id'];
            $sql = "UPDATE student SET student_name = :name, contact = :contact, dept_id = :dept_id WHERE student_id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Insert new student
            $sql = "INSERT INTO student (student_name, contact, dept_id) VALUES (:name, :contact, :dept_id)";
            $stmt = $dbh->prepare($sql);
        }

        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':contact', $contact, PDO::PARAM_STR);
        $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);

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
            
            // Validate column names
            if ($rows[0] !== ['student_id', 'student_name', 'contact', 'dept_id']) {
                $message = "<div class='alert alert-danger'>Error: Column names do not match the expected format!</div>";
            } else {
                foreach (array_slice($rows, 1) as $row) {
                    $student_id = $row[0]; 
                    $student_name = $row[1]; 
                    $contact = $row[2]; 
                    $dept_id = $row[3];

                    $sql = "INSERT INTO student (student_id, student_name, contact, dept_id) VALUES (?, ?, ?, ?)";
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute([$student_id, $student_name, $contact, $dept_id]);
                }
                $message = "<div class='alert alert-success'>Data imported successfully!</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Failed to parse Excel file!</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Error uploading file!</div>";
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
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>

    <div class="home-content">
        <div class="participant-entry-container">
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-user-circle'></i> Students</h2>
                </div>

                <div class="quick-access-grid">
                    <a href="#" class="quick-access-card" id="singleStudentBtn">
                        <i class='bx bx-detail'></i>
                        <h3>Upload Single Student</h3>
                        <p>Add a single student</p>
                    </a>

                    <a href="#" class="quick-access-card" id="multipleStudentBtn">
                        <i class='bx bx-list-ul'></i>
                        <h3>Upload Multiple Students</h3>
                        <p>Add multiple students</p>
                    </a>
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
                                    <label class="form-label">Student Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= isset($student_name) ? htmlspecialchars($student_name) : '' ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Contact</label>
                                    <input type="text" name="contact" class="form-control" value="<?= isset($contact) ? htmlspecialchars($contact) : '' ?>">
                                </div>
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

                    <div class="table-responsive mt-4">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sql = "SELECT s.student_id, s.student_name, s.contact, d.dept_name
                                        FROM student s
                                        JOIN departments d ON s.dept_id = d.dept_id";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $students = $query->fetchAll(PDO::FETCH_ASSOC);
                                if (count($students) > 0): 
                                    foreach ($students as $student): 
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                                        <td><?= htmlspecialchars($student['student_name']) ?></td>
                                        <td><?= htmlspecialchars($student['contact'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($student['dept_name'] ?? '') ?></td>
                                        <td>
                                            <a href="addstudents.php?edit_id=<?= $student['student_id'] ?>" class="btn btn-sm btn-primary">
                                                <i class='bx bx-edit'></i> Edit
                                            </a>
                                            <a href="addstudents.php?delete_id=<?= $student['student_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                                <i class='bx bx-trash'></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php 
                                    endforeach;
                                else: 
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No students found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

        document.getElementById('singleStudentBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('singleStudentContent');
        });

        document.getElementById('multipleStudentBtn').addEventListener('click', function(e) {
            e.preventDefault();
            toggleContent('multipleStudentContent');
        });
    </script>
</body>

</html>
