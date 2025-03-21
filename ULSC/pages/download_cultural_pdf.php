<?php
require_once('../../tcpdf/tcpdf.php'); // Adjust the path as per your structure
include('../includes/config.php');

$selected_event_id = isset($_GET['selected_event']) ? $_GET['selected_event'] : '';

// Create a new PDF document
$pdf = new TCPDF();

// Set document properties
$pdf->SetCreator('Spoural Management System');
$pdf->SetTitle('Event Participants Report');
$pdf->AddPage();

// Title
$pdf->SetFont('Helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Event Participants Report', 0, 1, 'C');
$pdf->Ln(5);

if ($selected_event_id) {
    // Fetch participants for the selected event
    $query = $dbh->prepare("
        SELECT p.id, p.student_id, d.dept_name 
        FROM participants p 
        JOIN departments d ON p.dept_id = d.dept_id 
        WHERE p.event_id = :event_id
    ");
    $query->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
    $query->execute();
    $participants = $query->fetchAll(PDO::FETCH_ASSOC);

    // Fetch event name
    $query = $dbh->prepare("SELECT event_name FROM events WHERE id = :event_id");
    $query->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
    $query->execute();
    $event = $query->fetch(PDO::FETCH_ASSOC);
    $selected_event_name = $event['event_name'];

    // Display Event Title
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, "Event: " . $selected_event_name, 0, 1, 'L');
} else {
    // Fetch all participants if no event is selected
    $query = $dbh->prepare("
        SELECT p.id, p.student_id, d.dept_name, e.event_name
        FROM participants p
        JOIN departments d ON p.dept_id = d.dept_id
        JOIN events e ON p.event_id = e.id
    ");
    $query->execute();
    $participants = $query->fetchAll(PDO::FETCH_ASSOC);

    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'All Events Report', 0, 1, 'L');
}

$pdf->Ln(5);

// Table Header
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(20, 10, 'ID', 1);
$pdf->Cell(50, 10, 'Participant ID', 1);
$pdf->Cell(60, 10, 'Department Name', 1);
if (!$selected_event_id) {
    $pdf->Cell(60, 10, 'Event Name', 1);
}
$pdf->Ln();

// Table Data
$pdf->SetFont('Helvetica', '', 10);
foreach ($participants as $participant) {
    $pdf->Cell(20, 10, $participant['id'], 1);
    $pdf->Cell(50, 10, htmlspecialchars($participant['student_id']), 1);
    $pdf->Cell(60, 10, htmlspecialchars($participant['dept_name']), 1);
    if (!$selected_event_id) {
        $pdf->Cell(60, 10, htmlspecialchars($participant['event_name']), 1);
    }
    $pdf->Ln();
}

// Output PDF
$pdf->Output('participants_report.pdf', 'D');
?>