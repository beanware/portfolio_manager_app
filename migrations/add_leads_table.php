<?php
require_once 'connection.php';

echo "Running Bounty System migration...\n";

// Create 'leads' table
$sql = "
CREATE TABLE IF NOT EXISTS leads (
    lead_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    organization_id INT NOT NULL,
    referrer_name VARCHAR(255) NOT NULL,
    referrer_email VARCHAR(255) NOT NULL,
    referrer_phone VARCHAR(50) NOT NULL,
    buyer_name VARCHAR(255) NOT NULL,
    buyer_contact VARCHAR(255) NOT NULL,
    reward_amount DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('pending', 'verified', 'deal_closed', 'paid', 'rejected') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(organization_id) ON DELETE CASCADE
);";

try {
    $pdo->exec($sql);
    echo "SUCCESS: Table 'leads' created successfully.\n";
} catch (PDOException $e) {
    echo "ERROR: Failed to create 'leads' table - " . $e->getMessage() . "\n";
}

echo "Migration complete.\n";
?>