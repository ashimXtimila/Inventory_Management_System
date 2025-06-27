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

// Fetch Orders with Product Names
$orders = $conn->query("SELECT orders.order_id, products.product_name, orders.quantity, orders.status, orders.created_at FROM orders JOIN products ON orders.product_id = products.product_id ORDER BY orders.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Processing</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #A7BEAE;
            margin: 0;
            padding: 20px;
        }
        h2 {
            color: #404041;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #404041;
            color: white;
        }
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-complete {
            background: #4CAF50;
            color: white;
        }
        .btn-cancel {
            background: #f44336;
            color: white;
        }
        a {
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
    <h2>Manage Orders</h2>
    <a href="admin_dashboard.php">Back to Dashboard</a>
    <table>
        <tr>
            <th>Order ID</th>
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Order Date</th>
            <th>Action</th>
        </tr>
        <?php while ($order = $orders->fetch_assoc()): ?>
            <tr>
                <td><?php echo $order['order_id']; ?></td>
                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                <td><?php echo $order['quantity']; ?></td>
                <td><?php echo htmlspecialchars($order['status']); ?></td>
                <td><?php echo $order['created_at']; ?></td>
                <td>
                    <a href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=Completed" class="btn btn-complete">Complete</a>
                    <a href="update_order_status.php?id=<?php echo $order['order_id']; ?>&status=Cancelled" class="btn btn-cancel">Cancel</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php $conn->close(); ?>
