<?php 
include 'connection.php';

$conn = new mysqli($servername, $username, $password, $dbname);
 // Ensure this file connects to the database
session_start(); // Start the session

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']); // Get username from form
    $password = trim($_POST['password']); // Get password from form

    // Prepare SQL query to fetch user by username
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, role, display_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($user_id, $user_name, $hashed_password, $role, $display_name);
    $stmt->fetch();

    // Verify password and set session variables
    if (password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $user_name;
        $_SESSION['role'] = $role;
        $_SESSION['display_name'] = $display_name;
        
        header("Location: index.php"); // Redirect to home page
        exit();
    } else {
        $error = "Invalid username or password."; // Set error message
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .login-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .login-container input {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #0d1b2a;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .login-container button:hover {
            background-color: #0a1a2a;
        }
        .login-container .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php
        // Display error message if there is one
        if (isset($error)) {
            echo "<p class='error'>$error</p>";
        }
        ?>
        <form method="POST" action="login.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
