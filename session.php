<?php 
include 'connection.php';

// Regenerate session ID to prevent session fixation attacks
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}


// Validate session to prevent session hijacking
if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: ./login.php");
    exit;
}

// Set session expiration time (10 minutes)
$inactivity_limit = 600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset();
    session_destroy();
    header("Location: ./login.php?expired=1");
    exit;
}

$_SESSION['last_activity'] = time();

// Store the user's IP address for additional security
if (!isset($_SESSION['user_ip'])) {
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
} elseif ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    header("Location: ./login.php");
    exit;
}

// Function to get user role (default role since only admin user exists)
function getUserRole() {
    return 'admin'; // Default role since only admin user exists
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username']; // Updated to username
    $password = $_POST['password'];

    // Prepare SQL query to fetch user by username
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $user_name, $hashed_password);
    $stmt->fetch();

    if (password_verify($password, $hashed_password)) {
        // Set session variables
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $user_name; // Updated to username
        $_SESSION['role'] = 'admin'; // Hardcoded role for admin
        $_SESSION['last_activity'] = time(); // Record the current time as last activity
        
        header("Location: index.php");
        exit();
    } else {
        echo "Invalid username or password.";
    }

    $stmt->close();
}
?>
