<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle GET request with 'id'
if (isset($_GET['id'])) {
    $category_id = intval($_GET['id']);

    // Check if category is used in products
    $check = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $check->bind_param("i", $category_id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        $_SESSION['message'] = "Cannot delete: Category is used in products.";
        $_SESSION['message_type'] = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Category deleted successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Delete failed: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }

    header("Location: admin_dashboard.php");
    exit();
} else {
    echo "No category ID provided.";
}

$conn->close();
?>
