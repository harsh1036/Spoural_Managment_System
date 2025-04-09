<?php
include('../includes/session_management.php');
include('../includes/config.php');

// Fetch session data
$admin_username = $_SESSION['login'];

// Initialize variables
$dept_id = $dept_name = "";

// **FETCH DATA FOR EDITING**
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $sql = "SELECT * FROM departments WHERE dept_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $deptData = $query->fetch(PDO::FETCH_ASSOC);

    if ($deptData) {
        $dept_id = $deptData['dept_id'];
        $dept_name = $deptData['dept_name'];
    }
}

// **INSERT OR UPDATE DEPARTMENT**
if (isset($_POST['save_department'])) {
    $dept_name = $_POST['dept_name'];

    if (!empty($_POST['dept_id'])) {
        $dept_id = $_POST['dept_id'];
        $sql = "UPDATE departments SET dept_name = :dept_name WHERE dept_id = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $dept_id, PDO::PARAM_INT);
    } else {
        $sql = "INSERT INTO departments (dept_name) VALUES (:dept_name)";
        $query = $dbh->prepare($sql);
    }

    $query->bindParam(':dept_name', $dept_name, PDO::PARAM_STR);

    if ($query->execute()) {
        echo "<script> window.location.href='addsingledepartment.php';</script>";
    } else {
        echo "<script>alert('Error adding/updating department!');</script>";
    }
}

// **DELETE DEPARTMENT**
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $sql = "DELETE FROM departments WHERE dept_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);

    if ($query->execute()) {
        echo "<script>window.location.href='addsingledepartment.php';</script>";
    } else {
        echo "<script>alert('Failed to delete department!');</script>";
    }
}

// Fetch all departments
$query = $dbh->prepare("SELECT * FROM departments ORDER BY dept_id DESC");
$query->execute();
$departments = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="home-content">
        <?php include_once('../includes/sidebar.php'); ?>

        <div class="home-content">
            <div class="home-page">
                <div class="main-content">
                    <section class="department-form">
                        <h2><?= !empty($dept_id) ? 'Edit Department' : 'New Department' ?></h2>
                        <form method="post" action="addsingledepartment.php" class="department-input-form">
                            <input type="hidden" name="dept_id" value="<?= htmlspecialchars($dept_id) ?>">

                            <label>Department Name:</label>
                            <input type="text" name="dept_name" class="input-field"
                                value="<?= htmlspecialchars($dept_name) ?>" required><br><br>

                            <button type="submit" name="save_department"
                                class="submit-button"><?= !empty($dept_id) ? 'Update' : 'Submit' ?></button>
                        </form>
                    </section>

                    <section class="department-table">
                        <h2>View Departments</h2>
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
                                <?php foreach ($departments as $dept) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($dept['dept_id']) ?></td>
                                    <td><?= htmlspecialchars($dept['dept_name']) ?></td>
                                    <td>
                                        <a href="addsingledepartment.php?edit_id=<?= $dept['dept_id'] ?>">
                                            <img src="../assets/images/edit.jpg" alt="Edit" width="20" height="20">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="addsingledepartment.php?delete_id=<?= $dept['dept_id'] ?>"
                                            onclick="return confirm('Are you sure?')">
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
        </div>
    </div>

    <?php include_once('../includes/footer.php'); ?>
</body>

</html>
