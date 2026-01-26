<?php
require_once 'connection.php'; // Include database connection

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = $_POST['project_name'];
    $projectDescription = $_POST['project_description'];
    $projectLocation = $_POST['project_location'] ?? null;
    $projectDate = $_POST['project_date'] ?? null;
    $projectType = $_POST['project_type'] ?? null;

    // Insert project into Projects table
    $sql = "INSERT INTO projects (project_name, project_description, project_location, project_date, project_type) VALUES (?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("sssss", $projectName, $projectDescription, $projectLocation, $projectDate, $projectType);

    if ($stmt->execute()) {
        $projectId = $connection->insert_id;

        // Handle main image upload
        if (!empty($_FILES['main_image']['tmp_name'])) {
            $mainImagePath = 'uploads/main/' . basename($_FILES['main_image']['name']);
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $mainImagePath)) {
                $mainImageTitle = $_FILES['main_image']['name'];
                $mainImageDescription = $_POST['main_image_description'] ?? null;

                $sql = "INSERT INTO mainimages (project_id, image_title, image_description, image_path) VALUES (?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param("isss", $projectId, $mainImageTitle, $mainImageDescription, $mainImagePath);
                if (!$stmt->execute()) {
                    $message .= "Error inserting main image details into 'mainimages' table: " . $stmt->error . ". ";
                }
            }
        }

        // Handle carousel images upload
        if (!empty($_FILES['carousel_images']['name'][0])) {
            error_log("Attempting to handle carousel images upload for projectId: " . $projectId); // Add log
            $carouselImages = $_FILES['carousel_images'];
            $numFiles = count($carouselImages['name']);
            $carouselData = [];

            for ($i = 0; $i < $numFiles; $i++) {
                $carouselImagePath = 'uploads/carousel/' . basename($carouselImages['name'][$i]);
                if (move_uploaded_file($carouselImages['tmp_name'][$i], $carouselImagePath)) {
                    $carouselData[] = [
                        'project_id' => $projectId,
                        'image_title' => $carouselImages['name'][$i],
                        // UI Note: Only one input field is provided for carousel_image_descriptions.
                        // So, $carousel_image_descriptions[$i] will be null for subsequent images.
                        'image_description' => $_POST['carousel_image_descriptions'][$i] ?? null,
                        'image_path' => $carouselImagePath,
                        'display_order' => $i + 1
                    ];
                    error_log("Carousel image uploaded: " . $carouselImagePath); // Add log
                } else {
                    $uploadError = "Failed to upload carousel image '" . $carouselImages['name'][$i] . "'. PHP Error Code: " . $carouselImages['error'][$i] . ". ";
                    $message .= $uploadError;
                    error_log($uploadError . " Details: " . print_r($carouselImages['error'], true)); // Add log
                }
            }

            if (!empty($carouselData)) { // Check if any images were successfully prepared for insert
                error_log("Preparing to insert " . count($carouselData) . " carousel images."); // Add log
                $sql = "INSERT INTO carouselimages (project_id, image_title, image_description, image_path, display_order) VALUES (?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                if (!$stmt) {
                    error_log("Carousel image INSERT statement preparation failed: " . $connection->error); // Add log
                    $message .= "Database error: Could not prepare carousel image insert statement. ";
                } else {
                    foreach ($carouselData as $data) {
                        $bp_projectId = $data['project_id'];
                        $bp_imageTitle = $data['image_title'];
                        $bp_imageDescription = $data['image_description'];
                        $bp_imagePath = $data['image_path'];
                        $bp_displayOrder = $data['display_order'];
                        $stmt->bind_param("isssi", $bp_projectId, $bp_imageTitle, $bp_imageDescription, $bp_imagePath, $bp_displayOrder);
                        if (!$stmt->execute()) {
                            $insertError = "Error inserting carousel image details for '" . $data['image_title'] . "' into 'carouselimages' table: " . $stmt->error . ". ";
                            $message .= $insertError;
                            error_log($insertError); // Add log
                        } else {
                            error_log("Carousel image inserted: " . $data['image_title']); // Add log
                        }
                    }
                }
            } else {
                error_log("No carousel images were successfully moved for project: " . $projectId); // Add log
            }
        } else {
            error_log("No carousel images selected for project: " . $projectId); // Add log
        }


        $message = "Project and images added successfully";
    } else {
        $message = "Error adding project: " . $stmt->error;
    }

    $stmt->close();
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Project</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
        }
        form {
            max-width: 600px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background-color: #f2f2f2;
            border-radius: 8px;
        }
        input, textarea, button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }
        button {
            background-color: #0d1b2a;
            color: white;
            border-radius: 4px;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #0d1b2a;
            backdrop-filter: blur(10px);
        }
        .success, .error {
            color: #333;
            margin-bottom: 10px;
        }
        .projects {
            margin-top: 20px;
            width: 100%;
            max-width: 600px;
        }
        .project {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff;
            box-sizing: border-box;
        }
        .carousel {
            display: flex;
            overflow-x: auto;
            gap: 10px;
        }
        .carousel img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .project {
                padding: 5px;
                font-size: 14px;
            }
            .carousel img {
                max-width: 50px;
                max-height: 50px;
            }
        }

        @media (max-width: 480px) {
            form {
                max-width: 90%;
            }
            .project {
                padding: 2px;
                font-size: 12px;
            }
            .carousel img {
                max-width: 30px;
                max-height: 30px;
            }
        }
    </style>

    <script>
        async function fetchProjects() {
            const response = await fetch('index.php');
            const projects = await response.json();
            const projectsContainer = document.querySelector('.projects');
            projectsContainer.innerHTML = '';

            projects.forEach(project => {
                const projectElement = document.createElement('div');
                projectElement.className = 'project';
                projectElement.innerHTML = 
                    `<h3>${project.project_name}</h3>
                    <p>${project.project_description}</p>
                    <div class="carousel" id="carousel-${project.project_id}"></div>`;
                projectsContainer.appendChild(projectElement);

                fetchCarouselImages(project.project_id);
            });
        }

        async function fetchCarouselImages(projectId) {
            const response = await fetch(`index.php?project_id=${projectId}`);
            const images = await response.json();
            const carousel = document.getElementById(`carousel-${projectId}`);

            images.forEach(image => {
                const imgElement = document.createElement('img');
                imgElement.src = image.image_path; // Use image path from the database
                imgElement.alt = image.image_title;
                carousel.appendChild(imgElement);
            });
        }

        document.addEventListener('DOMContentLoaded', fetchProjects);
    </script>
</head>
<body>
    <?php if ($message): ?>
        <div class="<?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form action="add_project.php" method="POST" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" id="title" name="project_name" required>

        <label for="description">Description:</label>
        <textarea id="description" name="project_description" required></textarea>

        <label for="location">Location:</label>
        <input type="text" id="location" name="project_location">

        <label for="date">Date:</label>
        <input type="date" id="date" name="project_date">

        <label for="type">Type:</label>
        <input type="text" id="type" name="project_type">

        <label for="main_image">Main Image:</label>
        <input type="file" id="main_image" name="main_image" accept="image/*" required>

        <label for="main_image_description">Main Image Description:</label>
        <input type="text" id="main_image_description" name="main_image_description">

        <label for="carousel_images">Carousel Images:</label>
        <input type="file" id="carousel_images" name="carousel_images[]" accept="image/*" multiple>

        <label for="carousel_image_descriptions">Carousel Image Descriptions:</label>
        <input type="text" name="carousel_image_descriptions[]" placeholder="Image description">

        <button type="submit">Add Project</button>
    </form>

    <div class="projects"></div>
</body>
</html>

