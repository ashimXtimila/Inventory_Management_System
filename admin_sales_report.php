<?php
session_start();

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if Admin is Logged In
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch Sales Report Data
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$query = "
    SELECT orders.order_id, products.product_name, orders.quantity, 
           (orders.quantity * products.price) AS total_price, 
           payments.payment_method, orders.created_at 
    FROM orders
    JOIN products ON orders.product_id = products.product_id
    JOIN payments ON orders.order_id = payments.order_id
    WHERE orders.status = 'Completed'
";

if ($startDate && $endDate) {
    $query .= " AND orders.created_at BETWEEN '$startDate' AND '$endDate'";
}

$query .= " ORDER BY orders.created_at DESC";

$salesReport = $conn->query($query);

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Product Name', 'Quantity Sold', 'Total Price', 'Payment Method', 'Order Date']);

    while ($row = $salesReport->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// Export to PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require('fpdf/fpdf.php');

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Sales Report', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(30, 10, 'Order ID', 1);
    $pdf->Cell(50, 10, 'Product Name', 1);
    $pdf->Cell(30, 10, 'Quantity', 1);
    $pdf->Cell(30, 10, 'Total Price', 1);
    $pdf->Cell(50, 10, 'Payment Method', 1);
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 12);
    while ($row = $salesReport->fetch_assoc()) {
        $pdf->Cell(30, 10, $row['order_id'], 1);
        $pdf->Cell(50, 10, $row['product_name'], 1);
        $pdf->Cell(30, 10, $row['quantity'], 1);
        $pdf->Cell(30, 10, 'Rs. ' . $row['total_price'], 1);
        $pdf->Cell(50, 10, $row['payment_method'], 1);
        $pdf->Ln();
    }

    $pdf->Output('D', 'sales_report.pdf');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        body { font-family: Arial, sans-serif; background: #F4F4F4; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #404041; color: white; }
        input { padding: 5px; margin-right: 10px; }
        button, a { padding: 5px 10px; background: #0056b3; color: white; border: none; border-radius: 5px; text-decoration: none; }
        a { margin-left: 10px; }
    </style>
</head>
<body>
    <h1>Sales Report</h1>

    <form method="GET">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">

        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">

        <button type="submit">Filter</button>
        <a href="?export=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">Export to CSV</a>
        <a href="?export=pdf&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">Export to PDF</a>
    </form>

    <table>
        <tr>
            <th>Order ID</th>
            <th>Product Name</th>
            <th>Quantity Sold</th>
            <th>Total Price</th>
            <th>Payment Method</th>
            <th>Order Date</th>
        </tr>
        <?php while ($sale = $salesReport->fetch_assoc()): ?>
            <tr>
                <td><?php echo $sale['order_id']; ?></td>
                <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                <td><?php echo $sale['quantity']; ?></td>
                <td>Rs. <?php echo $sale['total_price']; ?></td>
                <td><?php echo htmlspecialchars($sale['payment_method']); ?></td>
                <td><?php echo $sale['created_at']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php $conn->close(); ?>
