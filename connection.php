<?php
$servername = getenv('DB_SERVER') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "miks_port";

// Initialize MySQLi connection
$connection = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($connection->connect_error) {
    error_log("Connection failed: " . $connection->connect_error); // Log error
    die("Connection failed: " . $connection->connect_error); // Display specific error message
}

// Set charset to utf8mb4 for wider character support
$connection->set_charset("utf8mb4");

// Initialize PDO connection (optional, if needed elsewhere)
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Connection failed: ' . $e->getMessage()); // Log error
    die('Connection failed: ' . $e->getMessage()); // Display specific error message
}

?>
