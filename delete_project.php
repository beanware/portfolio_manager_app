<?php
// Include necessary files
require_once 'connection.php';

// Check if project_id is set
if (isset($_POST['project_id'])) {
    $projectId = intval($_POST['project_id']);

    // Delete the project from the database
    $sql = "DELETE FROM projects WHERE project_id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $projectId);

    if ($stmt->execute()) {
        // Redirect to projects page after deletion
        header("Location: projects.php");
        exit();
    } else {
        // Handle error (e.g., log it, show an error message, etc.)
        echo "Error deleting project.";
    }

    $stmt->close();
}

// Close the database connection
$connection->close();
?>
