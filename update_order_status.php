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

// Ensure the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['order_id'];
    $order_status = $_POST['order_status'];

    // Update order status in the database
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $order_status, $order_id);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php");
    } else {
        echo "Error updating order: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>
