<?php
session_start();
include('../includes/config.php');
require('../fpdf/fpdf.php'); // Make sure to have FPDF library in this path

// Check if user is logged in
if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check if event ID is provided
if (!isset($_POST['selected_event_pdf']) || empty($_POST['selected_event_pdf'])) {
    echo "No event selected";
    exit();
}

$event_id = intval($_POST['selected_event_pdf']);

// Get ULSC department
$ulsc_id = $_SESSION['ulsc_id'];
$query = $dbh->prepare("SELECT u.*, d.dept_name FROM ulsc u JOIN departments d ON u.dept_id = d.dept_id WHERE u.ulsc_id = :ulsc_id");
$query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
$query->execute();
$ulsc = $query->fetch(PDO::FETCH_ASSOC);

if (!$ulsc) {
    echo "ULSC member not found";
    exit();
}

// Get event details
$query = $dbh->prepare("SELECT * FROM events WHERE id = :event_id");
$query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
$query->execute();
$event = $query->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "Event not found";
    exit();
}

// Get participants
$query = $dbh->prepare("
    SELECT p.id, p.student_id, s.student_name, d.dept_name, p.is_captain 
    FROM participants p 
    JOIN departments d ON p.dept_id = d.dept_id 
    LEFT JOIN student s ON p.student_id = s.student_id
    WHERE p.event_id = :event_id
    ORDER BY p.is_captain DESC, s.student_name
");
$query->bindParam(':event_id', $event_id, PDO::PARAM_INT);
$query->execute();
$participants = $query->fetchAll(PDO::FETCH_ASSOC);

// Create PDF
class PDF extends FPDF
{
    // Page header
    function Header()
    {
        // Logo
        //$this->Image('logo.png', 10, 6, 30);
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30, 10, 'Spoural Event System', 0, 0, 'C');
        // Line break
        $this->Ln(20);
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Initialize PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Event Title
$pdf->Cell(0, 10, 'Cultural Event: ' . $event['event_name'], 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Department: ' . $ulsc['dept_name'], 0, 1);
$pdf->Cell(0, 10, 'Min Participants: ' . $event['min_participants'] . ' | Max Participants: ' . $event['max_participants'], 0, 1);
$pdf->Ln(5);

// Table Header
$pdf->SetFillColor(41, 66, 166);
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(15, 10, '#', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Student ID', 1, 0, 'C', true);
$pdf->Cell(60, 10, 'Student Name', 1, 0, 'C', true);
$pdf->Cell(50, 10, 'Department', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'Role', 1, 1, 'C', true);

// Table Content
$pdf->SetFillColor(224, 235, 255);
$pdf->SetTextColor(0);
$pdf->SetFont('Arial', '', 12);
$fill = false;
$count = 1;

foreach ($participants as $participant) {
    $pdf->Cell(15, 10, $count++, 1, 0, 'C', $fill);
    $pdf->Cell(40, 10, $participant['student_id'], 1, 0, 'L', $fill);
    $pdf->Cell(60, 10, $participant['student_name'] ?? 'N/A', 1, 0, 'L', $fill);
    $pdf->Cell(50, 10, $participant['dept_name'], 1, 0, 'L', $fill);
    $pdf->Cell(25, 10, $participant['is_captain'] ? 'Captain' : 'Participant', 1, 1, 'C', $fill);
    $fill = !$fill;
}

// Output the PDF
$filename = 'Cultural_Event_' . $event['event_name'] . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
?>
