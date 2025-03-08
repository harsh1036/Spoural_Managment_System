<?php
session_start();
include('../includes/config.php');



// Fetch session data
$admin_username = $_SESSION['login'];
// Function to generate a random password
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    return substr(str_shuffle($chars), 0, $length);
}

// INSERT ULSC
if (isset($_POST['add_ulsc'])) {
    $ulsc_id = $_POST['ulsc_id'];
    $ulsc_name = $_POST['ulsc_name'];
    $ulsc_dept =$_POST['ulsc_dept'];
    $ulsc_cont=$_POST['ulsc_cont'];
    $random_password = generateRandomPassword(); // Generate a random password
   

    $sql = "INSERT INTO ulsc (ulsc_id, ulsc_name,department,contact, password) VALUES (:ulsc_id, :ulsc_name,:ulsc_dept,:ulsc_cont, :password)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
    $query->bindParam(':ulsc_name', $ulsc_name, PDO::PARAM_STR);
    $query->bindParam(':ulsc_dept', $ulsc_id, PDO::PARAM_STR);
    $query->bindParam(':ulsc_cont', $ulsc_name, PDO::PARAM_STR);
    $query->bindParam(':password', $random_password, PDO::PARAM_STR);

    if ($query->execute()) {
        echo "<script> window.location.href='addulsc.php';</script>";
    } else {
        echo "";
    }
}

// FETCH DATA FOR EDIT
$editData = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $sql = "SELECT * FROM ulsc WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $editData = $query->fetch(PDO::FETCH_ASSOC);
}

// UPDATE ULSC
if (isset($_POST['update_ulsc'])) {
    $id = $_POST['id'];
    $ulsc_id = $_POST['ulsc_id'];
    $ulsc_name = $_POST['ulsc_name'];

    $sql = "UPDATE ulsc SET ulsc_id=:ulsc_id, ulsc_name=:ulsc_name WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
    $query->bindParam(':ulsc_name', $ulsc_name, PDO::PARAM_STR);

    if ($query->execute()) {
        echo "<script>window.location.href='addulsc.php';</script>";
    } else {
        echo "<script>alert('Error Updating ULSC');</script>";
    }
}

// DELETE ULSC
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $sql = "DELETE FROM ulsc WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);

    if ($query->execute()) {
        // Reset Auto Increment
        $dbh->exec("ALTER TABLE ulsc AUTO_INCREMENT = 1");
        echo "<script>window.location.href='addulsc.php';</script>";
    } else {
        echo "<script>alert('Error Deleting ULSC');</script>";
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
   
</head>

<body>
<div class="home-content">
        <?php
        include_once('../includes/sidebar.php');
        ?>

    <div class="home-content">
        <div class="home-page">
        <section class="new-admin">
        <h2><?= isset($editData) ? 'Edit ULSC' : 'New ULSC' ?></h2>
                <form action="" method="POST">
                    <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
                    <label>ULSC ID: </label><input type="text" name="ulsc_id" value="<?= $editData['ulsc_id'] ?? '' ?>"
                        required>
                    <label>ULSC Name: </label><input type="text" name="ulsc_name"
                        value="<?= $editData['ulsc_name'] ?? '' ?>" required><br><br>
                    <label>ULSC Department: </label><input type="text" name="ulsc_dept"
                        value="<?= $editData['ulsc_dept'] ?? '' ?>" required>
                    <label>ULSC Contact number: </label><input type="text" name="ulsc_cont"
                        value="<?= $editData['ulsc_cont'] ?? '' ?>" required>
                    <br><br><center>
                    <?php if (isset($editData)): ?>
                        <button type="submit" name="update_ulsc">Submit</button>
                    
                    <?php else: ?>
                        <button type="submit" name="add_ulsc">Submit</button>
                    <?php endif; ?></center>
                </form>
            </section>

            <section class="view-admin-details">
            <h2>View ULSC Details</h2>
                <table border="2px" class="table table-bordered table-striped small-table">
                    <thead>
                        <tr>
                            <th>Sr.no</th>
                            <th>ULSC ID</th>
                            <th>ULSC NAME</th>
                            <th>ULSC Department</th>
                            <th>ULSC Contact Number</th>
                            <th>Edit</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM ulsc";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_ASSOC);
                        $sr = 1;
                        foreach ($results as $row) { ?>
                            <tr>
                                <td><?= $sr ?></td>
                                <td><?= htmlspecialchars($row['ulsc_id']) ?></td>
                                <td><?= htmlspecialchars($row['ulsc_name']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= htmlspecialchars($row['Contact']) ?></td>
                             
                                <td>
                                    <a href="addulsc.php?edit_id=<?= $row['id'] ?>">
                                        <img src="../assets/images/edit.jpg" alt="Edit" width="20" height="20">
                                    </a>
                                </td>
                                <td>
                                    <a href="addulsc.php?delete_id=<?= $row['id'] ?>"
                                        onclick="return confirm('Are you sure?')">
                                        <img src="../assets/images/delete.jpg" alt="Edit" width="20" height="20">
                                    </a>
                                </td>
                            </tr>
                            <?php
                            $sr++;
                        } ?>
                    </tbody>

                </table>
            </section>
            <a href="download.php" class="btn btn-primary" style="margin-top: 10px;">Download PDF</a>

        </div>
    </div>
                        </section>
</div>

<?php
                        include_once('../includes/footer.php');
        ?>
    
</body>

</html>