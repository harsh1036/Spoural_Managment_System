<?php
require_once(__DIR__ . '/../../tcpdf/tcpdf.php');
include('../includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_event_id = isset($_POST['selected_event_pdf']) ? intval($_POST['selected_event_pdf']) : null;
    $download_all = isset($_POST['download_all_data']) ? true : false;

    // Create new PDF instance
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ULSC');
    $pdf->SetTitle('Sports Event Participants');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set logo path
    $logoPath = realpath('../assets/images/charusat.png');

    // Print logo at the top-left
    if ($logoPath && file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30);
    }

    // Add a title with spacing
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Charotar University of Science and Technology', 0, 1, 'C');

    // Add spacing to avoid overlapping with the logo
    $pdf->Ln(20);



    // Define query based on selection
    if ($download_all) {
        // Fetch all cultural events
        $eventQuery = "SELECT id, event_name FROM events WHERE event_type = 'Sports'";
        $eventResult = $dbh->prepare($eventQuery);
        $eventResult->execute();
        $events = $eventResult->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            $eventId = $event['id'];
            $eventName = $event['event_name'];

            // Fetch participants for this event
            $participantQuery = "SELECT DISTINCT p.id, p.student_id, d.dept_name, u.ulsc_name 
                                 FROM participants p 
                                 JOIN departments d ON p.dept_id = d.dept_id 
                                 JOIN ulsc u ON d.dept_id = u.dept_id 
                                 WHERE p.event_id = $eventId";
            $participantResult = $dbh->prepare($participantQuery);
            $participantResult->execute();
            $participants = $participantResult->fetchAll(PDO::FETCH_ASSOC);

            // Create a new table for each event
            $table = '<table border="1" cellpadding="5">';

            // First row: Event Name (merged columns)
            $table .= '<tr>
                        <td colspan="3" style="text-align:center;"><b>Event Name: ' . htmlspecialchars($eventName) . '</b></td>
                      </tr>';

            if (count($participants) > 0) {
                // Group participants by department
                $groupedData = [];
                foreach ($participants as $row) {
                    $groupedData[$row['dept_name']][] = $row;
                }

                foreach ($groupedData as $deptName => $participants) {
                    // Fetch ULSC Name for the department (assuming it's the same for all participants in the department)
                    $ulscName = $participants[0]['ulsc_name'];

                    // Second row: Department Name and ULSC Name
                    $table .= '<tr>
                                <td colspan="2" style="text-align:left;"><b>Department Name:</b> ' . htmlspecialchars($deptName) . '</td>
                                <td style="text-align:right;"><b>ULSC Name:</b> ' . htmlspecialchars($ulscName) . '</td>
                              </tr>';

                    // Third row: ID and Student ID headers
                    $table .= '<tr>
                                <th>ID</th>
                                <th>Student ID</th>
                                <th></th>
                              </tr>';

                    // Add individual rows for ID and Student ID
                    $uniqueStudents = []; // To track unique student IDs
                    foreach ($participants as $participant) {
                        if (!in_array($participant['student_id'], $uniqueStudents)) {
                            $uniqueStudents[] = $participant['student_id']; // Add to unique list
                            $table .= '<tr>
                                        <td>' . htmlspecialchars($participant['id']) . '</td>
                                        <td>' . htmlspecialchars($participant['student_id']) . '</td>
                                        <td></td>
                                      </tr>';
                        }
                    }
                }
            } else {
                // If no participants, show "No registered participants" in table format
                $table .= '<tr>
                            <td colspan="3" style="text-align:center;"><b>No registered participants</b></td>
                          </tr>';
            }

            $table .= '</table>';

            // Add the table to the PDF
            $pdf->writeHTML($table, true, false, true, false, '');

            // Add a small space between tables for better readability
            $pdf->Ln(10);
        }
    } else if ($selected_event_id) {
        // Handle single event download (same as before)
        $query = "SELECT DISTINCT p.id, p.student_id, d.dept_name, e.event_name, u.ulsc_name 
                  FROM participants p 
                  JOIN departments d ON p.dept_id = d.dept_id 
                  JOIN events e ON p.event_id = e.id 
                  JOIN ulsc u ON d.dept_id = u.dept_id 
                  WHERE e.event_type = 'Sports' AND e.id = $selected_event_id";
        $result = $dbh->prepare($query);
        $result->execute();
        $participants = $result->fetchAll(PDO::FETCH_ASSOC);

        // Create table
        $table = '<table border="1" cellpadding="5">';

        if (count($participants) > 0) {
            // Group participants by event and department
            $groupedData = [];
            foreach ($participants as $row) {
                $groupedData[$row['event_name']][$row['dept_name']][] = $row;
            }

            foreach ($groupedData as $eventName => $departments) {
                foreach ($departments as $deptName => $participants) {
                    // Fetch ULSC Name for the department (assuming it's the same for all participants in the department)
                    $ulscName = $participants[0]['ulsc_name'];

                    // First row: Department Name (merged columns)
                    $table .= '<tr>
                                <td colspan="3" style="text-align:center;"><b>' . htmlspecialchars($deptName) . '</b></td>
                              </tr>';

                    // Second row: Event Name and ULSC Name in the same row
                    $table .= '<tr>
                                <td colspan="2" style="text-align:left;"><b>Event Name:</b> ' . htmlspecialchars($eventName) . '</td>
                                <td style="text-align:right;"><b>ULSC Name:</b> ' . htmlspecialchars($ulscName) . '</td>
                              </tr>';

                    // Third row: ID and Student ID headers
                    $table .= '<tr>
                                <th>ID</th>
                                <th>Student ID</th>
                                <th></th>
                              </tr>';

                    // Add individual rows for ID and Student ID
                    $uniqueStudents = []; // To track unique student IDs
                    foreach ($participants as $participant) {
                        if (!in_array($participant['student_id'], $uniqueStudents)) {
                            $uniqueStudents[] = $participant['student_id']; // Add to unique list
                            $table .= '<tr>
                                        <td>' . htmlspecialchars($participant['id']) . '</td>
                                        <td>' . htmlspecialchars($participant['student_id']) . '</td>
                                        <td></td>
                                      </tr>';
                        }
                    }
                }
            }
        } else {
            // If no participants, show "No registered participants" in table format
            $table .= '<tr>
                        <td colspan="3" style="text-align:center;"><b>No registered participants</b></td>
                      </tr>';
        }

        $table .= '</table>';
        $pdf->writeHTML($table, true, false, true, false, '');
    } else {
        die("Invalid request.");
    }

    // Output PDF
    $pdf->Output('sports_event_participants.pdf', 'D');
    exit();
}
