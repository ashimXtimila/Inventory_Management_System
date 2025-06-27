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

// Ensure the request contains an ID and is from an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    // Delete the product from the database
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        // Redirect back to the admin dashboard with success message
        header("Location: admin_dashboard.php?message=Product deleted successfully");
    } else {
        // Redirect with error message
        header("Location: admin_dashboard.php?error=Error deleting product");
    }

    $stmt->close();
}

$conn->close();
?>
