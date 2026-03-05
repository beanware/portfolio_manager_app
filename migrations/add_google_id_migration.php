<?php
require_once 'connection.php';

echo "Adding google_id column to users table...
";

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL UNIQUE AFTER user_id");
    echo "SUCCESS: Column 'google_id' added to 'users'.
";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "SKIPPED: Column 'google_id' already exists.
";
    } else {
        echo "ERROR: " . $e->getMessage() . "
";
    }
}
?>