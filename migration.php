<?php
require_once 'connection.php';

echo "Starting database migration...\n";

// Function to execute a migration query
function runMigrationQuery($pdo, $sql, $successMessage, $errorMessage) {
    try {
        $pdo->exec($sql);
        echo "SUCCESS: " . $successMessage . "\n";
    } catch (PDOException $e) {
        // Check if it's an "already exists" error for idempotency
        if (strpos($e->getMessage(), 'Duplicate column name') !== false ||
            (strpos($e->getMessage(), 'Table') !== false && strpos($e->getMessage(), 'already exists') !== false) ||
            (strpos($e->getMessage(), 'Cannot add foreign key constraint') !== false && strpos($e->getMessage(), 'already exists') !== false && strpos($e->getMessage(), 'foreign key constraint fails') === false) // Exclude "foreign key constraint fails" which means actual data issue
        ) {
            echo "SKIPPED: " . $errorMessage . " (already exists or constraint duplicate)\n";
        } else {
            echo "ERROR: " . $errorMessage . " - " . $e->getMessage() . "\n";
            // Depending on desired strictness, you might exit here
            // exit(1);
        }
    }
}

// 1. Create 'organizations' table
$sql = "
CREATE TABLE IF NOT EXISTS organizations (
    organization_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    organization_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);";
runMigrationQuery($pdo, $sql, "Table 'organizations' created successfully.", "Failed to create 'organizations' table.");

// 2. Add 'organization_id' to existing tables and set foreign keys
$tablesToModify = [
    'users' => ['onDelete' => 'SET NULL', 'canBeNotNull' => false],
    'projects' => ['onDelete' => 'CASCADE', 'canBeNotNull' => true],
    'carouselimages' => ['onDelete' => 'CASCADE', 'canBeNotNull' => true],
    'mainimages' => ['onDelete' => 'CASCADE', 'canBeNotNull' => true],
    'project_images' => ['onDelete' => 'CASCADE', 'canBeNotNull' => true],
    'audit_log' => ['onDelete' => 'SET NULL', 'canBeNotNull' => false], // Keep audit trail even if user/org is deleted
    'system_settings' => ['onDelete' => 'SET NULL', 'canBeNotNull' => false] // Settings might be global or revert to global if org deleted
];

foreach ($tablesToModify as $tableName => $properties) {
    $onDeleteAction = $properties['onDelete'];
    $canBeNotNull = $properties['canBeNotNull'];

    // Check if column exists before adding it
    $columnExists = false;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM {$tableName} LIKE 'organization_id'");
        $columnExists = $stmt->fetch();
    } catch (PDOException $e) {
        echo "WARNING: Could not check for column 'organization_id' in '{$tableName}'. Error: " . $e->getMessage() . "\n";
        continue; // Skip to next table
    }

    if (!$columnExists) {
        $sql = "ALTER TABLE {$tableName} ADD COLUMN organization_id INT(11) NULL DEFAULT NULL;";
        runMigrationQuery($pdo, $sql, "Column 'organization_id' added to '{$tableName}'.", "Failed to add 'organization_id' to '{$tableName}'.");
    } else {
        echo "SKIPPED: Column 'organization_id' already exists in '{$tableName}'.\n";
    }

    // Add foreign key constraint if it doesn't exist
    $fkName = "fk_{$tableName}_organization_id";
    try {
        // Check if FK exists
        $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableName}' AND CONSTRAINT_NAME = '{$fkName}'");
        if (!$stmt->fetch()) {
            $sql = "ALTER TABLE {$tableName} ADD CONSTRAINT {$fkName} FOREIGN KEY (organization_id) REFERENCES organizations(organization_id) ON DELETE {$onDeleteAction};";
            runMigrationQuery($pdo, $sql, "Foreign key '{$fkName}' added to '{$tableName}'.", "Failed to add foreign key '{$fkName}' to '{$tableName}'.");
        } else {
            echo "SKIPPED: Foreign key '{$fkName}' already exists on '{$tableName}'.\n";
        }
    } catch (PDOException $e) {
        echo "ERROR: Could not check or add foreign key '{$fkName}' to '{$tableName}'. " . $e->getMessage() . "\n";
    }
}

// 3. Update unique constraints for multi-tenancy
$uniqueConstraints = [
    'users' => [
        ['column' => 'username', 'constraint' => 'username'],
        ['column' => 'email', 'constraint' => 'email']
    ],
    'projects' => [
        ['column' => 'project_slug', 'constraint' => 'project_slug']
    ]
];

