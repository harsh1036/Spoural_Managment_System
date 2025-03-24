<?php
require_once(__DIR__ . '/../../tcpdf/tcpdf.php');
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_event_id = isset($_POST['selected_event_pdf']) ? intval($_POST['selected_event_pdf']) : null;
    $download_all = isset($_POST['download_all_data']);

    // Create new PDF instance
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ULSC');
    $pdf->SetTitle('Cultural Event Participants');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set logo path
    $logoPath = realpath('../assets/images/charusat.png');
    if ($logoPath && file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30);
    }

    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Charotar University of Science and Technology', 0, 1, 'C');
    $pdf->Ln(20);

    if ($download_all) {
        $eventQuery = "SELECT id, event_name FROM events WHERE event_type = 'Cultural'";
        $eventResult = $dbh->prepare($eventQuery);
        $eventResult->execute();
        $events = $eventResult->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            generateEventTable($pdf, $dbh, $event['id'], $event['event_name']);
        }
    } elseif ($selected_event_id) {
        $eventQuery = "SELECT event_name FROM events WHERE id = :event_id AND event_type = 'Cultural'";
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

    $pdf->Output('cultural_event_participants.pdf', 'D');
    exit();
}

function generateEventTable($pdf, $dbh, $eventId, $eventName) {
    $participantQuery = "SELECT DISTINCT p.id, p.student_id, d.dept_name, u.ulsc_name 
                         FROM participants p 
                         JOIN departments d ON p.dept_id = d.dept_id 
                         JOIN ulsc u ON d.dept_id = u.dept_id 
                         WHERE p.event_id = :event_id";
    $stmt = $dbh->prepare($participantQuery);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $table = '<table border="1" cellpadding="5">';
    $table .= '<tr><td colspan="3" style="text-align:center;"><b>Event Name: ' . htmlspecialchars($eventName) . '</b></td></tr>';

    if ($participants) {
        $groupedData = [];
        foreach ($participants as $row) {
            $groupedData[$row['dept_name']][] = $row;
        }

        foreach ($groupedData as $deptName => $students) {
            $ulscName = $students[0]['ulsc_name'];
            $table .= '<tr><td colspan="2"><b>Department:</b> ' . htmlspecialchars($deptName) . '</td>
                      <td><b>ULSC Name:</b> ' . htmlspecialchars($ulscName) . '</td></tr>';
            $table .= '<tr><th>ID</th><th>Student ID</th><th></th></tr>';
            
            $uniqueStudents = [];
            foreach ($students as $student) {
                if (!in_array($student['student_id'], $uniqueStudents)) {
                    $uniqueStudents[] = $student['student_id'];
                    $table .= '<tr><td>' . htmlspecialchars($student['id']) . '</td>
                              <td>' . htmlspecialchars($student['student_id']) . '</td>
                              <td></td></tr>';
                }
            }
        }
    } else {
        $table .= '<tr><td colspan="3" style="text-align:center;">No registered participants '  .htmlspecialchars($eventName) . '</td></tr>';
    }

    $table .= '</table>';
    $pdf->writeHTML($table, true, false, true, false, '');
    $pdf->Ln(10);
}
