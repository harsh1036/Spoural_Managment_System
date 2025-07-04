<?php
include('../includes/session_management.php');
include('../includes/config.php');
include_once('../includes/sidebar.php'); // Navbar

// Handle template upload
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_template'])) {
    $event_id = intval($_POST['event_id']);
    if (isset($_FILES['template']) && $_FILES['template']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['template']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $upload_dir = '../uploads/cert_templates/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = 'event_' . $event_id . '_' . time() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['template']['tmp_name'], $filepath)) {
                $stmt = $conn->prepare("INSERT INTO certificate_templates (event_id, file_path) VALUES (?, ?)");
                $stmt->bind_param("is", $event_id, $filepath);
                $stmt->execute();
                $stmt->close();
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

// Fetch events for dropdown
$events = [];
$res = $conn->query("SELECT id, event_name FROM events ORDER BY event_name ASC");
if (!$res) {
    die("Query failed: " . $conn->error);
}
while ($row = $res->fetch_assoc()) $events[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Event Certificates</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="home-content">
    <div class="container" style="max-width:700px;margin:40px auto;">
        <h2>Upload Certificate Template</h2>
        <br>
        <?php if ($upload_message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($upload_message); ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Select Event:</label>
                <select name="event_id" required>
                    <option value="">-- Select Event --</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <br>
            <div class="mb-3">
                <label>Certificate Template (PDF/JPG/PNG):</label>
                <input type="file" name="template" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <br>
            <button type="submit" name="upload_template" class="btn btn-primary">Upload Template</button>
        </form>
        <br>
        <hr>
        <br>

        <h2>Download Certificates</h2>
        <br>
        <form method="get" action="download_certificates.php">
            <div class="mb-3">
                <label>Select Event:</label>
                <select name="event_id" required>
                    <option value="">-- Select Event --</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <br>
            <button type="submit" class="btn btn-success">Download Certificates</button>
        </form>
    </div>
</div>
<?php include_once('../includes/footer.php'); ?>
</body>
</html>
