<?php
require_once('libs/tcpdf/tcpdf.php'); // Correct path to TCPDF

session_start();

$host = 'localhost';
$username = 'root';
$password= '';
$db_name = 'smartlib';
$conn = mysqli_connect($host, $username, $password, $db_name);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get user ID from session
$query = "SELECT user_id FROM users WHERE name = '" . $_SESSION['name'] . "'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$user_id = $row['user_id'];

// Get user borrow history
$query = "SELECT b.title, c.name AS category, b.description, r.borrow_date, r.return_date, r.status 
          FROM books AS b 
          JOIN borrow_records AS r ON b.book_id = r.book_id 
          JOIN categories AS c ON c.category_id = b.category_id 
          WHERE r.user_id = '$user_id'";
$result = mysqli_query($conn, $query);

// Create PDF instance
$pdf = new TCPDF();
$pdf->SetCreator('SmartLib');
$pdf->SetAuthor('SmartLib');
$pdf->SetTitle('User Borrow History');
$pdf->SetHeaderData('', 0, 'SmartLib - Borrow History', '', array(0,64,255), array(0,64,128));
$pdf->setHeaderFont(Array('helvetica', '', 12));
$pdf->setFooterFont(Array('helvetica', '', 10));
$pdf->SetMargins(15, 27, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);

// Generate HTML content
$html = "<h2>User Borrow History</h2>";
while ($row = mysqli_fetch_assoc($result)) {
    $html .= "
        <hr>
        <strong>Title:</strong> {$row['title']}<br>
        <strong>Category:</strong> {$row['category']}<br>
        <strong>Description:</strong> {$row['description']}<br>
        <strong>Borrowed On:</strong> {$row['borrow_date']}<br>
        <strong>Returned On:</strong> {$row['return_date']}<br>
        <strong>Status:</strong> {$row['status']}<br><br>
    ";
}

// Write to PDF
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('borrow_history.pdf', 'I'); // 'I' = open in browser, 'D' = force download
?>
