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
     // Clear any buffered output
     ob_clean();
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
            $this->Cell(0, 10, 'Downloaded on : '.date('Y-m-d H:i:s'), 0, false, 'L', 0, '', 0, false, 'T', 'M');
            
            // Page number (right side)
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    // Create new PDF instance using our custom class
    $pdf = new MYPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ULSC');
    $pdf->SetTitle($dept_name . ' Sports Event Participants');

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
    $pdf->Cell(0, 10, 'Downloaded By : ' . $ulsc_name, 0, 1, 'R');

    $pdf->Ln(20);

    // Get all ULSC members from the same department for signature section
    $ulscQuery = "SELECT ulsc_id, ulsc_name 
                  FROM ulsc 
                  WHERE dept_id = :dept_id 
                  ORDER BY ulsc_name";
    $ulscStmt = $dbh->prepare($ulscQuery);
    $ulscStmt->bindParam(':dept_id', $department_id, PDO::PARAM_INT);
    $ulscStmt->execute();
    $ulscMembers = $ulscStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($download_all) {
        // Get all Sports events
        $eventQuery = "SELECT id, event_name FROM events WHERE event_type = 'Sports' ORDER BY event_name";
        $eventResult = $dbh->prepare($eventQuery);
        $eventResult->execute();
        $events = $eventResult->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            generateEventTable($pdf, $dbh, $event['id'], $event['event_name'], $department_id, $ulsc_name);
        }
    } elseif ($selected_event_id) {
        $eventQuery = "SELECT event_name FROM events WHERE id = :event_id AND event_type = 'Sports'";
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

    // Add signatures section at the end
    addSignatureSection($pdf, $ulscMembers);

    // Output the PDF
    $pdf->Output($dept_name . '_Sports_event_participants.pdf', 'D');
    exit();
}

function generateEventTable($pdf, $dbh, $eventId, $eventName, $departmentId, $ulscName) {
    try {
        // Get participants FILTERED BY DEPARTMENT - including student names
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
                   </tr>';
                   
        // Show only logged-in ULSC name instead of all ULSC names
        $table .= '<tr class="subheader">
                    <td style="text-align:center;padding:12px;border:1px solid #ddd;">' . htmlspecialchars($ulscName) . '</td>
                    <td style="text-align:center;padding:12px;border:1px solid #ddd;">' . htmlspecialchars($eventName) . '</td>
                    <td style="text-align:center;padding:12px;border:1px solid #ddd;">' . $participantCount . '</td>
                   </tr>';

        if (!empty($participants)) {
            // Student IDs and Names header with normal borders
            $table .= '<tr class="header">
                        <td style="text-align:center;padding:12px;border:1px solid #ddd;">
                            STUDENT ID
                        </td>
                        <td colspan="2" style="text-align:center;padding:12px;border:1px solid #ddd;">
                            STUDENT NAME
                        </td>
                       </tr>';
            
            // Student IDs and Names list with normal borders
            $rowCount = 0;
            foreach ($participants as $student) {
                $bgColor = ($rowCount++ % 2 == 0) ? '#ffffff' : '#f5f9ff';
                $studentName = isset($student['student_name']) ? htmlspecialchars($student['student_name']) : 'Name not found';
                $table .= '<tr>
                            <td style="text-align:center;padding:10px;border:1px solid #ddd;background-color:'.$bgColor.';">
                                ' . htmlspecialchars($student['student_id']) . '
                            </td>
                            <td colspan="2" style="text-align:center;padding:10px;border:1px solid #ddd;background-color:'.$bgColor.';">
                                ' . $studentName . '
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

function addSignatureSection($pdf, $ulscMembers) {
    // Add a new page for signatures if needed
    if ($pdf->getY() > $pdf->getPageHeight() - 100) {
        $pdf->AddPage();
    } else {
        $pdf->Ln(20);
    }
    
    // Add signature section title
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'ULSC Members Signatures', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Calculate columns and rows based on number of ULSC members
    $columns = 2; // Use 2 columns for signatures
    $rowsPerPage = 5; // Maximum number of rows per page
    $columnWidth = $pdf->getPageWidth() / $columns - 20; // Adjust width for margins
    
    $pdf->SetFont('helvetica', '', 10);
    
    $count = 0;
    $startX = 10;
    $startY = $pdf->getY();
    $lineGap = 25; // Space between signature lines
    
    foreach ($ulscMembers as $member) {
        // Calculate position
        $col = $count % $columns;
        $row = floor($count / $columns);
        
        // Check if we need a new page
        if ($row >= $rowsPerPage) {
            $pdf->AddPage();
            $count = 0;
            $col = 0;
            $row = 0;
            $startY = $pdf->getY();
        }
        
        $x = $startX + ($col * $columnWidth);
        $y = $startY + ($row * $lineGap);
        
        // Position cursor for signature
        $pdf->SetXY($x, $y);
        
        // Draw signature line
        $pdf->Line($x, $y + 10, $x + ($columnWidth - 20), $y + 10);
        
        // Add ULSC name
        $pdf->SetXY($x, $y);
        $pdf->Cell($columnWidth - 20, 10, htmlspecialchars($member['ulsc_name']) . ':', 0, 1, 'L');
        
        $count++;
    }
    
    // Return to main flow
    $pdf->SetY($startY + ($rowsPerPage * $lineGap) + 10);
}