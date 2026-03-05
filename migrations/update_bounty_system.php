<?php
require_once 'connection.php';

echo "Updating Bounty System (Specific Bounties & Dead Ends)...\n";

// 1. Add 'bounty_info' to projects table
$sql_projects = "ALTER TABLE projects ADD COLUMN bounty_info VARCHAR(255) DEFAULT 'Contact for details' AFTER perks;";

// 2. Update 'status' enum in leads table to include 'dead_end'
$sql_leads = "ALTER TABLE leads MODIFY COLUMN status ENUM('pending', 'verified', 'deal_closed', 'paid', 'rejected', 'dead_end') DEFAULT 'pending';";

try {
    $pdo->exec($sql_projects);
    echo "SUCCESS: Added 'bounty_info' to 'projects' table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "SKIPPED: Column 'bounty_info' already exists.\n";
    } else {
        echo "ERROR: Failed to update 'projects' table - " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec($sql_leads);
    echo "SUCCESS: Updated 'status' enum in 'leads' table.\n";
} catch (PDOException $e) {
    echo "ERROR: Failed to update 'leads' table - " . $e->getMessage() . "\n";
}

echo "Migration complete.\n";
?>