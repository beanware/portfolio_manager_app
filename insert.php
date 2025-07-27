<?php
require_once 'connection.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = $_POST['project_name'];
    $projectDescription = $_POST['project_description'];

    // Insert project into Projects table
    $sql = "INSERT INTO projects (project_name, project_description) VALUES (?, ?)";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ss", $projectName, $projectDescription);

    if ($stmt->execute()) {
        $projectId = $connection->insert_id;

        // Handle main image upload
        $uploadDir = 'uploads/';
        $mainImagePath = $uploadDir . basename($_FILES['main_image']['name']);
        move_uploaded_file($_FILES['main_image']['tmp_name'], $mainImagePath);

        $sql = "INSERT INTO mainimages (project_id, image_title, image_path) VALUES (?, ?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("iss", $projectId, $_FILES['main_image']['name'], $mainImagePath);
        $stmt->execute();

        // Handle carousel images upload
        if (!empty($_FILES['carousel_images']['name'][0])) {
            $carouselImages = $_FILES['carousel_images'];
            $numFiles = count($carouselImages['name']);

            for ($i = 0; $i < $numFiles; $i++) {
                $carouselImagePath = $uploadDir . basename($carouselImages['name'][$i]);
                move_uploaded_file($carouselImages['tmp_name'][$i], $carouselImagePath);

                $sql = "INSERT INTO carouselimages (project_id, image_title, image_path) VALUES (?, ?, ?)";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param("iss", $projectId, $carouselImages['name'][$i], $carouselImagePath);
                $stmt->execute();
            }
        }

        echo "Project and images added successfully";
    } else {
        echo "Error adding project: " . $stmt->error;
    }

    $stmt->close();
    $connection->close();
}
?>
