<?php
// Establish database connection here if not already included in the main file
$servername = "localhost";
$username = "root"; // Change if needed
$password = ""; // Change if needed
$dbname = "inventory_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
} else {
    echo "Connected to Database!"; // Debugging message
}


// Fetch orders function

// Function to get all orders
function getOrders($conn) {
    $query = "SELECT o.order_id, u.username, o.quantity, o.order_status, o.created_at
              FROM orders o
              JOIN users u ON o.user_id = u.user_id
              ORDER BY o.created_at DESC";

    $result = $conn->query($query);

    if (!$result) {
        die("Query Failed: " . $conn->error); // Print SQL errors
    }

    if ($result->num_rows == 0) {
        die("No orders found in the database."); // Debugging message
    }

    return $result;
}




// Function to update order status (For order tracking)
function updateOrderStatus($conn, $orderId, $status)
{
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $orderId);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return false;
    }
}





?>
