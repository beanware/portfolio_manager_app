<?php
// update_project.php
session_start();
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
    $projectName = isset($_POST['project_name']) ? $_POST['project_name'] : '';
    $projectDescription = isset($_POST['project_description']) ? $_POST['project_description'] : '';
    $projectLocation = isset($_POST['project_location']) ? $_POST['project_location'] : '';
    $projectDate = isset($_POST['project_date']) ? $_POST['project_date'] : '';
    $projectType = isset($_POST['project_type']) ? $_POST['project_type'] : '';

    if ($projectId > 0) {
        // Update the project details in the database
        $stmt = $connection->prepare("UPDATE projects SET project_name = ?, project_description = ?, project_location = ?, project_date = ?, project_type = ? WHERE project_id = ?");
        $stmt->bind_param("sssssi", $projectName, $projectDescription, $projectLocation, $projectDate, $projectType, $projectId);
        $stmt->execute();
        $stmt->close();

        // Redirect to the projects page after updating
        header("Location: projects.php");
        exit;
    } else {
        echo "Invalid Project ID.";
    }
} else {
    echo "Invalid Request.";
}

$connection->close();
?>
