<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Product</title>
    <style>
        body { font-family: Arial, sans-serif; background: #A7BEAE; display: flex; margin: 0; }
        .sidebar { width: 200px; background: #404041; padding: 15px; height: 100vh; position: fixed; color: white; }
        .sidebar a { display: block; color: white; padding: 10px; text-decoration: none; }
        .sidebar a:hover { background: #0056b3; border-radius: 5px; }
        .container { margin-left: 220px; padding: 20px; width: calc(100% - 220px); }
        .info-box { background: white; padding: 20px; border-radius: 5px; box-shadow: 2px 2px 10px gray; }
        .contact { font-weight: bold; color: #0056b3; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>User Panel</h2>
    <a href="user_dashboard.php">View Products</a>
    <a href="user_orders.php">Order Processing</a>
    <a href="#">Return Product</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <h1>Return Product</h1>
    <div class="info-box">
        <p>If you wish to return a product, please contact the administrator.</p>
        <p>You can reach out via:</p>
        <ul>
            <li>Email: <span class="contact">ashimtimila4@gmail.com</span></li>
            <li>Phone: <span class="contact">+977-9840252535</span></li>
        </ul>
        <p>Ensure you have your order ID and purchase details ready.</p>
    </div>
</div>

</body>
</html>
