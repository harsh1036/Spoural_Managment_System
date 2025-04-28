<?php
include('../includes/session_management.php');
include('../includes/config.php');
include('sendMail.php');

// Fetch session data
$admin_username = $_SESSION['login'];

// Function to generate a random password
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    return substr(str_shuffle($chars), 0, $length);
}

// Initialize variables
$ulsc_id = $ulsc_name = $dept_id = $contact = "";

// **FETCH DATA FOR EDITING**
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $sql = "SELECT * FROM ulsc WHERE id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $editData = $query->fetch(PDO::FETCH_ASSOC);

    if ($editData) {
        $ulsc_id = $editData['ulsc_id'];
        $ulsc_name = $editData['ulsc_name'];
        $dept_id = $editData['dept_id'];
        $contact = $editData['contact'];
    }
}

// **INSERT OR UPDATE ULSC**
if (isset($_POST['save_ulsc'])) {
    $id = $_POST['id'];
    $ulsc_id = $_POST['ulsc_id'];
    $ulsc_name = $_POST['ulsc_name'];
    $dept_id = $_POST['dept_id'];
    $contact = $_POST['contact'];
    $plain_password = "1234"; // Default password
    $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT); // Hash the password

    // Generate email ID
    $email = $ulsc_id . "@charusat.edu.in";

    if (!empty($id)) {
        // For update, don't change password and email
        $sql = "UPDATE ulsc SET ulsc_id = :ulsc_id, ulsc_name = :ulsc_name, dept_id = :dept_id, contact = :contact WHERE id = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_INT);
    } else {
        // For new entry, include email and hashed password
        $sql = "INSERT INTO ulsc (ulsc_id, ulsc_name, dept_id, contact, email, password) VALUES (:ulsc_id, :ulsc_name, :dept_id, :contact, :email, :password)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    }

    $query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
    $query->bindParam(':ulsc_name', $ulsc_name, PDO::PARAM_STR);
    $query->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
    $query->bindParam(':contact', $contact, PDO::PARAM_INT);

    if ($query->execute()) {
        if (empty($id)) {
            // Only send email for new entries
            if (sendULSCEmail($ulsc_name, $email, $plain_password)) {
                echo "<script>alert('ULSC added successfully with email: " . $email . " and default password: 1234');</script>";
            } else {
                echo "<script>alert('ULSC added successfully with email: " . $email . " and default password: 1234. Email sending failed.');</script>";
            }
        } else {
            echo "<script>alert('ULSC updated successfully!');</script>";
        }
        echo "<script>window.location.href='addulsc.php';</script>";
    } else {
        echo "<script>alert('Error adding/updating ULSC');</script>";
    }
}

// **DELETE ULSC**
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $sql = "DELETE FROM ulsc WHERE id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);

    if ($query->execute()) {
        echo "<script>window.location.href='addulsc.php';</script>";
    } else {
        echo "<script>alert('Error deleting ULSC');</script>";
    }
}

// Fetch departments from database
$sql = "SELECT dept_id, dept_name FROM departments";
$query = $dbh->prepare($sql);
$query->execute();
$departments = $query->fetchAll(PDO::FETCH_ASSOC);

// Fetch all ULSC data
$sql = "SELECT ulsc.*, departments.dept_name FROM ulsc JOIN departments ON ulsc.dept_id = departments.dept_id";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spoural Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="home-content">
        <?php include_once('../includes/sidebar.php'); ?>

        <div class="home-content">
            <div class="home-page">
                <div class="main-content">
                    <section class="ulsc-form">
                        <h2><?= !empty($id) ? 'Edit ULSC' : 'New ULSC' ?></h2>
                        <form method="post" action="addulsc.php" class="ulsc-input-form">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($id ?? '') ?>">

                            <label>ULSC ID:</label>
                            <input type="text" name="ulsc_id" class="input-field" value="<?= htmlspecialchars($ulsc_id) ?>" required><br><br>

                            <label>ULSC Name:</label>
                            <input type="text" name="ulsc_name" class="input-field" value="<?= htmlspecialchars($ulsc_name) ?>" required><br><br>

                            <label>Department:</label>
                            <select name="dept_id" class="input-field" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['dept_id'] ?>" <?= isset($dept_id) && $dept_id == $dept['dept_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['dept_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select><br><br>

                            <label>Contact Number:</label>
                            <input type="number" name="contact" class="input-field" value="<?= htmlspecialchars($contact) ?>" required><br><br>

                            <button type="submit" name="save_ulsc" class="submit-button"><?= !empty($id) ? 'Update' : 'Submit' ?></button>
                        </form>
                    </section>

                    <section class="ulsc-table">
                        <h2>View ULSC Details</h2>
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
                                $sr = 1;
                                foreach ($results as $row) { ?>
                                    <tr>
                                        <td><?= $sr ?></td>
                                        <td><?= htmlspecialchars($row['ulsc_id']) ?></td>
                                        <td><?= htmlspecialchars($row['ulsc_name']) ?></td>
                                        <td><?= htmlspecialchars($row['dept_name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact']) ?></td>
                                        <td>
                                            
                                        <a href="addsingleulsc.php?edit_id=<?= $row['id'] ?>">
                                                <img src="../assets/images/edit.jpg" alt="Edit" width="20" height="20">
                                            </a>
                                        </td>
                                        <td>
                                            <a href="addsingleulsc.php?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">
                                                <img src="../assets/images/delete.jpg" alt="Delete" width="20" height="20">
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                    $sr++;
                                } ?>
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
