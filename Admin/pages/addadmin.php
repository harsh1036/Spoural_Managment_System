<?php

session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login

// Fetch session data
$admin_username = $_SESSION['login'];
function generateRandomPassword($length = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    return substr(str_shuffle($chars), 0, $length);
}

$message = "";

// **FETCH ADMIN DATA FOR EDITING**
$editData = null;
if (isset($_GET['edit_id'])) {
    $admin_id = $_GET['edit_id'];
    $sql = "SELECT * FROM admins WHERE admin_id=:admin_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':admin_id', $admin_id, PDO::PARAM_STR);
    $query->execute();
    $editData = $query->fetch(PDO::FETCH_ASSOC);
}

// **INSERT ADMIN**
if (isset($_POST['add_admin'])) {
    $admin_name = $_POST['admin_name'];
    $admin_id = $_POST['admin_id'];
    $random_password = generateRandomPassword(); // Generate a random password

    $sql = "INSERT INTO admins (admin_id, admin_name, password) VALUES (:admin_id, :admin_name, :password)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':admin_id', $admin_id, PDO::PARAM_STR);
    $query->bindParam(':admin_name', $admin_name, PDO::PARAM_STR);
    $query->bindParam(':password', $random_password, PDO::PARAM_STR);

    if ($query->execute()) {
        echo "<script> window.location.href='addadmin.php';</script>";
    } else {
        echo "";
    }
}

// **UPDATE ADMIN**
if (isset($_POST['edit_admin'])) {
    $admin_id = $_POST['admin_id'];
    $admin_name = $_POST['admin_name'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $editData['password'];

    $sql = "UPDATE admins SET admin_name = :admin_name, password = :password WHERE admin_id = :admin_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':admin_id', $admin_id, PDO::PARAM_STR);
    $query->bindParam(':admin_name', $admin_name, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);

    if ($query->execute()) {
        echo "<script> window.location.href='addadmin.php';</script>";
    } else {
        echo "";
    }
}

// **DELETE ADMIN**
if (isset($_GET['delete_id'])) {
    $admin_id = $_GET['delete_id'];

    $sql = "DELETE FROM admins WHERE admin_id = :admin_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':admin_id', $admin_id, PDO::PARAM_STR);

    if ($query->execute()) {
        echo " <Script>window.location.href='addadmin.php';</script>";
    } else {
        echo "";
    }
}

// **FETCH ALL ADMINS**
$sql = "SELECT * FROM admins";
$query = $dbh->prepare($sql);
$query->execute();
$admins = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spoural Management System - Admin</title>
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
                <h2><?= isset($editData) ? 'Edit Admin' : 'New Admin' ?></h2>
                <form method="POST" action="addadmin.php">
                    <label>Admin ID:</label>
                    <input type="text" name="admin_id" value="<?= $editData['admin_id'] ?? '' ?>">
                    <label>Admin Name:</label>
                    <input type="text" name="admin_name" value="<?= $editData['admin_name'] ?? '' ?>" required>
                    <?php if (isset($editData)): ?>
                        <button type="submit" name="edit_admin">Submit</button>

                    <?php else: ?>
                        <button type="submit" name="add_admin">Submit</button>
                    <?php endif; ?>
                </form>
            </section>

            <section class="view-admin-details">
                <h2>View Admin Details</h2>
                <table border="2px" class="table table-bordered table-striped small-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Admin ID</th>
                            <th>Admin Name</th>
                            <th>Edit</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['id']); ?></td>
                                <td><?php echo htmlspecialchars($admin['admin_id']); ?></td>
                                <td><?php echo htmlspecialchars($admin['admin_name']); ?></td>
                                <td>
                                    <a href="addadmin.php?edit_id=<?php echo $admin['admin_id']; ?>">
                                        <img src="../assets/images/edit.jpg" alt="Edit" width="20" height="20">
                                    </a>
                                </td>
                                <td>
                                    <a href="addadmin.php?delete_id=<?php echo $admin['admin_id']; ?>"
                                        onclick="return confirm('Are you sure you want to delete this admin?');">
                                        <img src="../assets/images/delete.jpg" alt="Edit" width="20" height="20">
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
                        </section>
</div>

<?php
                        include_once('../includes/footer.php');
        ?>
</body>

</html>