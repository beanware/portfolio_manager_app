<?php
// Include the connection.php file for database connection
include 'connection.php';

// Initialize error array
$errors = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $projectName = $_POST['project_name'] ?? '';
    $projectDescription = $_POST['project_description'] ?? '';
    $projectLocation = $_POST['project_location'] ?? '';
    $projectDate = $_POST['project_date'] ?? '';
    $projectType = $_POST['project_type'] ?? '';

    // Insert new project into database
    if ($projectName) {
        try {
            // Prepare and execute the query
            $stmt = $pdo->prepare('INSERT INTO projects (project_name, project_description, project_location, project_date, project_type) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$projectName, $projectDescription, $projectLocation, $projectDate, $projectType]);

            // Get the last inserted project ID
            $projectId = $pdo->lastInsertId();

            // Handle Main Image Upload
            if (!empty($_FILES['main_image']['name'])) {
                $mainImagePath = 'uploads/main/' . basename($_FILES['main_image']['name']);
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $mainImagePath)) {
                    $stmt = $pdo->prepare('INSERT INTO mainimages (project_id, image_title, image_path) VALUES (?, ?, ?)');
                    $stmt->execute([$projectId, $_POST['main_image_title'], $mainImagePath]);
                } else {
                    $errors[] = "Failed to upload main image. Error: " . $_FILES['main_image']['error'];
                }
            }

            // Handle Carousel Images Upload
            foreach ($_FILES['carousel_images']['name'] as $key => $name) {
                if (!empty($name)) {
                    $carouselImagePath = 'uploads/carousel/' . basename($name);
                    if (move_uploaded_file($_FILES['carousel_images']['tmp_name'][$key], $carouselImagePath)) {
                        $stmt = $pdo->prepare('INSERT INTO carouselimages (project_id, image_title, image_description, image_path, display_order) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$projectId, $_POST['carousel_titles'][$key], $_POST['carousel_descriptions'][$key], $carouselImagePath, $key + 1]);
                    } else {
                        $errors[] = "Failed to upload carousel image: $name. Error: " . $_FILES['carousel_images']['error'][$key];
                    }
                }
            }

            // Handle PDF Documentation Upload
            if (!empty($_FILES['documentation']['name'])) {
                $docPath = 'uploads/docs/' . basename($_FILES['documentation']['name']);
                if (move_uploaded_file($_FILES['documentation']['tmp_name'], $docPath)) {
                    $stmt = $pdo->prepare('UPDATE projects SET documentation = ? WHERE project_id = ?');
                    $stmt->execute([file_get_contents($docPath), $projectId]);
                } else {
                    $errors[] = "Failed to upload documentation. Error: " . $_FILES['documentation']['error'];
                }
            }

            // Redirect to avoid form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;

        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all projects
try {
    $projects = $pdo->query('SELECT * FROM projects')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Database fetch error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <a
    href="index.php"
  type="button"
  class="inline-block rounded border-2 border-primary px-6 pb-[6px] pt-2 text-xs font-medium uppercase transition duration-150 ease-in-out hover:border-primary-accent-300 hover:bg-primary-50/50 hover:text-white focus:border-primary-600 focus:bg-primary-50/50 focus:text-primary-600 focus:outline-none focus:ring-0 active:border-primary-700 active:text-primary-700 motion-reduce:transition-none dark:text-primary-500 dark:hover:bg-gray-700 dark:focus:bg-blue-950"
  data-twe-ripple-init>
  Back to Home
</a>

    <h1 class="text-3xl text-center font-bold mb-6">Project Management</h1>

    <!-- Add/Edit Project Form -->
    <div class="bg-white p-6 rounded shadow-md">
        <h2 class="text-2xl text-center mb-4">Add/Edit Project</h2>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-200 text-red-700 p-4 rounded mb-4">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="project_name" class="block text-gray-700">Project Name</label>
                <input type="text" name="project_name" id="project_name" class="w-full p-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="project_description" class="block text-gray-700">Project Description</label>
                <textarea name="project_description" id="project_description" class="w-full p-2 border border-gray-300 rounded"></textarea>
            </div>
            <div class="mb-4">
                <label for="project_location" class="block text-gray-700">Project Location</label>
                <input type="text" name="project_location" id="project_location" class="w-full p-2 border border-gray-300 rounded">
            </div>
            <div class="mb-4">
                <label for="project_date" class="block text-gray-700">Project Date</label>
                <input type="date" name="project_date" id="project_date" class="w-full p-2 border border-gray-300 rounded">
            </div>
            <div class="mb-4">
                <label for="project_type" class="block text-gray-700">Project Type</label>
                <input type="text" name="project_type" id="project_type" class="w-full p-2 border border-gray-300 rounded">
            </div>

            <!-- Main Image -->
            <div class="mb-4">
                <label for="main_image" class="block text-gray-700">Main Image</label>
                <input type="file" name="main_image" id="main_image" class="w-full p-2" accept="image/*">
                <input type="text" name="main_image_title" placeholder="Main Image Title" class="w-full p-2 border border-gray-300 rounded mt-2">
            </div>

            <!-- Carousel Images -->
            <div class="mb-4">
                <label for="carousel_images" class="block text-gray-700">Carousel Images</label>
                <input type="file" name="carousel_images[]" id="carousel_images" class="w-full p-2" multiple accept="image/*">
                <div class="mt-2">
                    <input type="text" name="carousel_titles[]" placeholder="Carousel Image Title" class="w-full p-2 border border-gray-300 rounded mb-2">
                    <textarea name="carousel_descriptions[]" placeholder="Carousel Image Description" class="w-full p-2 border border-gray-300 rounded mb-2"></textarea>
                </div>
            </div>

            <!-- Documentation -->
            <div class="mb-4">
                <label for="documentation" class="block text-gray-700">Upload Documentation (PDF)</label>
                <input type="file" name="documentation" id="documentation" class="w-full p-2" accept=".pdf">
            </div>

            <button type="submit" class="bg-gray-700 text-white py-2 px-4 rounded transition duration-300 ease-in-out">Submit</button>
        </form>
    </div>
        <!-- Display projects -->
    <div class="mb-6">
        <h2 class="text-2xl text-center font-bold mb-4">Existing Projects</h2>
        <?php foreach ($projects as $project): ?>
            <div class="bg-white p-4 mb-4 rounded transition duration-300 ease-in-out hover:shadow-lg dark:hover:shadow-black/30 cursor-pointer shadow-sm">
                <!-- Fetch and display main image -->
                <?php
                // Get the main image for the project
                $mainImageQuery = "SELECT * FROM mainimages WHERE project_id = " . intval($project['project_id']);
                $mainImageResult = $connection->query($mainImageQuery);
                
                // Check if the main image query was successful
                if ($mainImageResult) {
                    $mainImage = $mainImageResult->fetch_assoc();
                } else {
                    $mainImage = null; // If there is no main image
                }
                ?>

                <?php if ($mainImage): ?>
                    <img src="<?php echo htmlspecialchars($mainImage['image_path']); ?>" alt="<?php echo htmlspecialchars($mainImage['image_title']); ?>" class="w-full h-48 object-cover">

                <?php else: ?>
                    <img src="uploads/main/default.jpg" alt="Default image" class="w-full h-48 object-cover"> <!-- Default image -->
                <?php endif; ?>
                <h3 class="text-xl font-semibold"><?= htmlspecialchars($project['project_name']) ?></h3>
                <p><?= htmlspecialchars($project['project_description']) ?></p>
                <small>Location: <?= htmlspecialchars($project['project_location']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
