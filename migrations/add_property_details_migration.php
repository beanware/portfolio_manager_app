<?php
require_once 'connection.php';

echo "Adding property-specific fields to projects table...
";

$fields = [
    'price' => "VARCHAR(255) NULL",
    'amenities' => "TEXT NULL",
    'perks' => "TEXT NULL"
];

foreach ($fields as $field => $type) {
    try {
        $pdo->exec("ALTER TABLE projects ADD COLUMN $field $type");
        echo "SUCCESS: Column '$field' added to projects.
";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "SKIPPED: Column '$field' already exists in projects.
";
        } else {
            echo "ERROR adding '$field' to projects: " . $e->getMessage() . "
";
        }
    }
}

echo "Migration complete.
";
?>