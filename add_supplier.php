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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_name = trim($_POST['supplier_name']);
    $contact_person = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT supplier_id FROM suppliers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('This email is already used by another supplier.'); window.location.href='admin_dashboard.php';</script>";
        exit();
    }
    $stmt->close();

    // Insert new supplier
    $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $supplier_name, $contact_person, $phone, $email, $address);

    if ($stmt->execute()) {
        echo "<script>alert('Supplier added successfully!'); window.location.href='admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to add supplier.'); window.location.href='admin_dashboard.php';</script>";
    }
    $stmt->close();
}

$conn->close();
?>
