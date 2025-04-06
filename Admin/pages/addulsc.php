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
$id = $ulsc_id = $ulsc_name = $dept_id = $contact = "";

// Handle delete operation
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = $_GET['delete_id'];
        $sql = "DELETE FROM ulsc WHERE id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo "<script>alert('ULSC deleted successfully!'); window.location.href='addulsc.php';</script>";
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

// Handle form submission
if (isset($_POST['save_ulsc'])) {
    $ulsc_id = $_POST['ulsc_id'];
    $ulsc_name = $_POST['ulsc_name'];
    $dept_id = $_POST['dept_id'];
    $contact = $_POST['contact'];
    $id = $_POST['id'];

    try {
        if (!empty($id)) {
            // Update existing ULSC
            $sql = "UPDATE ulsc SET ulsc_id = :ulsc_id, ulsc_name = :name, dept_id = :dept_id, contact = :contact WHERE id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Insert new ULSC
            $sql = "INSERT INTO ulsc (ulsc_id, ulsc_name, dept_id, contact) VALUES (:ulsc_id, :name, :dept_id, :contact)";
            $stmt = $dbh->prepare($sql);
        }

        $stmt->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
        $stmt->bindParam(':name', $ulsc_name, PDO::PARAM_STR);
        $stmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
        $stmt->bindParam(':contact', $contact, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo "<script>alert('ULSC saved successfully!'); window.location.href='addulsc.php';</script>";
        } else {
            echo "<script>alert('Error saving ULSC!');</script>";
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
    </style>
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>

    <div class="home-content">
        <div class="participant-entry-container">
            <div class="content-card">
                <div class="content-header">
                    <h2><i class='bx bx-football'></i> ULSC</h2>
                </div>

                <div class="quick-access-grid">
                    <a href="#" class="quick-access-card" id="singleULSCBtn">
                        <i class='bx bx-detail'></i>
                        <h3>ADD Single ULSC</h3>
                        <p>Add Single ulsc for a specific department</p>
                    </a>

                    <a href="#" class="quick-access-card" id="multipleULSCBtn">
                        <i class='bx bx-list-ul'></i>
                        <h3>Upload Multiple ULSC</h3>
                        <p>Add Multiple ulsc for a Multiple department</p>
                    </a>
                </div>
            </div>

            <div class="content-card" id="singleULSCContent" style="display: none;">
                <div class="content-header">
                    <h2><i class='bx bx-detail'></i> Single ULSC Management</h2>
                </div>
                <div class="main-content">
                    <section class="ulsc-form">
                        <h3><?= isset($_GET['edit_id']) ? 'Edit ULSC' : 'New ULSC' ?></h3>
                        <form method="post" action="addulsc.php" class="ulsc-input-form">
                            <input type="hidden" name="id" value="<?= isset($_GET['edit_id']) ? htmlspecialchars($_GET['edit_id']) : '' ?>">

                            <label>ULSC ID:</label>
                            <input type="text" name="ulsc_id" class="input-field" value="<?= isset($ulsc_id) ? htmlspecialchars($ulsc_id) : '' ?>" required>

                            <label>ULSC Name:</label>
                            <input type="text" name="ulsc_name" class="input-field" value="<?= isset($ulsc_name) ? htmlspecialchars($ulsc_name) : '' ?>" required>

                            <label>Department:</label>
                            <select name="dept_id" class="input-field" required>
                                <option value="">Select Department</option>
                                <?php 
                                $sql = "SELECT dept_id, dept_name FROM departments";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $departments = $query->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($departments as $dept): 
                                ?>
                                    <option value="<?= $dept['dept_id'] ?>" <?= isset($dept_id) && $dept_id == $dept['dept_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['dept_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label>Contact Number:</label>
                            <input type="number" name="contact" class="input-field" value="<?= isset($contact) ? htmlspecialchars($contact) : '' ?>" required>

                            <button type="submit" name="save_ulsc" class="submit-button"><?= isset($_GET['edit_id']) ? 'Update' : 'Submit' ?></button>
                        </form>
                    </section>
                    <br><br>
                    <section class="ulsc-table">
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
                                        <a href="#" class="edit-ulsc" data-id="<?= $row['id'] ?>" data-ulsc-id="<?= htmlspecialchars($row['ulsc_id']) ?>" 
                                           data-ulsc-name="<?= htmlspecialchars($row['ulsc_name']) ?>" 
                                           data-dept-id="<?= $row['dept_id'] ?>" 
                                           data-contact="<?= htmlspecialchars($row['contact']) ?>">
                                            <img src="../assets/images/edit.jpg" alt="Edit" width="20" height="20">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="addulsc.php?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">
                                            <img src="../assets/images/delete.jpg" alt="Delete" width="20" height="20">
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                $sr++;
                                } 
                                ?>
                            </tbody>
                        </table>
                    </section>
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
</body>

</html>
