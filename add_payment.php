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

// Handle Payment Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['order_id'];
    $payment_method = $_POST['payment_method'];
    $amount = $_POST['amount'];
    $payment_date = date("Y-m-d H:i:s");

    // Insert Payment into Database
    $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, amount, payment_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $order_id, $payment_method, $amount, $payment_date);

    if ($stmt->execute()) {
        echo "<script>alert('Payment added successfully!'); window.location.href='admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error adding payment.'); window.location.href='admin_dashboard.php';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
