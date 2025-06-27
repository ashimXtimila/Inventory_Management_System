<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if User is Logged In
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$orders = $conn->query("SELECT order_id, order_date, user_id FROM orders WHERE user_id = $user_id ORDER BY order_date DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Processing</title>
    <style>
        body { font-family: Arial, sans-serif; background: #A7BEAE; display: flex; margin: 0; }
        .sidebar { width: 200px; background: #404041; padding: 15px; height: 100vh; position: fixed; color: white; }
        .sidebar a { display: block; color: white; padding: 10px; text-decoration: none; }
        .sidebar a:hover { background: #0056b3; border-radius: 5px; }
        .container { margin-left: 220px; padding: 20px; width: calc(100% - 220px); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; text-align: left; padding: 10px; }
        th { background: #404041; color: white; }
        .details-btn { background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .details-btn:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>User Panel</h2>
    <a href="user_dashboard.php">View Products</a>
    <a href="#">Order Processing</a>
    <a href="return_policy.php">Return Product</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <h1>Your Orders</h1>

    <table>
        <tr>
            <th>Order ID</th>
            <th>Order Date</th>
            <th>Customer ID</th>
            <th>Details</th>
        </tr>
        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo $order['order_date']; ?></td>
                    <td><?php echo $order['user_id']; ?></td>
                    <td><a href="order_details.php?order_id=<?php echo $order['order_id']; ?>" class="details-btn">View</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No orders found.</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>

<?php $conn->close(); ?>
