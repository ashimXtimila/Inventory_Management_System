<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name']);
    
    // Validate input
    if (empty($category_name)) {
        echo "<script>alert('Category name cannot be empty.'); window.location.href='admin_dashboard.php';</script>";
        exit();
    }
    
    // Check if category name already exists (case-insensitive)
    $check_stmt = $conn->prepare("SELECT category_id FROM categories WHERE LOWER(category_name) = LOWER(?)");
    $check_stmt->bind_param("s", $category_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Category already exists
        echo "<script>alert('Category name already exists. Please choose a different name.'); window.location.href='admin_dashboard.php';</script>";
        $check_stmt->close();
        $conn->close();
        exit();
    }
    
    $check_stmt->close();
    
    // Insert new category
    $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
    $stmt->bind_param("s", $category_name);
    
    if ($stmt->execute()) {
        echo "<script>alert('Category added successfully.'); window.location.href='admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error adding category: " . $conn->error . "'); window.location.href='admin_dashboard.php';</script>";
    }
    
    $stmt->close();
}

$conn->close();
?>