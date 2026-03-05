<?php
define('ACCESS_ALLOWED', true);
require_once 'connection.php';
require_once 'includes/auth_functions.php';
require_once 'includes/google_auth_config.php';

if (!isset($_GET['code'])) {
    header('Location: login.php?error=google_auth_failed');
    exit();
}

$code = $_GET['code'];

// 1. Exchange code for access token
$token_url = 'https://oauth2.googleapis.com/token';
$token_params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
    'code' => $code
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    header('Location: login.php?error=google_token_failed');
    exit();
}

$access_token = $token_data['access_token'];

// 2. Fetch user info
$userinfo_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userinfo_url . '?access_token=' . $access_token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_info_response = curl_exec($ch);
curl_close($ch);

$user_info = json_decode($user_info_response, true);

if (!isset($user_info['sub'])) {
    header('Location: login.php?error=google_userinfo_failed');
    exit();
}

$google_id = $user_info['sub'];
$email = $user_info['email'];
$name = $user_info['name'];
$picture = $user_info['picture'] ?? '';

// 3. Check if user exists
$stmt = $connection->prepare("SELECT user_id, username, role, display_name, organization_id FROM users WHERE google_id = ? OR email = ?");
$stmt->bind_param("ss", $google_id, $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    // User exists, update google_id if it's missing (linked by email)
    if (empty($user['google_id'])) {
        $stmt = $connection->prepare("UPDATE users SET google_id = ? WHERE user_id = ?");
        $stmt->bind_param("si", $google_id, $user['user_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['display_name'] = $user['display_name'];
    $_SESSION['organization_id'] = $user['organization_id'];
    
    logActivity('Google Login', 'User', $user['user_id']);
    header('Location: index.php');
    exit();
} else {
    // 4. Create new user (Organization to be created later)
    $connection->begin_transaction();
    
    try {
        // Use a clean approach for username based on email
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0])) . rand(100, 999);
        
        $role = 'admin'; // Will be organization admin once org is created
        $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // Random password for social login users
        
        $stmt = $connection->prepare("INSERT INTO users (google_id, username, email, password_hash, display_name, role, organization_id) VALUES (?, ?, ?, ?, ?, ?, NULL)");
        $stmt->bind_param("ssssss", $google_id, $username, $email, $password_hash, $name, $role);
        $stmt->execute();
        $user_id = $connection->insert_id;
        $stmt->close();
        
        $connection->commit();
        
        // Set session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['display_name'] = $name;
        $_SESSION['organization_id'] = null;
        
        logActivity('Google Registration', 'User', $user_id);
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $connection->rollback();
        error_log("Google Auth Registration Error: " . $e->getMessage());
        header('Location: login.php?error=registration_failed');
        exit();
    }
}
?>