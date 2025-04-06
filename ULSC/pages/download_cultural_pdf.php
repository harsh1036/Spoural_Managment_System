<?php
require_once(__DIR__ . '/../../tcpdf/tcpdf.php');
include('../includes/config.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for ULSC login
if (!isset($_SESSION['ulsc_id'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch ULSC Member's Department ID
$ulsc_id = $_SESSION['ulsc_id'];

// Fetch ULSC department details
$sql = "SELECT u.dept_id, d.dept_name, u.ulsc_name 
        FROM ulsc u 
        JOIN departments d ON u.dept_id = d.dept_id 
        WHERE u.ulsc_id = :ulsc_id";
$query = $dbh->prepare($sql);
$query->bindParam(':ulsc_id', $ulsc_id, PDO::PARAM_STR);
$query->execute();
$ulsc_user = $query->fetch(PDO::FETCH_ASSOC);

if (!$ulsc_user) {
    session_destroy();
    header("Location: ../index.php?error=invalid_session");
    exit();
}

// Store ULSC's department ID safely
$dept_id = $ulsc_user['dept_id'];
$dept_name = htmlspecialchars($ulsc_user['dept_name']);
$ulsc_name = htmlspecialchars($ulsc_user['ulsc_name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_event_id = isset($_POST['selected_event_pdf']) ? intval($_POST['selected_event_pdf']) : null;
    $download_all = isset($_POST['download_all_data']);
    
    // We'll use the authenticated user's department ID regardless of what was posted
    // This ensures we only show data for the logged-in ULSC's department
    $department_id = $dept_id;

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
            $this->Cell(0, 10, 'Created: '.date('Y-m-d H:i:s'), 0, false, 'L', 0, '', 0, false, 'T', 'M');
            
            // Page number (right side)
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    // Create new PDF instance using our custom class
    $pdf = new MYPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ULSC');
    $pdf->SetTitle($dept_name . ' Cultural Event Participants');

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

    // Add department name and ULSC name
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Department: ' . $dept_name, 0, 1, 'R');
    $pdf->Cell(0, 10, 'ULSC: ' . $ulsc_name, 0, 1, 'R');

    $pdf->Ln(20);

    if ($download_all) {
        // Get all Cultural events
        $eventQuery = "SELECT id, event_name FROM events WHERE event_type = 'Cultural' ORDER BY event_name";
        $eventResult = $dbh->prepare($eventQuery);
        $eventResult->execute();
        $events = $eventResult->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            generateEventTable($pdf, $dbh, $event['id'], $event['event_name'], $department_id, $ulsc_name);
        }
    } elseif ($selected_event_id) {
        $eventQuery = "SELECT event_name FROM events WHERE id = :event_id AND event_type = 'Cultural'";
        $stmt = $dbh->prepare($eventQuery);
        $stmt->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            generateEventTable($pdf, $dbh, $selected_event_id, $event['event_name'], $department_id, $ulsc_name);
        } else {
            die("Invalid event selection.");
        }
    } else {
        die("Invalid request.");
    }

    // Output the PDF
    $pdf->Output($dept_name . '_Cultural_event_participants.pdf', 'D');
    exit();
}

function generateEventTable($pdf, $dbh, $eventId, $eventName, $departmentId, $ulscName) {
    try {
        // Get participants FILTERED BY DEPARTMENT
        $participantQuery = "SELECT p.student_id 
                           FROM participants p 
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
        $table = '<style>
                    .header { 
                        background-color: #4a6fdc; 
                        color: white; 
                        font-weight: bold;
                        font-size: 11pt;
                    }
                    .subheader {
                        background-color: #f2f6ff;
                        font-weight: bold;
                    }
                    .data-row {
                        background-color: #ffffff;
                    }
                    .alt-row {
                        background-color: #f5f9ff;
                    }
                    .no-data {
                        background-color: #fff8e6;
                        font-style: italic;
                    }
                </style>';

        $table .= '<table border="1" cellpadding="8" style="width:100%; border-collapse:collapse; font-family:helvetica; border:1px solid #ddd;">';
        
        // ULSC and Event Name header row with normal borders
        $table .= '<tr class="header">
                    <th style="width:40%;text-align:center;padding:12px;border:1px solid #ddd;">ULSC NAME</th>
                    <th style="width:40%;text-align:center;padding:12px;border:1px solid #ddd;">EVENT NAME</th>
                    <th style="width:20%;text-align:center;padding:12px;border:1px solid #ddd;">TOTAL PARTICIPANTS</th>
                   </tr>
                   <tr class="subheader">
                    <td style="text-align:center;padding:12px;border:1px solid #ddd;">' . htmlspecialchars($ulscName) . '</td>
                    <td style="text-align:center;padding:12px;border:1px solid #ddd;">' . htmlspecialchars($eventName) . '</td>
                    <td style="text-align:center;padding:12px;border:1px solid #ddd;">' . $participantCount . '</td>
                   </tr>';

        if (!empty($participants)) {
            // Student IDs header with normal borders
            $table .= '<tr class="header">
                        <td colspan="3" style="text-align:center;padding:12px;border:1px solid #ddd;">
                            STUDENT IDS
                        </td>
                       </tr>';
            
            // Student IDs list with normal borders
            $rowCount = 0;
            foreach ($participants as $student) {
                $bgColor = ($rowCount++ % 2 == 0) ? '#ffffff' : '#f5f9ff';
                $table .= '<tr>
                            <td colspan="3" style="text-align:center;padding:10px;border:1px solid #ddd;background-color:'.$bgColor.';">
                                ' . htmlspecialchars($student['student_id']) . '
                            </td>
                           </tr>';
            }
        } else {
            $table .= '<tr class="no-data">
                        <td colspan="3" style="text-align:center;padding:12px;border:1px solid #ddd;">
                            No registered participants for this event in your department.
                        </td>
                      </tr>';
        }

        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
        $pdf->Ln(15);

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        die("An error occurred while generating the report. Please try again later.");
    }
}