foreach ($uniqueConstraints as $tableName => $constraints) {
    foreach ($constraints as $constraintInfo) {
        $column = $constraintInfo['column'];
        $constraintName = $constraintInfo['constraint'];

        // Drop existing unique constraint
        try {
            // Check if constraint exists before dropping
            $stmt = $pdo->query("SHOW INDEX FROM {$tableName} WHERE Key_name = '{$constraintName}' AND Non_unique = 0");
            if ($stmt->fetch()) {
                $sql = "ALTER TABLE {$tableName} DROP INDEX {$constraintName};";
                runMigrationQuery($pdo, $sql, "Unique constraint '{$constraintName}' dropped from '{$tableName}'.", "Failed to drop unique constraint '{$constraintName}' from '{$tableName}'.");
            } else {
                echo "SKIPPED: Unique constraint '{$constraintName}' does not exist on '{$tableName}'.\n";
            }
        } catch (PDOException $e) {
            echo "ERROR: Could not drop unique constraint '{$constraintName}' on '{$tableName}'. " . $e->getMessage() . "\n";
        }

        // Add composite unique constraint with organization_id
        $newConstraintName = "uq_{$tableName}_{$column}_org";
        try {
             // Check if new constraint exists before adding
            $stmt = $pdo->query("SHOW INDEX FROM {$tableName} WHERE Key_name = '{$newConstraintName}'");
            if (!$stmt->fetch()) {
                $sql = "ALTER TABLE {$tableName} ADD CONSTRAINT {$newConstraintName} UNIQUE ({$column}, organization_id);";
                runMigrationQuery($pdo, $sql, "Composite unique constraint '{$newConstraintName}' added to '{$tableName}'.", "Failed to add composite unique constraint '{$newConstraintName}' to '{$tableName}'.");
            } else {
                echo "SKIPPED: Composite unique constraint '{$newConstraintName}' already exists on '{$tableName}'.\n";
            }
        } catch (PDOException $e) {
            echo "ERROR: Could not add composite unique constraint '{$newConstraintName}' on '{$tableName}'. " . $e->getMessage() . "\n";
        }
    }
}

// 4. Populate a default organization if none exists
$sql = "INSERT IGNORE INTO organizations (organization_name) VALUES ('Default Organization');";
runMigrationQuery($pdo, $sql, "Default organization created or already exists.", "Failed to create default organization.");

// 5. Assign existing data to the default organization
// First, get the ID of the 'Default Organization'
$defaultOrgId = null;
try {
    $stmt = $pdo->query("SELECT organization_id FROM organizations WHERE organization_name = 'Default Organization'");
    $defaultOrgId = $stmt->fetchColumn();
    if ($defaultOrgId) {
        echo "Default organization ID found: " . $defaultOrgId . "\n";
    } else {
        echo "ERROR: Default organization not found after creation attempt. Cannot assign existing data.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: Failed to retrieve default organization ID - " . $e->getMessage() . "\n";
}

if ($defaultOrgId) {
    foreach ($tablesToModify as $tableName => $properties) {
        $canBeNotNull = $properties['canBeNotNull'];
        // Only update if organization_id is NULL
        $sql = "UPDATE {$tableName} SET organization_id = {$defaultOrgId} WHERE organization_id IS NULL;";
        runMigrationQuery($pdo, $sql, "Existing records in '{$tableName}' assigned to default organization.", "Failed to assign existing records in '{$tableName}' to default organization.");

        // After updating, make the column NOT NULL if allowed and if it's currently nullable
        if ($canBeNotNull) {
            $isNullable = true;
            try {
                $stmt = $pdo->query("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableName}' AND COLUMN_NAME = 'organization_id'");
                $result = $stmt->fetch();
                if ($result && $result['IS_NULLABLE'] === 'NO') {
                    $isNullable = false;
                }
            } catch (PDOException $e) {
                echo "WARNING: Could not check nullability of 'organization_id' in '{$tableName}'. Error: " . $e->getMessage() . "\n";
            }

            if ($isNullable) {
                $sql = "ALTER TABLE {$tableName} MODIFY COLUMN organization_id INT(11) NOT NULL;";
                runMigrationQuery($pdo, $sql, "Column 'organization_id' in '{$tableName}' set to NOT NULL.", "Failed to set 'organization_id' in '{$tableName}' to NOT NULL.");
            } else {
                 echo "SKIPPED: Column 'organization_id' in '{$tableName}' is already NOT NULL.\n";
            }
        } else {
            echo "SKIPPED: Column 'organization_id' in '{$tableName}' cannot be NOT NULL due to foreign key constraint.\n";
        }
    }
} else {
    echo "Skipping assignment of existing data as default organization ID could not be determined.\n";
}

echo "Database migration complete.\n";

?>