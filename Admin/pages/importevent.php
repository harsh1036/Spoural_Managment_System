<?php
include('../includes/config.php');
require 'SimpleXLSX.php'; // Ensure this file exists


use Shuchkin\SimpleXLSX;

if (isset($_POST['import'])) {
    if ($_FILES['excel_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];

        if ($xlsx = SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();

            foreach (array_slice($rows, 1) as $row) {
                // Ensure you extract the right number of columns
                $id = $row[0]; 
                $event_name = $row[1]; 
                $event_type = $row[2]; 
                $min_participants = $row[3]; 
                $max_participants = $row[4];

            
                // Prepare SQL query with exactly 4 placeholders
                $sql = "INSERT INTO events (id, event_name, event_type, min_participants,max_participants) VALUES (?,?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die("SQL Error: " . $conn->error);
                }

                // Ensure data types match: 's' (string), 's' (string), 's' (string for contact), 'i' (integer)
                $stmt->bind_param("issii",$id,$event_name,$event_type,$min_participants,$max_participants);
                $stmt->execute();
            }

            echo "Data imported successfully!";
        } else {
            echo "Failed to parse Excel file!";
        }
    } else {
        echo "Error uploading file!";
    }
}
?><?php
session_start();
include('../includes/config.php');

// Check if user is logged in, else redirect to login


// Fetch session data
$admin_username = $_SESSION['login'];


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Excel | SPORUAL Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Your custom styles -->
</head>

<body class="bg-light">
<?php
        include_once('../includes/sidebar.php');
        ?> <br><br>
        <div class="home-content">
            <br><br>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>📂 Import Event Data</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">Upload Excel File (.xlsx)</label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="import" class="btn btn-success">
                                    📥 Import Data
                                </button>
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
