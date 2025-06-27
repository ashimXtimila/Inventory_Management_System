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

// Ensure the request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = trim($_POST["product_name"]);
    $category_id = $_POST["category_id"];
    $supplier_id = $_POST["supplier_id"];
    $quantity = (int)$_POST["quantity"];
    $price = (float)$_POST["price"];

    // Check if the product already exists (by name, category, and supplier)
    $stmt = $conn->prepare("SELECT product_id, quantity FROM products WHERE product_name = ? AND category_id = ? AND supplier_id = ?");
    $stmt->bind_param("sii", $product_name, $category_id, $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // If product exists, update quantity
        $new_quantity = $row['quantity'] + $quantity;
        $update_stmt = $conn->prepare("UPDATE products SET quantity = ?, price = ? WHERE product_id = ?");
        $update_stmt->bind_param("idi", $new_quantity, $price, $row['product_id']);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // If product doesn't exist, insert new row
        $insert_stmt = $conn->prepare("INSERT INTO products (product_name, category_id, supplier_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("siiid", $product_name, $category_id, $supplier_id, $quantity, $price);
        $insert_stmt->execute();
        $insert_stmt->close();
    }

    $stmt->close();
    $conn->close();

    header("Location: admin_dashboard.php");
    exit();
}
?>
