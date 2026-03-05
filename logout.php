<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

header("Location: login.php"); // Redirect to login page
exit();
?>
