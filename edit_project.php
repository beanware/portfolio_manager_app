<?php
// edit_project.php
session_start();
require_once 'connection.php';

// Get the project ID from the URL
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($projectId > 0) {
    // Retrieve the project details from the database
    $stmt = $connection->prepare("SELECT project_name, project_description, project_location, project_date, project_type FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();
    } else {
        echo "Project not found.";
        exit;
    }
    $stmt->close();
} else {
    echo "Invalid Project ID.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project</title>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <h1>Edit Project</h1>
    <form action="update_project.php" method="POST">
        <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
        <label for="project_name">Project Name:</label>
        <input type="text" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
        <br>

        <label for="project_description">Description:</label>
        <textarea id="project_description" name="project_description" required><?php echo htmlspecialchars($project['project_description']); ?></textarea>
        <br>

        <label for="project_location">Location:</label>
        <input type="text" id="project_location" name="project_location" value="<?php echo htmlspecialchars($project['project_location']); ?>" required>
        <br>

        <label for="project_date">Date:</label>
        <input type="date" id="project_date" name="project_date" value="<?php echo htmlspecialchars($project['project_date']); ?>" required>
        <br>

        <label for="project_type">Type:</label>
        <input type="text" id="project_type" name="project_type" value="<?php echo htmlspecialchars($project['project_type']); ?>" required>
        <br>

        <button type="submit">Update Project</button>
    </form>
</body>
</html>
