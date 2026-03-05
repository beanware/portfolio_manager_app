<?php
require_once 'connection.php';

echo "Expanding organizations table with real estate specific fields...
";

$fields = [
    'company_address' => "TEXT NULL",
    'contact_email' => "VARCHAR(255) NULL",
    'contact_phone' => "VARCHAR(50) NULL",
    'website' => "VARCHAR(255) NULL",
    'license_number' => "VARCHAR(100) NULL",
    'description' => "TEXT NULL",
    'tax_id' => "VARCHAR(100) NULL"
];

foreach ($fields as $field => $type) {
    try {
        $pdo->exec("ALTER TABLE organizations ADD COLUMN $field $type");
        echo "SUCCESS: Column '$field' added.
";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "SKIPPED: Column '$field' already exists.
";
        } else {
            echo "ERROR adding '$field': " . $e->getMessage() . "
";
        }
    }
}

echo "Migration complete.
";
?>