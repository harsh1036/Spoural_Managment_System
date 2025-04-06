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
$event_id = $event_name = $event_type = $min_participants = $max_participants = "";
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
                    <h2><i class='bx bx-user-circle'></i> Departments</h2>
                </div>

                <div class="quick-access-grid">
                    <a href="#" class="quick-access-card" id="singleDeptBtn">
                        <i class='bx bx-detail'></i>
                        <h3>Upload Single Department</h3>
                        <p>Add a single department</p>
                    </a>

                    <a href="#" class="quick-access-card" id="multipleDeptBtn">
                        <i class='bx bx-list-ul'></i>
                        <h3>Upload Multiple Departments</h3>
                        <p>Add multiple departments</p>
                    </a>
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
                    <br><br>
                    <section class="department-table">
                        <h3>View Departments</h3>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Department ID</th>
                                    <th>Department Name</th>
                                    <th>Edit</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $query = $dbh->prepare("SELECT * FROM departments ORDER BY dept_id DESC");
                                $query->execute();
                                $departments = $query->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($departments as $dept) { 
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($dept['dept_id']) ?></td>
                                    <td><?= htmlspecialchars($dept['dept_name']) ?></td>
                                    <td>
                                        <a href="adddepartment.php?edit_id=<?= $dept['dept_id'] ?>">
                                            <img src="../assets/images/edit.jpg" alt="Edit" width="20" height="20">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="adddepartment.php?delete_id=<?= $dept['dept_id'] ?>" onclick="return confirm('Are you sure?')">
                                            <img src="../assets/images/delete.jpg" alt="Delete" width="20" height="20">
                                        </a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
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
    </script>
</body>

</html>
