<?php
/**
 * Authentication and Security Functions
 * For Property Portfolio Management System
 */

/**
 * Start secure session
 */
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 3600, // 1 hour
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => isset($_SERVER['HTTPS']), // Only send cookie over HTTPS
            'httponly' => true, // Prevent JavaScript access to cookie
            'samesite' => 'Lax' // CSRF protection
        ]);
        session_start();
    }
}
startSession(); // Start session automatically when this file is included

// Prevent direct access
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}

/**
 * Generate and store a CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data from session
 * Returns null if not authenticated
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? 'viewer',
        'display_name' => $_SESSION['display_name'] ?? 'User',
        'organization_id' => $_SESSION['organization_id'] ?? null
    ];
}

/**
 * Require authentication for a page
 * Redirects to login if not authenticated
 */
function requireAuth($redirectTo = 'login.php') {
    if (!isAuthenticated()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
        safeRedirect("{$redirectTo}?redirect={$redirect}");
    }
}

/**
 * Require specific role(s) for a page
 * Returns 403 if user doesn't have required role
 */
function requireRole($requiredRoles, $redirectTo = '403.php') {
    requireAuth();
    
    if (!is_array($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }
    
    // Always allow super_admin to access admin pages if not explicitly excluded
    if (!in_array('super_admin', $requiredRoles) && !in_array('admin_only', $requiredRoles)) {
        $requiredRoles[] = 'super_admin';
    }

    $user = getCurrentUser();
    if (!in_array($user['role'], $requiredRoles)) {
        if ($redirectTo === false) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
        safeRedirect("{$redirectTo}");
    }
}

/**
 * Check if user has at least one of the specified roles
 */
function hasAnyRole($roles, $user = null) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if ($user === null) {
        $user = getCurrentUser();
    }

    if (!$user) {
        return false;
    }
    
    return in_array($user['role'], $roles);
}

/**
 * Safe redirect function
 */
function safeRedirect($url, $statusCode = 302) {
    if (!headers_sent()) {
        header("Location: {$url}", true, $statusCode);
        exit();
    } else {
        // JavaScript fallback
        echo "<script>window.location.href='{$url}';</script>";
        exit();
    }
}

/**
 * Generate secure random string
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Sanitize user input for database
 */
function sanitizeInput($input, $type = 'string') {
    if (is_null($input)) {
        return null;
    }
    
    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'string':
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}

/**
 * Log user activity for security audit
 */
function logActivity($action, $entity = null, $entityId = null) {
    global $connection; // Assuming $connection is available
    
    $user = getCurrentUser();
    $userId = $user ? $user['id'] : null;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    try {
        $stmt = $connection->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("ississ", $userId, $action, $entity, $entityId, $ipAddress, $userAgent);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Silent fail - don't break application if logging fails
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Check if username/email already exists
 */
function userExists($username, $email, $organization_id) {
    global $connection;
    
    $stmt = $connection->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND organization_id = ?");
    $stmt->bind_param("ssi", $username, $email, $organization_id);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    global $connection;
    
    $stmt = $connection->prepare("
        SELECT user_id, username, email, display_name, role, created_at 
        FROM users WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * Register a new user
 */
function registerUser($username, $email, $password, $display_name, $organization_id, $role = 'viewer') {
    global $connection;

    // Check if username or email already exists in the organization
    if (userExists($username, $email, $organization_id)) {
        return ['success' => false, 'message' => 'Username or email already registered in this organization.'];
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $connection->prepare("
            INSERT INTO users (username, email, password_hash, display_name, role, organization_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssi", $username, $email, $password_hash, $display_name, $role, $organization_id);
        $stmt->execute();

        if ($stmt->affected_rows === 1) {
            $userId = $stmt->insert_id;
            logActivity('User Registered', 'User', $userId);
            $stmt->close();
            return ['success' => true, 'message' => 'Registration successful!', 'user_id' => $userId];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Registration failed: Could not insert user.'];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Registration SQL Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An unexpected database error occurred during registration.'];
    }
}

/**
 * Verify user credentials
 */
function verifyCredentials($usernameOrEmail, $password) {
    global $connection;
    
    $stmt = $connection->prepare("
        SELECT user_id, username, email, password_hash, role, display_name, account_locked_until, organization_id 
        FROM users WHERE username = ? OR email = ?
    ");
    $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [false, "Invalid credentials"];
    }
    
    $user = $result->fetch_assoc();
    
    // Check if account is locked
    if (!empty($user['account_locked_until'])) {
        $lockTime = strtotime($user['account_locked_until']);
        if ($lockTime > time()) {
            return [false, "Account is temporarily locked. Please try again later."];
        }
    }

    if (!password_verify($password, $user['password_hash'])) {
        return [false, "Invalid credentials"];
    }
    
    return [true, $user];
}