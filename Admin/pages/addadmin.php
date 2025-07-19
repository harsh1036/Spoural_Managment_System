<?php
include('../includes/session_management.php');
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
    $random_password = 12345; // Generate a random password

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

    $sql = "UPDATE admins SET status = 0 WHERE admin_id = :admin_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':admin_id', $admin_id, PDO::PARAM_STR);

    if ($query->execute()) {
        echo " <Script>window.location.href='addadmin.php';</script>";
    } else {
        echo "";
    }
}

// **FETCH ADMINS WITH OPTIONAL SEARCH**
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $sql = "SELECT * FROM admins WHERE status = 1 AND (admin_id LIKE :search OR admin_name LIKE :search)";
    $query = $dbh->prepare($sql);
    $likeSearch = "%$search%";
    $query->bindParam(':search', $likeSearch, PDO::PARAM_STR);
} else {
    $sql = "SELECT * FROM admins WHERE status = 1";
    $query = $dbh->prepare($sql);
}
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
                        <button type="submit"class="btn btn-primary" name="edit_admin">Submit</button>

                    <?php else: ?>
                        <button type="submit" class="btn btn-primary" name="add_admin">Submit</button>
                    <?php endif; ?>
                </form>
            </section>

            <section class="view-admin-details">
                <h2>View Admin Details</h2>
                <input type="text" id="adminSearch" placeholder="Search by Admin ID or Name" class="form-control mb-3" style="max-width: 300px;">
                <table border="2px" class="table table-bordered table-striped small-table" id="adminTable">
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
                                    <button type="button" 
                                            class="edit-admin btn btn-sm btn-primary"
                                            data-id="<?php echo htmlspecialchars($admin['admin_id']); ?>"
                                            data-name="<?php echo htmlspecialchars($admin['admin_name']); ?>">
                                        <i class='bx bx-edit'></i> Edit
                                    </button>
                                </td>
                                <td>
                                    <a href="addadmin.php?delete_id=<?php echo $admin['admin_id']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this admin?')">
                                        <i class='bx bx-trash'></i> Delete
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-admin').forEach(button => {
                button.addEventListener('click', function() {
                    // Get the data from the clicked button
                    const adminId = this.getAttribute('data-id');
                    const adminName = this.getAttribute('data-name');

                    // Populate the form fields
                    document.querySelector('input[name="admin_id"]').value = adminId;
                    document.querySelector('input[name="admin_name"]').value = adminName;

                    // Update form title and button
                    document.querySelector('.new-admin h2').textContent = 'Edit Admin';
                    document.querySelector('button[type="submit"]').name = 'edit_admin';
                    document.querySelector('button[type="submit"]').textContent = 'Update Admin';

                    // Scroll to form
                    document.querySelector('.new-admin').scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    </script>
    <script>
        // Live search for admin table
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('adminSearch');
            const table = document.getElementById('adminTable');
            searchInput.addEventListener('input', function() {
                const filter = searchInput.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const adminId = row.children[1].textContent.toLowerCase();
                    const adminName = row.children[2].textContent.toLowerCase();
                    if (adminId.includes(filter) || adminName.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>

</html>