<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    if (!isset($_POST['products']) || !isset($_POST['payment_method'])) {
        die("Missing product data or payment method.");
    }

    $user_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'];
    $total_amount = 0;

    // Create Order
    $order_query = $conn->prepare("INSERT INTO orders (user_id, order_date) VALUES (?, NOW())");
    if (!$order_query) {
        die("Order Query Preparation Failed: " . $conn->error);
    }
    $order_query->bind_param("i", $user_id);
    if (!$order_query->execute()) {
        die("Order Query Execution Failed: " . $order_query->error);
    }
    $order_id = $order_query->insert_id;
    $order_query->close();

    foreach ($_POST['products'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = intval($quantity);

        if ($quantity > 0) {
            $price_query = $conn->prepare("SELECT price, quantity FROM products WHERE product_id = ?");
            if (!$price_query) {
                die("Price Query Failed: " . $conn->error);
            }
            $price_query->bind_param("i", $product_id);
            $price_query->execute();
            $price_query->bind_result($price, $available_quantity);
            $price_query->fetch();
            $price_query->close();

            if (isset($available_quantity) && $available_quantity >= $quantity) {
                $total_amount += $quantity * $price;

                // Insert into order_items
                $order_item_query = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                if (!$order_item_query) {
                    die("Order Item Query Failed: " . $conn->error);
                }
                $order_item_query->bind_param("iiid", $order_id, $product_id, $quantity, $price);
                if (!$order_item_query->execute()) {
                    die("Order Item Query Execution Failed: " . $order_item_query->error);
                }
                $order_item_query->close();

                // Update stock
                $update_stock_query = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
                if (!$update_stock_query) {
                    die("Stock Update Query Failed: " . $conn->error);
                }
                $update_stock_query->bind_param("ii", $quantity, $product_id);
                $update_stock_query->execute();
                $update_stock_query->close();
            } else {
                echo "<script>alert('Insufficient stock for Product ID: " . $product_id . "');</script>";
            }
        }
    }

    // Insert Payment
    if ($total_amount > 0) {
        $payment_query = $conn->prepare("INSERT INTO payments (order_id, payment_method, amount, payment_date) VALUES (?, ?, ?, NOW())");
        if (!$payment_query) {
            die("Payment Query Failed: " . $conn->error);
        }
        $payment_query->bind_param("isd", $order_id, $payment_method, $total_amount);
        if (!$payment_query->execute()) {
            die("Payment Query Execution Failed: " . $payment_query->error);
        }
        $payment_query->close();
        
        echo "<script>alert('Order placed successfully! Total: Rs. " . number_format($total_amount, 2) . "'); window.location.href = 'user_orders.php';</script>";
    } else {
        echo "<script>alert('No valid items selected for order!');</script>";
    }

    exit();
}

$conn->close();
?>
