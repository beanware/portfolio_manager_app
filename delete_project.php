<?php
define('ACCESS_ALLOWED', true); // Define this to allow access to included files

require_once 'connection.php';
require_once 'includes/auth_functions.php';

// Authenticate and authorize admin access
requireRole('admin');

// Get the current user
$currentUser = getCurrentUser();
$organization_id = $currentUser['organization_id'];
$isSuperAdmin = hasAnyRole(['super_admin']);

// If the user has no organization and is not a super_admin, block access
if (!$organization_id && !$isSuperAdmin) {
    safeRedirect("403.php?message=" . urlencode("You are not associated with any organization."), 403);
}

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'])) {
    $projectId = intval($_POST['project_id']);

    if ($projectId > 0) {
        // Start a transaction for atomicity
        $connection->begin_transaction();

        try {
            // 1. Fetch image paths for mainimages and carouselimages
            $imagePathsToDelete = [];
            
            // Build the WHERE clause dynamically based on role
            $whereClause = "WHERE project_id = ?";
            if (!$isSuperAdmin) {
                $whereClause .= " AND organization_id = $organization_id";
            }

            // Main Image
            $stmt = $connection->prepare("SELECT image_path FROM mainimages " . $whereClause);
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $imagePathsToDelete[] = $row['image_path'];
            }
            $stmt->close();

            // Carousel Images
            $stmt = $connection->prepare("SELECT image_path FROM carouselimages " . $whereClause);
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $imagePathsToDelete[] = $row['image_path'];
            }
            $stmt->close();

            // 2. Delete records from mainimages and carouselimages tables
            $stmt = $connection->prepare("DELETE FROM mainimages " . $whereClause);
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $stmt->close();

            $stmt = $connection->prepare("DELETE FROM carouselimages " . $whereClause);
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $stmt->close();

            // 3. Delete the project record from the projects table
            $stmt = $connection->prepare("DELETE FROM projects " . $whereClause);
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $deletedRows = $stmt->affected_rows;
            $stmt->close();

            if ($deletedRows > 0) {
                // 4. Delete physical files
                foreach ($imagePathsToDelete as $relativePath) {
                    // Construct the absolute path
                    $absolutePath = __DIR__ . '/' . $relativePath; 

                    if (file_exists($absolutePath) && is_file($absolutePath)) {
                        if (unlink($absolutePath)) {
                            error_log("Successfully deleted file: " . $absolutePath);
                        } else {
                            error_log("Failed to delete file: " . $absolutePath . " - Check permissions.");
                        }
                    } else {
                        error_log("File not found or is not a regular file: " . $absolutePath);
                    }
                }
                $connection->commit();
                $message = "Project and associated images deleted successfully!";
                $messageType = 'success';
            } else {
                $connection->rollback();
                $message = "Project not found, already deleted, or you do not have permission to delete it.";
            }

        } catch (mysqli_sql_exception $e) {
            $connection->rollback();
            $message = "Database error: " . $e->getMessage();
            error_log("Delete Project SQL Error: " . $e->getMessage());
        }
    } else {
        $message = "Invalid project ID.";
    }
} else {
    $message = "Invalid request method or missing project ID.";
}

$connection->close();

// Redirect back to projects.php with message
header("Location: projects.php?message=" . urlencode($message) . "&type=" . urlencode($messageType));
exit();
?>