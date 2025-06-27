<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Check if user ID is set
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        header("Location: admin_dashboard.php?error=You cannot delete your own account.");
        exit();
    }

    // Delete user from database
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?message=User deleted successfully.");
    } else {
        header("Location: admin_dashboard.php?error=Error deleting user.");
    }

    $stmt->close();
}

$conn->close();
?>
