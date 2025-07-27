<?php
$servername = getenv('DB_SERVER') ?: "localhost";
$username = getenv('DB_USER') ?: "vmyeemun_domuka";
$password = getenv('DB_PASS') ?: "661942.do";
$dbname = getenv('DB_NAME') ?: "vmyeemun_miks_port";

// Initialize MySQLi connection
$connection = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($connection->connect_error) {
    error_log("Connection failed: " . $connection->connect_error); // Log error
    die("Connection failed. Please try again later."); // Generic error message
}

// Set charset to utf8mb4 for wider character support
$connection->set_charset("utf8mb4");

// Initialize PDO connection (optional, if needed elsewhere)
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Connection failed: ' . $e->getMessage()); // Log error
    die('Connection failed. Please try again later.'); // Generic error message
}

?>
