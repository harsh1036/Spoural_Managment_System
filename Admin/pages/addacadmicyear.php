<?php
include('../includes/session_management.php');
include('../includes/config.php');
include_once('../includes/sidebar.php'); // Navbar

$year = '';
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = trim($_POST['year'] ?? '');

    // Validation: not empty, format YYYY-YY, not duplicate, and second year is first year + 1
    if (empty($year)) {
        $message = "Academic year is required.";
    } elseif (!preg_match('/^([0-9]{4})-([0-9]{2})$/', $year, $matches)) {
        $message = "Format must be YYYY-YY (e.g., 2024-25).";
    } else {
        $first_year = (int)$matches[1];
        $second_year = (int)$matches[2];
        $expected_second = ($first_year + 1) % 100; // last two digits of next year
        if ($second_year !== $expected_second) {
            $message = "Second year must be exactly one more than the first year (e.g., 2024-25, not 2024-26).";
        } else {
            // Check for duplicate
            $stmt = $conn->prepare("SELECT id FROM academic_years WHERE year = ?");
            $stmt->bind_param("s", $year);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $message = "This academic year already exists.";
            } else {
                // Insert
                $stmt_insert = $conn->prepare("INSERT INTO academic_years (year) VALUES (?)");
                $stmt_insert->bind_param("s", $year);
                if ($stmt_insert->execute()) {
                    $success = true;
                    $message = "Academic year added successfully!";
                    $year = '';
                } else {
                    $message = "Database error: " . $conn->error;
                }
                $stmt_insert->close();
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Academic Year</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .main-content {
            max-width: 500px;
            margin: 40px auto;
            background: var(--bg-secondary, #fff);
            border-radius: var(--border-radius, 16px);
            box-shadow: var(--box-shadow, 0 2px 8px rgba(0,0,0,0.08));
            padding: 32px;
        }
        .form-label { font-weight: 600; }
        .form-control { font-size: 18px; }
        .btn-primary {
            background: var(--primary-color, #2942a6);
            border: none;
            font-weight: 600;
        }
        .alert { margin-top: 20px; }
        .years-table {
            width: 100%;
            margin-top: 32px;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .years-table th, .years-table td {
            padding: 12px 18px;
            text-align: left;
        }
        .years-table th {
            background: var(--primary-color, #2942a6);
            color: #fff;
        }
        .years-table tr:nth-child(even) {
            background: #f6f8fa;
        }
    </style>
</head>
<body>
    <div class="home-content">
        <div class="main-content">
            <h2><i class='bx bxs-building-house'></i> Add Academic Year</h2>
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label for="year" class="form-label">Academic Year (e.g., 2024-25):</label>
                    <input type="text" class="form-control" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" required pattern="^[0-9]{4}-[0-9]{2}$" maxlength="7">
                </div><br>
                <button type="submit" class="btn btn-primary">Add Year</button>
            </form>

            <!-- Academic Years Table -->
            <h3 style="margin-top:40px;">All Academic Years</h3>
            <?php
            $result = $conn->query("SELECT year FROM academic_years ORDER BY year DESC");
            if ($result && $result->num_rows > 0):
            ?>
                <table class="years-table">
                    <thead>
                        <tr><th>#</th><th>Academic Year</th></tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['year']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="margin-top:24px; color:#888;">No academic years found.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once('../includes/footer.php'); ?>
</body>
</html>
