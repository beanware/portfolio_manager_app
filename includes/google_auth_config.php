<?php
/**
 * Google OAuth Configuration
 */

// Replace these with your actual Google OAuth credentials
define('GOOGLE_CLIENT_ID', '714040254600-jpuioqlaimh2sh6rqme83ftuejqkmks0.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Determine the base URL dynamically
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$current_dir = dirname($script_name);
// Clean up the directory path (ensure it doesn't end with a slash)
$current_dir = rtrim(str_replace('\\', '/', $current_dir), '/');
$base_url = "$protocol://$host$current_dir";

define('GOOGLE_REDIRECT_URI', $base_url . '/google_callback.php');

/**
 * Get Google Login URL
 */
function getGoogleLoginUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'offline',
        'prompt' => 'select_account'
    ];
    
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}
?>