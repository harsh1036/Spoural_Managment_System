<?php
require_once('../../tcpdf/tcpdf.php');
include('../includes/config.php');

// Create new PDF instance
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('ULSC Details');
date_default_timezone_set('Asia/Kolkata'); 
$pdf->SetHeaderData('', 0, 'ULSC Details', 'Generated on: ' . date('Y-m-d H:i:s'));
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->SetFont('dejavusans', '', 10);
$pdf->AddPage();

// Fetch ULSC data
$sql = "SELECT * FROM ulsc";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_ASSOC);

// Table header
$html = '<h2 style="text-align:center;">ULSC Details</h2>
<table border="1" cellpadding="5">
<tr>
    <th style="width:10%;"><b>Sr. No</b></th>
    <th style="width:30%;"><b>ULSC ID</b></th>
    <th style="width:50%;"><b>ULSC Name</b></th>
</tr>';

$sr = 1;
foreach ($results as $row) {
    $html .= '<tr>
                <td style="text-align:center;">' . $sr . '</td>
                <td>' . htmlspecialchars($row['ulsc_id']) . '</td>
                <td>' . htmlspecialchars($row['ulsc_name']) . '</td>
              </tr>';
    $sr++;
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// Output the PDF
$pdf->Output('ULSC_Details.pdf', 'D');
exit;
?>
