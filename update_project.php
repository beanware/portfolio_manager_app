<?php
define('ACCESS_ALLOWED', true);
require_once 'connection.php';
require_once 'includes/auth_functions.php';

// Authenticate
requireAuth();
requireRole('admin');

$currentUser = getCurrentUser();
$organization_id = $currentUser['organization_id'];
$isSuperAdmin = hasAnyRole(['super_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("Security validation failed.");
    }

    $projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
    $action = $_POST['action'] ?? 'update_details';

    // Verify ownership
    $stmt = $connection->prepare("SELECT organization_id FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$project || (!$isSuperAdmin && $project['organization_id'] != $organization_id)) {
        die("Access denied or project not found.");
    }

    // ACTION: Update Basic Details
    if ($action === 'update_details') {
        $name = sanitizeInput($_POST['project_name']);
        $desc = $_POST['project_description'];
        $loc = sanitizeInput($_POST['project_location']);
        $date = $_POST['project_date'];
        $type = sanitizeInput($_POST['project_type']);
        $status = sanitizeInput($_POST['project_status']);

        $stmt = $connection->prepare("UPDATE projects SET project_name = ?, project_description = ?, project_location = ?, project_date = ?, project_type = ?, project_status = ? WHERE project_id = ?");
        $stmt->bind_param("ssssssi", $name, $desc, $loc, $date, $type, $status, $projectId);
        $stmt->execute();
        $stmt->close();
        
        logActivity('Updated Project Details', 'Project', $projectId);
        safeRedirect("edit_project.php?project_id=$projectId&success=Details+updated");
    }

    // ACTION: Update Main Image
    elseif ($action === 'update_main_image') {
        if (!empty($_FILES['main_image']['name'])) {
            $title = sanitizeInput($_POST['main_image_title'] ?? '');
            $filename = time() . '_main_' . basename($_FILES['main_image']['name']);
            $targetPath = 'uploads/main/' . $filename;

            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $targetPath)) {
                // Delete old image if exists? (Optional but good practice)
                $connection->query("DELETE FROM mainimages WHERE project_id = $projectId");
                
                $stmt = $connection->prepare("INSERT INTO mainimages (project_id, image_title, image_path, organization_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $projectId, $title, $targetPath, $project['organization_id']);
                $stmt->execute();
                $stmt->close();
                
                logActivity('Updated Main Image', 'Project', $projectId);
                safeRedirect("edit_project.php?project_id=$projectId&success=Main+image+updated");
            }
        }
    }

    // ACTION: Add Carousel Images
    elseif ($action === 'add_carousel') {
        if (isset($_FILES['carousel_images']) && is_array($_FILES['carousel_images']['name'])) {
            foreach ($_FILES['carousel_images']['name'] as $key => $name) {
                if (!empty($name)) {
                    $filename = time() . '_gallery_' . basename($name);
                    $targetPath = 'uploads/carousel/' . $filename;

                    if (move_uploaded_file($_FILES['carousel_images']['tmp_name'][$key], $targetPath)) {
                        $stmt = $connection->prepare("INSERT INTO carouselimages (project_id, image_title, image_path, organization_id) VALUES (?, ?, ?, ?)");
                        $emptyTitle = '';
                        $stmt->bind_param("issi", $projectId, $emptyTitle, $targetPath, $project['organization_id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
            logActivity('Added Gallery Images', 'Project', $projectId);
            safeRedirect("edit_project.php?project_id=$projectId&success=Gallery+updated");
        }
    }
}

safeRedirect("projects.php");
?>
