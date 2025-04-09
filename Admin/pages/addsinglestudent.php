<?php
include('../includes/session_management.php');
include('../includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['login'])) {
    header('location:../index.php');
    exit();
}

// Initialize variables
$student_id = '';
$student_name = '';
$contact = '';
$dept_id = '';

// Fetch departments
try {
    $query = $dbh->prepare("SELECT dept_id, dept_name FROM departments");
    $query->execute();
    $departments = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = [];
}

// Check if editing a student
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    try {
        $stmt = $dbh->prepare("SELECT * FROM student WHERE student_id = :id");
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $student_id = $student['student_id'];
            $student_name = $student['student_name'];
            $contact = $student['contact'] ?? '';
            $dept_id = $student['dept_id'] ?? '';
        }
    } catch (PDOException $e) {
        echo "<script>alert('Error fetching student data: " . $e->getMessage() . "');</script>";
    }
}

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

// Fetch all students
try {
    $sql = "SELECT s.student_id, s.student_name, s.contact, d.dept_name
            FROM student s
            JOIN departments d ON s.dept_id = d.dept_id";
    $query = $dbh->prepare($sql);
    $query->execute();
    $students = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $students = [];
    echo "<script>alert('Error fetching students: " . $e->getMessage() . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Single Student | Spoural Event System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php include_once('../includes/sidebar.php'); ?>

    <div class="home-content">
        <div class="container-fluid px-4">
            <div class="form-container">
                <h2 class="form-title"><?= !empty($student_id) ? 'Edit Student' : 'Add New Student' ?></h2>

                <form method="post" action="">
                    <?php if (!empty($student_id)): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($student_id) ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Student Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($student_name) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($contact) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-select">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['dept_id']) ?>" <?= ($dept_id == $dept['dept_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['dept_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="submit" class="btn btn-primary">
                            <?= !empty($student_id) ? 'Update Student' : 'Add Student' ?>
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
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td><?= htmlspecialchars($student['student_name']) ?></td>
                                    <td><?= htmlspecialchars($student['contact'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($student['dept_name'] ?? '') ?></td>
                                    <td>
                                        <a href="?edit_id=<?= $student['student_id'] ?>" class="btn btn-sm btn-primary">
                                            <i class='bx bx-edit'></i> Edit
                                        </a>
                                        <a href="?delete_id=<?= $student['student_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                            <i class='bx bx-trash'></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No students found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include_once('../includes/footer.php'); ?>
</body>

</html>
