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

// Check if the supplier_id is provided in the URL
if (isset($_GET['id'])) {
    $supplier_id = (int)$_GET['id'];

    // Check if the supplier is associated with any products
    $check_supplier = $conn->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
    $check_supplier->bind_param("i", $supplier_id);
    $check_supplier->execute();
    $check_supplier_result = $check_supplier->get_result();
    $count = $check_supplier_result->fetch_row()[0];

    if ($count > 0) {
        // Supplier is associated with products, so we can't delete it directly
        $_SESSION['error'] = "This supplier is associated with products and cannot be deleted.";
    } else {
        // Supplier is not associated with products, proceed with deletion
        $delete_stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
        $delete_stmt->bind_param("i", $supplier_id);
        $delete_stmt->execute();

        if ($delete_stmt->affected_rows > 0) {
            $_SESSION['success'] = "Supplier deleted successfully.";
        } else {
            $_SESSION['error'] = "Error: Supplier not found or unable to delete.";
        }

        $delete_stmt->close();
    }

    $check_supplier->close();
} else {
    $_SESSION['error'] = "No supplier ID provided.";
}

// Redirect back to the admin dashboard
header("Location: admin_dashboard.php");
exit();
?>
