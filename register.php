<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_management_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Password validation function
function validatePassword($password) {
    return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $newUsername = trim($_POST['username']);
    $newPassword = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $role = 'user';


    // Check if username already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $newUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Username already exists!";
    } elseif (!validatePassword($newPassword)) {
        $error = "Password must be at least 8 characters long, include at least one uppercase letter, one lowercase letter, one number, and one special character.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match!";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $newUsername, $hashedPassword, $role);
        
        if ($stmt->execute()) {
            $success = "Account created successfully! <a href='login.php'>Login here</a>";
        } else {
            $error = "Error registering user!";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('login.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .register-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-radius: 8px;
            width: 350px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            width: 100%;
        }
        button:hover {
            background: #0056b3;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        .login-link {
            margin-top: 10px;
            display: block;
            text-decoration: none;
            color: #28a745;
        }
        .login-link:hover {
            text-decoration: underline;
        }
        .password-guidelines {
            font-size: 0.9em;
            color: #555;
            text-align: left;
            margin-bottom: 10px;
        }
        .password-match {
            font-size: 0.9em;
            color: red;
            text-align: left;
            display: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Create a New Account</h2>
        <form method="POST" onsubmit="return checkPasswordMatch()">
            <input type="text" name="username" placeholder="Username" required><br>
            
            <input type="password" id="password" name="password" placeholder="Password" required><br>
            <div class="password-guidelines">
                Password must be:
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Contain at least one uppercase letter</li>
                    <li>Contain at least one lowercase letter</li>
                    <li>Include at least one number</li>
                    <li>Have one special character (@$!%*?&)</li>
                </ul>
            </div>

            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required><br>
            <p id="password-match" class="password-match">Passwords do not match!</p>

            <select name="role" required>
            <option value="user">User</option>
            </select><br>

            <button type="submit" name="register">Register</button>
        </form>
        <?php 
        if (isset($error)) echo "<p class='error'>$error</p>";
        if (isset($success)) echo "<p class='success'>$success</p>"; 
        ?>
        <a href="login.php" class="login-link">Already have an account? Login</a>
    </div>

    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('password-match');

        function checkPasswordMatch() {
            if (password.value !== confirmPassword.value) {
                passwordMatch.style.display = 'block';
                return false; // prevent form submission
            } else {
                passwordMatch.style.display = 'none';
                return true; // allow form submission
            }
        }

        // Real-time check
        confirmPassword.addEventListener('input', () => {
            if (password.value !== confirmPassword.value) {
                passwordMatch.style.display = 'block';
            } else {
                passwordMatch.style.display = 'none';
            }
        });
    </script>
</body>
</html>
