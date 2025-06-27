<?php
require('fpdf.php'); // download from http://www.fpdf.org/
include('db_connection.php'); // reuse your DB config

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Column headers
$pdf->Cell(40, 10, 'Product');
$pdf->Cell(30, 10, 'Category');
$pdf->Cell(30, 10, 'Supplier');
$pdf->Cell(20, 10, 'Qty');
$pdf->Ln();

// Fetch data
$result = $conn->query("
    SELECT p.product_name, c.category_name, s.supplier_name, p.quantity
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
");

while ($row = $result->fetch_assoc()) {
    $pdf->Cell(40, 10, $row['product_name']);
    $pdf->Cell(30, 10, $row['category_name']);
    $pdf->Cell(30, 10, $row['supplier_name']);
    $pdf->Cell(20, 10, $row['quantity']);
    $pdf->Ln();
}

$pdf->Output();
?>
