<?php
require_once(__DIR__ . '/../../tcpdf/tcpdf.php');
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_event_id = isset($_POST['selected_event_pdf']) ? intval($_POST['selected_event_pdf']) : null;
    $download_all = isset($_POST['download_all_data']);

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
    $pdf->SetTitle('Sports Event Participants');

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

    $pdf->Ln(20);

    if ($download_all) {
        $eventQuery = "SELECT id, event_name FROM events WHERE event_type = 'Sports'";
        $eventResult = $dbh->prepare($eventQuery);
        $eventResult->execute();
        $events = $eventResult->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            generateEventTable($pdf, $dbh, $event['id'], $event['event_name']);
        }
    } elseif ($selected_event_id) {
        $eventQuery = "SELECT event_name FROM events WHERE id = :event_id AND event_type = 'Sports'";
        $stmt = $dbh->prepare($eventQuery);
        $stmt->bindParam(':event_id', $selected_event_id, PDO::PARAM_INT);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            generateEventTable($pdf, $dbh, $selected_event_id, $event['event_name']);
        } else {
            die("Invalid event selection.");
        }
    } else {
        die("Invalid request.");
    }

    // Output the PDF
    $pdf->Output('Sports_event_participants.pdf', 'D');
    exit();
}function generateEventTable($pdf, $dbh, $eventId, $eventName) {
    try {
        // Get ULSC name for this event
        $ulscQuery = "SELECT u.ULSC_NAME 
                     FROM participants p
                     JOIN departments d ON p.dept_id = d.dept_id
                     JOIN ulsc u ON d.dept_id = u.dept_id
                     WHERE p.event_id = :event_id
                     LIMIT 1";
        $ulscStmt = $dbh->prepare($ulscQuery);
        $ulscStmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $ulscStmt->execute();
        $ulsc = $ulscStmt->fetch(PDO::FETCH_ASSOC);
        $ulscName = $ulsc ? $ulsc['ULSC_NAME'] : 'N/A';

        // Get participants
        $participantQuery = "SELECT p.student_id 
                           FROM participants p 
                           WHERE p.event_id = :event_id
                           ORDER BY p.student_id";
        $stmt = $dbh->prepare($participantQuery);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
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
                            No registered participants
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