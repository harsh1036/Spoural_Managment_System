<?php
// Start output buffering at the very beginning of the file
ob_start(); 
require_once(__DIR__ . '/../../tcpdf/tcpdf.php');
include('../includes/config.php');
include('../includes/session_management.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for Admin login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch Admin details
$admin_id = $_SESSION['login'];
$query = $dbh->prepare("SELECT * FROM admins WHERE admin_id = :admin_id");
$query->bindParam(':admin_id', $admin_id, PDO::PARAM_STR);
$query->execute();
$admin = $query->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_destroy();
    header("Location: ../index.php?error=invalid_session");
    exit();
}

$admin_name = htmlspecialchars($admin['admin_name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     // Clear any buffered output
     ob_clean();
    $selected_event_id = isset($_POST['selected_event_pdf']) ? intval($_POST['selected_event_pdf']) : null;
    $download_all = isset($_POST['download_all_data']);
    $dept_id = isset($_POST['dept_id']) ? intval($_POST['dept_id']) : null;

    // Extend TCPDF to add custom footer
    class MYPDF extends TCPDF {
        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 10);
            
            // Date created (left side)
            date_default_timezone_set('Asia/Kolkata');
            $this->Cell(0, 10, 'Downloaded on : '.date('Y-m-d H:i:s'), 0, false, 'L', 0, '', 0, false, 'T', 'M');
            
            // Page number (right side)
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    // Create new PDF instance using our custom class
    $pdf = new MYPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Sports Event Participants Report');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true); // Enable footer for our custom footer

    // Set auto page breaks with a bottom margin to accommodate page numbers
    $pdf->SetAutoPageBreak(true, 25);

    // Add a page
    $pdf->AddPage();

    // Set logo path
    $logoPath = realpath('../assets/images/charusat.png');
    if ($logoPath && file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30);
    }

    // Add admin name
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Downloaded By : ' . $admin_name, 0, 1, 'R');

    $pdf->Ln(20);

    if ($download_all && $dept_id) {
        // Get department name
        $deptQuery = "SELECT dept_name FROM departments WHERE dept_id = :dept_id";
        $deptStmt = $dbh->prepare($deptQuery);
        $deptStmt->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
        $deptStmt->execute();
        $dept_name = $deptStmt->fetchColumn();

        // Get all Sports events for this department
        $eventQuery = "SELECT DISTINCT e.id, e.event_name 
                      FROM events e 
                      JOIN participants p ON e.id = p.event_id 
                      WHERE e.event_type = 'Sports' AND p.dept_id = :dept_id 
                      ORDER BY e.event_name";
        $eventResult = $dbh->prepare($eventQuery);
        $eventResult->bindParam(':dept_id', $dept_id, PDO::PARAM_INT);
        $eventResult->execute();
        $events = $eventResult->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            generateEventTable($pdf, $dbh, $event['id'], $event['event_name'], $dept_id, $dept_name);
        }
    } elseif ($selected_event_id) {
        $eventQuery = "SELECT event_name FROM events WHERE id = :event_id AND event_type = 'Sports'";
        $stmt = $dbh->prepare($eventQuery);
        $stmt->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            // Get all departments for this event
            $deptQuery = "SELECT DISTINCT d.dept_id, d.dept_name 
                         FROM departments d 
                         JOIN participants p ON d.dept_id = p.dept_id 
                         WHERE p.event_id = :event_id 
                         ORDER BY d.dept_name";
            $deptStmt = $dbh->prepare($deptQuery);
            $deptStmt->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
            $deptStmt->execute();
            $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($departments as $dept) {
                generateEventTable($pdf, $dbh, $selected_event_id, $event['event_name'], $dept['dept_id'], $dept['dept_name']);
            }
        } else {
            die("Invalid event selection.");
        }
    } else {
        die("Invalid request.");
    }

    // Output the PDF
    $pdf->Output('Sports_event_participants_report.pdf', 'D');
    exit();
}

function generateEventTable($pdf, $dbh, $eventId, $eventName, $departmentId, $deptName) {
    try {
        // Get participants for this department and event
        $participantQuery = "SELECT p.student_id, s.student_name 
                           FROM participants p 
                           LEFT JOIN student s ON p.student_id = s.student_id
                           WHERE p.event_id = :event_id
                           AND p.dept_id = :dept_id
                           ORDER BY p.student_id";
        $stmt = $dbh->prepare($participantQuery);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->bindParam(':dept_id', $departmentId, PDO::PARAM_INT);
        $stmt->execute();
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $participantCount = count($participants);

        // Start building the table
        $table = '<style></style>';

        $table .= '<table border="1" cellpadding="3" style="width:100%; border-collapse:collapse; font-family:helvetica; border:1px solid #000;">';
        
        // Header row
        $table .= '<tr>
                    <th style="width:25%;text-align:center;padding:6px;border:1px solid #000;font-weight:normal;vertical-align:middle;">DEPARTMENT</th>
                    <th style="width:35%;text-align:center;padding:6px;border:1px solid #000;font-weight:normal;vertical-align:middle;">EVENT NAME</th>
                    <th style="width:20%;text-align:center;padding:6px;border:1px solid #000;font-weight:normal;vertical-align:middle;">TOTAL<br>PARTICIPANTS</th>
                    <th style="width:20%;text-align:center;padding:6px;border:1px solid #000;font-weight:normal;vertical-align:middle;">ULSC NAME</th>
                   </tr>';
        
        // Get ULSC name for this department
        $ulscQuery = "SELECT ulsc_name FROM ulsc WHERE dept_id = :dept_id LIMIT 1";
        $ulscStmt = $dbh->prepare($ulscQuery);
        $ulscStmt->bindParam(':dept_id', $departmentId, PDO::PARAM_INT);
        $ulscStmt->execute();
        $ulsc_name = $ulscStmt->fetchColumn() ?: 'Not Assigned';

        // Data row
        $table .= '<tr>
                    <td style="text-align:center;padding:6px;border:1px solid #000;font-weight:normal;">' . htmlspecialchars($deptName) . '</td>
                    <td style="text-align:center;padding:6px;border:1px solid #000;font-weight:normal;">' . htmlspecialchars($eventName) . '</td>
                    <td style="text-align:center;padding:6px;border:1px solid #000;font-weight:normal;">' . $participantCount . '</td>
                    <td style="text-align:center;padding:6px;border:1px solid #000;font-weight:normal;">' . htmlspecialchars($ulsc_name) . '</td>
                   </tr>';
        
        // STUDENT IDS row
        $table .= '<tr>
                    <td colspan="4" style="text-align:center;padding:6px;border:1px solid #000;font-weight:normal;">STUDENT IDS</td>
                   </tr>';
        
        // Student IDs (each on a new line)
        if (!empty($participants)) {
            $ids = array_map(function($student) { return htmlspecialchars($student["student_id"]); }, $participants);
            $table .= '<tr>
                        <td colspan="4" style="text-align:center;padding:6px;border:1px solid #000;font-weight:normal;">' . implode('<br>', $ids) . '</td>
                       </tr>';
        } else {
            $table .= '<tr>
                        <td colspan="4" style="text-align:center;padding:6px;border:1px solid #000;font-weight:normal;">No registered participants for this event in this department.</td>
                      </tr>';
        }
        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        $pdf->Ln(8);

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        die("An error occurred while generating the report. Please try again later.");
    }
}
?> 