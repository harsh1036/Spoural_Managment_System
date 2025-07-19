<?php
include('../includes/session_management.php');
include('../includes/config.php');
include_once('../includes/sidebar.php'); // Navbar

// Fetch academic years for dropdown
$years = [];
$res = $conn->query("SELECT year FROM academic_years ORDER BY year DESC");
while ($row = $res->fetch_assoc()) $years[] = $row['year'];

// Fetch events
$events = [];
$res = $conn->query("SELECT id, event_name FROM events ORDER BY event_name ASC");
while ($row = $res->fetch_assoc()) $events[] = $row;

// Fetch departments
$departments = [];
$res = $conn->query("SELECT dept_id, dept_name FROM departments ORDER BY dept_name ASC");
while ($row = $res->fetch_assoc()) $departments[] = $row;

// Handle template upload
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_template'])) {
    $academic_year = $_POST['academic_year'];
    // Check if template exists for this year
    $stmt = $conn->prepare("SELECT id FROM certificate_templates WHERE academic_year = ?");
    $stmt->bind_param("s", $academic_year);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $upload_message = "A template for this academic year already exists.";
    } else {
        if (isset($_FILES['template']) && $_FILES['template']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['template']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                $upload_dir = '../uploads/cert_templates/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $filename = 'year_' . $academic_year . '_' . time() . '.' . $ext;
                $filepath = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['template']['tmp_name'], $filepath)) {
                    $stmt2 = $conn->prepare("INSERT INTO certificate_templates (academic_year, file_path) VALUES (?, ?)");
                    $stmt2->bind_param("ss", $academic_year, $filepath);
                    $stmt2->execute();
                    $stmt2->close();
                    $upload_message = "Template uploaded successfully!";
                } else {
                    $upload_message = "Failed to upload file.";
                }
            } else {
                $upload_message = "Only PDF, JPG, and PNG files are allowed.";
            }
        } else {
            $upload_message = "No file uploaded or upload error.";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Academic Year Certificates</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="home-content">
    <div class="container" style="max-width:700px;margin:40px auto;">
        <h2>Upload Certificate Template (Per Academic Year)</h2>
        <?php if ($upload_message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($upload_message); ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Select Academic Year:</label>
                <select name="academic_year" required>
                    <option value="">-- Select Academic Year --</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Certificate Template (PDF/JPG/PNG):</label>
                <input type="file" name="template" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <button type="submit" name="upload_template" class="btn btn-primary">Upload Template</button>
        </form>

        <hr>

        <h2>Download Certificates</h2>
        <form method="get" action="download_certificates.php">
            <div class="mb-3">
                <label>Select Academic Year:</label>
                <select name="academic_year" required>
                    <option value="">-- Select Academic Year --</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Download Type:</label>
                <select name="download_type" id="download_type" onchange="toggleDownloadType()" required>
                    <option value="">-- Select Type --</option>
                    <option value="event">Event-wise</option>
                    <option value="department">Department-wise</option>
                </select>
            </div>
            <div class="mb-3" id="event_select_div" style="display:none;">
                <label>Select Event:</label>
                <select name="event_id">
                    <option value="">-- Select Event --</option>
                    <option value="all">-- All Events --</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3" id="department_select_div" style="display:none;">
                <label>Select Department:</label>
                <select name="dept_id">
                    <option value="">-- Select Department --</option>
                    <option value="all">-- All Departments --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['dept_id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Download Certificates</button>
        </form>
    </div>
</div>
<script>
function toggleDownloadType() {
    var type = document.getElementById('download_type').value;
    document.getElementById('event_select_div').style.display = (type === 'event') ? 'block' : 'none';
    document.getElementById('department_select_div').style.display = (type === 'department') ? 'block' : 'none';
}
</script>
<?php include_once('../includes/footer.php'); ?>
</body>
</html>
