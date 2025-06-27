<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = isset($_POST["user_id"]) ? trim($_POST["user_id"]) : '';
    $userName = isset($_POST["user_name"]) ? trim($_POST["user_name"]) : '';

    if (!empty($userId) && !empty($userName)) {
        // Check if user already exists
        $checkSql = "SELECT * FROM users WHERE user_id = ? OR username = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("ss", $userId, $userName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "User with this ID or name already exists";
        } else {
            // Insert new user
            $insertSql = "INSERT INTO users (user_id, username) VALUES (?, ?)";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param("ss", $userId, $userName);

            if ($stmt->execute()) {
                $message = "User added successfully";
            } else {
                $message = "Failed to add user";
            }
        }

        $stmt->close();
    } else {
        $message = "Please fill all fields";
    }

    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($message));
    exit();
}

// Display message if redirected
if (isset($_GET['msg'])) {
    $escapedMsg = htmlspecialchars($_GET['msg'], ENT_QUOTES);
    echo "<script>
        alert('$escapedMsg');
        window.location.href = 'admin_dashboard.php';
    </script>";
}

?>

