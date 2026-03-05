<?php
require_once 'connection.php';

echo "Adding visit tracking system...\n";

// 1. Add 'views' column to projects for total count
$sql1 = "ALTER TABLE projects ADD COLUMN views INT DEFAULT 0;";

// 2. Create a detailed visits table for time-based statistics
$sql2 = "
CREATE TABLE IF NOT EXISTS project_visits (
    visit_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE
);";

try {
    $pdo->exec($sql1);
    echo "SUCCESS: Added 'views' column to 'projects'.\n";
} catch (PDOException $e) {
    echo "SKIPPED: 'views' column might already exist.\n";
}

try {
    $pdo->exec($sql2);
    echo "SUCCESS: Created 'project_visits' table.\n";
} catch (PDOException $e) {
    echo "ERROR creating 'project_visits': " . $e->getMessage() . "\n";
}

echo "Migration complete.\n";
?>