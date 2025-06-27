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

if (!isset($_GET['order_id'])) {
    die("Invalid request.");
}

$order_id = intval($_GET['order_id']);
$order_query = $conn->query("
    SELECT orders.order_id, orders.order_date, users.user_id
    FROM orders
    JOIN users ON orders.user_id = users.user_id
    WHERE orders.order_id = $order_id
");

if ($order_query->num_rows === 0) {
    die("Order not found.");
}

$order = $order_query->fetch_assoc();
$items_query = $conn->query("
    SELECT products.product_name, order_items.quantity, order_items.price 
    FROM order_items 
    JOIN products ON order_items.product_id = products.product_id 
    WHERE order_items.order_id = $order_id
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details</title>
    <style>
        body { font-family: Arial, sans-serif; background: #A7BEAE; display: flex; margin: 0; }
        .container { margin: auto; padding: 20px; width: 80%; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1, h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; text-align: left; padding: 10px; }
        th { background: #404041; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>Order Details</h1>
    <h2>Order #<?php echo $order['order_id']; ?> - Date: <?php echo $order['order_date']; ?></h2>

    <table>
        <tr>
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Price (Rs.)</th>
            <th>Total Price (Rs.)</th>
        </tr>

        <?php
        $order_total = 0;
        while ($item = $items_query->fetch_assoc()):
            $total_price = $item['quantity'] * $item['price'];
            $order_total += $total_price;
        ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td><?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo number_format($total_price, 2); ?></td>
            </tr>
        <?php endwhile; ?>

        <tr>
            <td colspan="3" style="text-align:right; font-weight:bold;">Order Total:</td>
            <td style="font-weight:bold;"><?php echo number_format($order_total, 2); ?></td>
        </tr>
    </table>

    <p style="text-align:center; margin-top: 20px;">
        <a href="user_orders.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Back to Orders</a>
    </p>
</div>

</body>
</html>

<?php $conn->close(); ?>
