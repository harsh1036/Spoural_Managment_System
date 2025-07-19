<?php
include('../includes/session_management.php');
include('../includes/config.php');
include_once('../includes/sidebar.php'); // Navbar

// Fetch academic years for dropdown
$years = [];
$res = $conn->query("SELECT id, year FROM academic_years ORDER BY year DESC");
if (!$res) {
    die("SQL Error: " . $conn->error);
}
while ($row = $res->fetch_assoc()) $years[] = $row;
// Fetch years that already have a template
$used_year_ids = [];
$res = $conn->query("SELECT academic_year_id FROM certificate_templates");
while ($row = $res->fetch_assoc()) $used_year_ids[] = $row['academic_year_id'];

// Handle template upload
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_template'])) {
    $academic_year_id = $_POST['academic_year_id'];
    // Check if template exists for this year
    $stmt = $conn->prepare("SELECT id FROM certificate_templates WHERE academic_year_id = ?");
    $stmt->bind_param("i", $academic_year_id);
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
                $filename = 'year_' . $academic_year_id . '_' . time() . '.' . $ext;
                $filepath = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['template']['tmp_name'], $filepath)) {
                    $stmt2 = $conn->prepare("INSERT INTO certificate_templates (academic_year_id, file_path) VALUES (?, ?)");
                    $stmt2->bind_param("is", $academic_year_id, $filepath);
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
    <title>Certificate Templates</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
    .card-container {
      display: flex;
      gap: 2rem;
      justify-content: center;
      margin: 2rem 0;
    }
    .upload-card {
      background: #f5f8fa;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08);
      padding: 2rem 2.5rem;
      min-width: 320px;
      text-align: center;
      transition: box-shadow 0.2s, background 0.2s;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .upload-card:hover {
      background: #fff;
      box-shadow: 0 8px 32px rgba(44, 62, 80, 0.12);
    }
    .icon-title-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 0.5rem;
    }
    .icon-title-row i {
      font-size: 2.2rem;
      color: #2236d1;
    }
    .card-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: #222;
    }
    .card-subtitle {
      color: #888;
      font-size: 1rem;
      margin: 0;
    }
    .content-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08);
      padding: 2rem 2.5rem;
      margin: 2rem auto;
      max-width: 600px;
      display: none;
    }
    .content-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .content-header h2 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 700;
      color: #2236d1;
    }
    .btn-primary, .btn-success {
      min-width: 120px;
    }
    </style>
</head>
<body>
<?php include_once('../includes/sidebar.php'); ?>
<div class="home-content">
    <div class="content-header">
        <i class='bx bx-certification' style="font-size:2rem;color:#2236d1;"></i>
        <h2>Certificate Templates</h2>
    </div>
    <div class="card-container">
        <div class="upload-card" id="uploadCertCard">
            <div class="icon-title-row">
                <i class='bx bx-upload'></i>
                <span class="card-title">Upload Certificate Template</span>
            </div>
            <p class="card-subtitle">Upload a template for a specific academic year</p>
        </div>
        <div class="upload-card" id="downloadCertCard">
            <div class="icon-title-row">
                <i class='bx bx-download'></i>
                <span class="card-title">Download Certificates</span>
            </div>
            <p class="card-subtitle">Download certificates for a year</p>
        </div>
    </div>
    <div class="content-card" id="uploadCertContent">
        <div class="content-header">
            <i class='bx bx-upload'></i>
            <h2>Upload Certificate Template (Per Academic Year)</h2>
        </div>
        <?php if ($upload_message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($upload_message); ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Select Academic Year:</label>
                <select name="academic_year_id" class="form-control" required>
                    <option value="">-- Select Academic Year --</option>
                    <?php foreach ($years as $year): ?>
                        <?php if (!in_array($year['id'], $used_year_ids)): ?>
                            <option value="<?= htmlspecialchars($year['id']) ?>"><?= htmlspecialchars($year['year']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Certificate Template (PDF/JPG/PNG):</label>
                <input type="file" name="template" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <button type="submit" name="upload_template" class="btn btn-primary">Upload Template</button>
        </form>
    </div>
    <div class="content-card" id="downloadCertContent">
        <div class="content-header">
            <i class='bx bx-download'></i>
            <h2>Download Certificates</h2>
        </div>
        <form method="get" action="download_certificates.php">
            <div class="mb-3">
                <label>Select Academic Year:</label>
                <select name="academic_year" class="form-control" required>
                    <option value="">-- Select Academic Year --</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo htmlspecialchars($year['id']); ?>"><?php echo htmlspecialchars($year['year']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Download Certificates</button>
        </form>
    </div>
</div>
<?php include_once('../includes/footer.php'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var uploadCard = document.getElementById('uploadCertCard');
    var downloadCard = document.getElementById('downloadCertCard');
    var uploadContent = document.getElementById('uploadCertContent');
    var downloadContent = document.getElementById('downloadCertContent');

    function showUpload() {
        uploadContent.style.display = 'block';
        downloadContent.style.display = 'none';
        uploadCard.classList.add('active');
        downloadCard.classList.remove('active');
    }
    function showDownload() {
        uploadContent.style.display = 'none';
        downloadContent.style.display = 'block';
        uploadCard.classList.remove('active');
        downloadCard.classList.add('active');
    }
    if (uploadCard && downloadCard && uploadContent && downloadContent) {
        uploadCard.addEventListener('click', showUpload);
        downloadCard.addEventListener('click', showDownload);
        // Hide both by default
        uploadContent.style.display = 'none';
        downloadContent.style.display = 'none';
        uploadCard.classList.remove('active');
        downloadCard.classList.remove('active');
    }
});
</script>
</body>
</html>
