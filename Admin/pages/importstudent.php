<?php
session_start();
include('../includes/config.php');
require 'SimpleXLSXGen.php'; // Ensure this file exists
require 'SimpleXLSX.php'; // Ensure this file exists
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

// Define the correct column headers
$expectedColumns = ['student_id', 'student_name', 'contact', 'dept_id'];

$message = "";

// Function to generate and download the Excel template
if (isset($_GET['download_template'])) {
    $data = [
        $expectedColumns, // Column headers
    ];
    $xlsx = SimpleXLSXGen::fromArray($data);
    $xlsx->downloadAs('Student_Template.xlsx');
    exit;
}

// Handle file upload and validation
if (isset($_POST['import'])) {
    if ($_FILES['excel_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];
        
        if ($xlsx = SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            
            // Validate column names
            if ($rows[0] !== $expectedColumns) {
                $message = "<div class='alert alert-danger'>Error: Column names do not match the expected format!</div>";
            } else {
                foreach (array_slice($rows, 1) as $row) {
                    $student_id = $row[0]; 
                    $student_name = $row[1]; 
                    $contact = $row[2]; 
                    $department_id = $row[3];

                    $sql = "INSERT INTO student (student_id, student_name, contact, dept_id) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        $message = "<div class='alert alert-danger'>SQL Error: " . $conn->error . "</div>";
                        break;
                    }
                    $stmt->bind_param("sssi", $student_id, $student_name, $contact, $department_id);
                    $stmt->execute();
                }
                $message = "<div class='alert alert-success'>Data imported successfully!</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Failed to parse Excel file!</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Error uploading file!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Student Data | SPORUAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Custom Styles -->
</head>
<body class="bg-light">
<?php include_once('../includes/sidebar.php'); ?>
<br><br>
<div class="home-content">
    <br><br>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>📂 Import Student Data</h4>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>