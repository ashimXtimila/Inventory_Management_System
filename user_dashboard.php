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

// Check if User is Logged In
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Fetch products
$products = $conn->query("SELECT * FROM products 
                          JOIN suppliers ON products.supplier_id = suppliers.supplier_id 
                          JOIN categories ON products.category_id = categories.category_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #A7BEAE; display: flex; margin: 0; }
        .sidebar { width: 200px; background: #404041; padding: 15px; height: 100vh; position: fixed; color: white; }
        .sidebar a { display: block; color: white; padding: 10px; text-decoration: none; }
        .sidebar a:hover { background: #0056b3; border-radius: 5px; }
        .container { margin-left: 220px; padding: 20px; width: calc(100% - 220px); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; text-align: left; padding: 10px; }
        th { background: #404041; color: white; }
        .out-of-stock { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>User Panel</h2>
    <a href="#">View Products</a>
    <a href="user_orders.php">Order Processing</a>
    <a href="return_policy.php">Return Product</a> <!-- New Return Product menu -->
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <h1>Welcome</h1>

    <h2>Available Products</h2>
    <form method="POST" action="process_order.php">

        <table>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Quantity Available</th>
                <th>Price</th>
                <th>Order Quantity</th>
            </tr>
            <?php while ($product = $products->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $product['product_id']; ?></td>
                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                    <td>
                        <?php if ($product['quantity'] == 0): ?>
                            <span class="out-of-stock">Out of Stock</span>
                        <?php else: ?>
                            <?php echo $product['quantity']; ?>
                        <?php endif; ?>
                    </td>
                    <td>Rs. <?php echo number_format($product['price'], 2); ?></td>
                    <td>
                        <?php if ($product['quantity'] > 0): ?>
                            <input type="number" name="products[<?php echo $product['product_id']; ?>]" min="0" max="<?php echo $product['quantity']; ?>" value="0">
                        <?php else: ?>
                            <input type="number" disabled value="0">
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <h2>Payment Method</h2>
        <select name="payment_method" required>
            <option value="Cash">Cash</option>
            <option value="Cheque">Cheque</option>
        </select>

        <br><br>
        <button type="submit" name="checkout">Checkout</button>
    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>

