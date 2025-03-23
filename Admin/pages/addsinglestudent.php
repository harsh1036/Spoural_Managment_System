<?php
session_start();
include('../includes/config.php');

// Fetch session data
$admin_username = $_SESSION['login'];

// Initialize variables
$student_id = $student_name = $contact = $department_id = "";

// **FETCH DATA FOR EDITING**
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $sql = "SELECT * FROM student WHERE student_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_STR);
    $query->execute();
    $studentData = $query->fetch(PDO::FETCH_ASSOC);

    if ($studentData) {
        $student_id = $studentData['student_id'];
        $student_name = $studentData['student_name'];
        $contact = $studentData['contact'];
        $department_id = $studentData['department_id'];
    }
}

// **INSERT OR UPDATE STUDENT**
if (isset($_POST['save_student'])) {
    $student_id = $_POST['student_id'];
    $student_name = $_POST['student_name'];
    $contact = $_POST['contact'];
    $department_id = $_POST['department_id'];

    if (!empty($_POST['id'])) {
        $id = $_POST['id'];
        $sql = "UPDATE student SET student_name = :student_name, contact = :contact, department_id = :department_id WHERE student_id = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_STR);
    } else {
        $sql = "INSERT INTO student (student_id, student_name, contact, department_id) VALUES (:student_id, :student_name, :contact, :department_id)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':student_id', $student_id, PDO::PARAM_STR);
    }

    $query->bindParam(':student_name', $student_name, PDO::PARAM_STR);
    $query->bindParam(':contact', $contact, PDO::PARAM_INT);
    $query->bindParam(':department_id', $department_id, PDO::PARAM_INT);

    if ($query->execute()) {
        echo "<script> window.location.href='addstudent.php';</script>";
    } else {
        echo "<script>alert('Error adding/updating student!');</script>";
    }
}

// **DELETE STUDENT**
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $sql = "DELETE FROM student WHERE student_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_STR);

    if ($query->execute()) {
        echo "<script>window.location.href='addstudent.php';</script>";
    } else {
        echo "<script>alert('Failed to delete student!');</script>";
    }
}

// Fetch departments
$query = $dbh->prepare("SELECT dept_id, dept_name FROM departments");
$query->execute();
$departments = $query->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students
$sql = "SELECT student.*, departments.dept_name FROM student JOIN departments ON student.department_id = departments.dept_id";
$query = $dbh->prepare($sql);
$query->execute();
$students = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="home-content">
        <?php include_once('../includes/sidebar.php'); ?>

        <div class="home-content">
            <div class="home-page">
                <div class="main-content">
                    <section class="student-form">
                        <h2><?= !empty($student_id) ? 'Edit Student' : 'New Student' ?></h2>
                        <form method="post" action="addstudent.php" class="student-input-form">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($student_id) ?>">

                            <label>Student ID:</label>
                            <input type="text" name="student_id" class="input-field" value="<?= htmlspecialchars($student_id) ?>" required><br><br>

                            <label>Student Name:</label>
                            <input type="text" name="student_name" class="input-field" value="<?= htmlspecialchars($student_name) ?>" required><br><br>

                            <label>Department:</label>
                            <select name="department_id" class="input-field" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['dept_id'] ?>" <?= isset($department_id) && $department_id == $dept['dept_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['dept_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select><br><br>

                            <label>Contact Number:</label>
                            <input type="number" name="contact" class="input-field" value="<?= htmlspecialchars($contact) ?>" required><br><br>

                            <button type="submit" name="save_student" class="submit-button"><?= !empty($student_id) ? 'Update' : 'Submit' ?></button>
                        </form>
                    </section>

                    <section class="student-table">
                        <h2>View Students</h2>
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Department</th>
                                    <th>Contact</th>
                                    <th>Edit</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td><?= htmlspecialchars($student['student_name']) ?></td>
                                    <td><?= htmlspecialchars($student['dept_name']) ?></td>
                                    <td><?= htmlspecialchars($student['contact']) ?></td>
                                    <td>
                                        <a href="addstudent.php?edit_id=<?= $student['student_id'] ?>">
                                            <img src="../assets/images/edit.jpg" alt="Edit" width="20" height="20">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="addstudent.php?delete_id=<?= $student['student_id'] ?>" onclick="return confirm('Are you sure?')">
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
