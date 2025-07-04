<?php
// Enable output buffering
ob_start();

// Set error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 0);

include('../includes/session_management.php');
include('../includes/config.php');

$event_id = intval($_GET['event_id'] ?? 0);
if (!$event_id) {
    ob_end_clean(); // Clean the output buffer
    die("Invalid event selected.");
}

// Always use the common template
$template_path = 'certificate.jpg'; // or .png
if (!file_exists($template_path)) {
    ob_end_clean();
    die("No certificate template found. Please upload one.");
}

$user_dept = $_SESSION['department']; // e.g., "CSPIT-CE"


// Fetch participants and their details
$sql = "SELECT p.*, s.student_name AS student_name, e.event_name AS event_name, d.dept_name AS department_name
        FROM participants p
        JOIN student s ON s.student_id = CAST(p.student_id AS UNSIGNED)
        JOIN events e ON p.event_id = e.id
        LEFT JOIN departments d ON p.dept_id = d.dept_id
        WHERE p.event_id = ? AND d.dept_name = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    ob_end_clean(); // Clean the output buffer
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("is", $event_id, $user_dept);
$stmt->execute();
$result = $stmt->get_result();
$participants = [];
while ($row = $result->fetch_assoc()) {
    $participants[] = $row;
}
$stmt->close();

if (empty($participants)) {
    ob_end_clean(); // Clean the output buffer
    die("No participants found for this event.");
}

// Generate a single PDF with all certificates
require_once '../../tcpdf/tcpdf.php'; // Adjust path if needed
$pdf = new TCPDF('L', 'mm', 'A4'); // Landscape, A4

foreach ($participants as $participant) {
    $pdf->AddPage();
    $pdf->Image($template_path, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

    // Name (centered, large, bold)
    $pdf->SetFont('times', 'B', 28);
    $pdf->SetTextColor(44, 62, 80);
    $pdf->SetXY(0, 70); // Adjust Y to match your template
    $pdf->Cell(250, 15, $participant['student_name'], 0, 1, 'C');

    // Event and Department (example, adjust as needed)
    $pdf->SetFont('times', 'B', 16);
    $pdf->SetTextColor(255, 87, 34); // Orange for event
    $pdf->SetXY(100, 95);
    $pdf->Cell(60, 10, $participant['event_name'] . " of " . $participant['department_name'], 0, 0, 'L');
}

// Clean the output buffer
ob_end_clean();

// Output the PDF for download
$pdf->Output('certificates_event_' . $event_id . '.pdf', 'D');
exit;
?>
