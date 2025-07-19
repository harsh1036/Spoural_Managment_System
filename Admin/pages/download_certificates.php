<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Enable output buffering
ob_start();

include('../includes/session_management.php');
include('../includes/config.php');

$academic_year = $_GET['academic_year'] ?? '';
if (!$academic_year) die("Invalid academic year selected.");

$type = $_GET['download_type'] ?? '';

if ($type === 'event' && ($_GET['event_id'] ?? '') === 'all') {
    // All events for the selected academic year
    $sql = "SELECT p.*, s.student_name, e.event_name, d.dept_name AS department_name
            FROM participants p
            JOIN student s ON s.student_id = CAST(p.student_id AS UNSIGNED)
            JOIN events e ON p.event_id = e.id
            LEFT JOIN departments d ON p.dept_id = d.dept_id
            WHERE p.academic_year_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $academic_year);
} elseif ($type === 'department' && ($_GET['dept_id'] ?? '') === 'all') {
    // All departments for the selected academic year
    $sql = "SELECT p.*, s.student_name, e.event_name, d.dept_name AS department_name
            FROM participants p
            JOIN student s ON s.student_id = CAST(p.student_id AS UNSIGNED)
            JOIN events e ON p.event_id = e.id
            LEFT JOIN departments d ON p.dept_id = d.dept_id
            WHERE p.academic_year_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $academic_year);
} elseif ($type === 'event') {
    $event_id = intval($_GET['event_id'] ?? 0);
    if (!$event_id) die("Invalid event selected.");
    // Fetch participants for this event
    $sql = "SELECT p.*, s.student_name, e.event_name, d.dept_name AS department_name
            FROM participants p
            JOIN student s ON s.student_id = CAST(p.student_id AS UNSIGNED)
            JOIN events e ON p.event_id = e.id
            LEFT JOIN departments d ON p.dept_id = d.dept_id
            WHERE p.event_id = ? AND p.academic_year_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("is", $event_id, $academic_year);
} elseif ($type === 'department') {
    $dept_id = intval($_GET['dept_id'] ?? 0);
    if (!$dept_id) die("Invalid department selected.");
    // Fetch participants for this department (all events)
    $sql = "SELECT p.*, s.student_name, e.event_name, d.dept_name AS department_name
            FROM participants p
            JOIN student s ON s.student_id = CAST(p.student_id AS UNSIGNED)
            JOIN events e ON p.event_id = e.id
            LEFT JOIN departments d ON p.dept_id = d.dept_id
            WHERE p.dept_id = ? AND p.academic_year_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("is", $dept_id, $academic_year);
} elseif ($type === '' || empty($type)) {
    // No download_type provided: fetch all participants for the academic year
    $sql = "SELECT p.*, s.student_name, e.event_name, d.dept_name AS department_name
            FROM participants p
            JOIN student s ON s.student_id = CAST(p.student_id AS UNSIGNED)
            JOIN events e ON p.event_id = e.id
            LEFT JOIN departments d ON p.dept_id = d.dept_id
            WHERE p.academic_year_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $academic_year);
} else {
    die("Invalid download type.");
}

$stmt->execute();
$result = $stmt->get_result();
$participants = [];
while ($row = $result->fetch_assoc()) {
    $participants[] = $row;
}
$stmt->close();

if (empty($participants)) die("No participants found for this selection.");

// Remove duplicate participants by student_id
$unique_participants = [];
$seen_students = [];
foreach ($participants as $row) {
    if (!in_array($row['student_id'], $seen_students)) {
        $unique_participants[] = $row;
        $seen_students[] = $row['student_id'];
    }
}
$participants = $unique_participants;

// Always use the common template
$template_path = 'certificate.jpg'; // or .png
if (!file_exists($template_path)) {
    ob_end_clean();
    die("No certificate template found. Please upload one.");
}

// Generate a single PDF with only the first certificate
require_once '../../tcpdf/tcpdf.php'; // Adjust path if needed
$pdf = new TCPDF('L', 'mm', 'A4'); // Landscape, A4
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);

foreach ($participants as $participant) {
    $pdf->AddPage();
    $pdf->Image($template_path, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

    // Name (centered, large, bold)
    $pdf->SetFont('times', 'B', 28);
    $pdf->SetTextColor(44, 62, 80);
    $pdf->SetXY(20, 85); // Adjust Y to match your template
    $pdf->Cell(250, 15, $participant['student_name'], 0, 1, 'C');

    // Event and Department (example, adjust as needed)
    $pdf->SetFont('times', 'B', 16);
    $pdf->SetTextColor(255, 87, 34); // Orange for event
    $pdf->SetXY(120, 110);
    $pdf->Cell(60, 10, $participant['event_name'] . " of " . $participant['department_name'], 0, 0, 'L');
}

// Clean the output buffer
ob_end_clean();

// Output the PDF for download
if ($type === 'event') {
    if (isset($event_id) && ($_GET['event_id'] ?? '') !== 'all') {
        $filename = 'certificates_event_' . $event_id . '.pdf';
    } else {
        $filename = 'certificates_all_events.pdf';
    }
} elseif ($type === 'department') {
    if (isset($dept_id) && ($_GET['dept_id'] ?? '') !== 'all') {
        $filename = 'certificates_department_' . $dept_id . '.pdf';
    } else {
        $filename = 'certificates_all_departments.pdf';
    }
} else {
    $filename = 'certificates.pdf';
}
$pdf->Output($filename, 'D');
exit;
?>
