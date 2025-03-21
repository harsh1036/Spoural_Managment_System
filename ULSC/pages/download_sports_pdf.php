<?php
session_start();
require_once('../../tcpdf/tcpdf.php');
include('../includes/config.php');

// Create a new PDF instance
$pdf = new TCPDF();
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Participants Data Report (Sports Events)', 0, 1, 'C');
$pdf->Ln(5);

// Fetch all cultural event IDs
$query = $dbh->prepare("SELECT id FROM events WHERE event_type = 'Sports'");
$query->execute();
$cultural_event_ids = $query->fetchAll(PDO::FETCH_COLUMN); // Fetch IDs only

// Check if downloading all data
if (isset($_POST['download_all_data'])) {
    $query = $dbh->prepare("
        SELECT e.event_name, p.id, p.student_id, d.dept_name
        FROM participants p
        JOIN events e ON p.event_id = e.id
        JOIN departments d ON p.dept_id = d.dept_id
        WHERE p.event_id IN (" . implode(',', $cultural_event_ids) . ")
        ORDER BY e.event_name, p.id
    ");
    $query->execute();
    $participants = $query->fetchAll(PDO::FETCH_ASSOC);

    $pdf->SetFont('helvetica', '', 12);

    $current_event = "";
    foreach ($participants as $participant) {
        if ($participant['event_name'] != $current_event) {
            $current_event = $participant['event_name'];
            $pdf->Ln(5); // Add space between event sections
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, "Event: " . $current_event, 0, 1);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(30, 10, 'ID', 1);
            $pdf->Cell(50, 10, 'Participant ID', 1);
            $pdf->Cell(50, 10, 'Department Name', 1);
            $pdf->Ln();
        }
        $pdf->Cell(30, 10, $participant['id'], 1);
        $pdf->Cell(50, 10, $participant['student_id'], 1);
        $pdf->Cell(50, 10, $participant['dept_name'], 1);
        $pdf->Ln();
    }
}

// Download data for selected event only
elseif (isset($_POST['selected_event_pdf'])) {
    $event_id = intval($_POST['selected_event_pdf']);

    // Fetch the event name
    $query = $dbh->prepare("SELECT event_name FROM events WHERE id = :event_id");
    $query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $query->execute();
    $event = $query->fetch(PDO::FETCH_ASSOC);

    $selected_event_name = $event ? $event['event_name'] : 'Unknown Event';

    // Display the event name in the PDF header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, "Event: " . $selected_event_name, 0, 1, 'C');
    $pdf->Ln(5);

    // Fetch participant data
    $query = $dbh->prepare("
        SELECT p.id, p.student_id, d.dept_name
        FROM participants p
        JOIN departments d ON p.dept_id = d.dept_id
        WHERE p.event_id = :event_id
    ");
    $query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $query->execute();
    $participants = $query->fetchAll(PDO::FETCH_ASSOC);

    $pdf->SetFont('helvetica', '', 12);

    $pdf->Cell(30, 10, 'ID', 1);
    $pdf->Cell(50, 10, 'Participant ID', 1);
    $pdf->Cell(50, 10, 'Department Name', 1);
    $pdf->Ln();

    foreach ($participants as $participant) {
        $pdf->Cell(30, 10, $participant['id'], 1);
        $pdf->Cell(50, 10, $participant['student_id'], 1);
        $pdf->Cell(50, 10, $participant['dept_name'], 1);
        $pdf->Ln();
    }
}

// Output PDF
$pdf->Output('Participants_Report.pdf', 'D');
?>
