<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_management_system";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers in the first row
$sheet->setCellValue('A1', 'Product');
$sheet->setCellValue('B1', 'Category');
$sheet->setCellValue('C1', 'Supplier');
$sheet->setCellValue('D1', 'Quantity');

// Make headers bold
$sheet->getStyle('A1:D1')->getFont()->setBold(true);

// Set column widths
$sheet->getColumnDimension('A')->setWidth(25);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(10);

// Query to fetch data
$query = "
    SELECT p.product_name, c.category_name, s.supplier_name, p.quantity
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Populate spreadsheet rows
$rowIndex = 2;
while ($row = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue("A{$rowIndex}", $row['product_name']);
    $sheet->setCellValue("B{$rowIndex}", $row['category_name']);
    $sheet->setCellValue("C{$rowIndex}", $row['supplier_name']);
    $sheet->setCellValue("D{$rowIndex}", $row['quantity']);
    $rowIndex++;
}

// Output headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="report.xlsx"');
header('Cache-Control: max-age=0');

// Write file to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;
