<?php
define('ACCESS_ALLOWED', true); // Define this to allow access to included files
// Include the connection.php file for database connection
include 'connection.php';
require_once 'includes/auth_functions.php'; // Include authentication functions

// Authenticate and authorize admin access
requireRole('admin');

// Function to generate a unique slug
function generateSlug($string, $connection) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    $original_slug = $slug;
    $count = 1;
    
    // Check for uniqueness
    while (true) {
        $stmt = $connection->prepare("SELECT COUNT(*) FROM projects WHERE project_slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $stmt->bind_result($num_rows);
        $stmt->fetch();
        $stmt->close();
        
        if ($num_rows == 0) {
            break; // Slug is unique
        }
        
        $slug = $original_slug . '-' . $count;
        $count++;
    }
    return $slug;
}

// Function to fetch ENUM values from a column
function getEnumValues($connection, $table, $column) {
    error_log("Attempting to get ENUM values for table: {$table}, column: {$column}");
    $query = "SHOW COLUMNS FROM " . $table . " LIKE '" . $column . "'";
    $result = $connection->query($query);
    if (!$result) {
        error_log("Query to get ENUM type failed: " . $connection->error);
        return [];
    }
    $row = $result->fetch_assoc();
    if ($row && isset($row['Type'])) {
        error_log("Raw ENUM type string for {$column}: " . $row['Type']);
        preg_match("/enum\('(.*)'\)/", $row['Type'], $matches);
        if (isset($matches[1])) {
            $enum_values = explode("','", $matches[1]);
            error_log("Extracted ENUM values for {$column}: " . implode(', ', $enum_values));
            return $enum_values;
        } else {
            error_log("Failed to parse ENUM type string for {$column}: " . $row['Type']);
        }
    } else {
        error_log("Column {$column} not found or has no type information in SHOW COLUMNS result.");
    }
    return [];
}

$projectTypes = getEnumValues($connection, 'projects', 'project_type');
$projectStatuses = getEnumValues($connection, 'projects', 'project_status');

// Initialize error array
$errors = [];
$message = '';
$messageType = ''; // 'success' or 'error'

// Check for messages from redirects (e.g., from delete_project.php)
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $messageType = htmlspecialchars($_GET['type'] ?? 'error'); // Default to error
}

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
            $projectSlug = generateSlug($projectName, $connection); // Generate slug

            // Prepare and execute the query
            $stmt = $connection->prepare('INSERT INTO projects (project_name, project_slug, project_description, project_location, project_date, project_type) VALUES (?, ?, ?, ?, ?, ?)');
            
            // Create fresh variables for bind_param to ensure they are passed by reference
            $bp_projectName = $projectName;
            $bp_projectSlug = $projectSlug;
            $bp_projectDescription = $projectDescription;
            $bp_projectLocation = $projectLocation;
            $bp_projectDate = $projectDate;
            $bp_projectType = $projectType; 

            $stmt->bind_param("ssssss", $bp_projectName, $bp_projectSlug, $bp_projectDescription, $bp_projectLocation, $bp_projectDate, $bp_projectType);
            $stmt->execute();

            // Get the last inserted project ID
            $projectId = $connection->insert_id;

            // Handle Main Image Upload
            if (!empty($_FILES['main_image']['name'])) {
                $mainImageTitle = $_POST['main_image_title'] ?? '';
                $mainImagePath = 'uploads/main/' . basename($_FILES['main_image']['name']);
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $mainImagePath)) {
                    $stmt = $connection->prepare('INSERT INTO mainimages (project_id, image_title, image_path) VALUES (?, ?, ?)');
                    $stmt->bind_param("iss", $projectId, $mainImageTitle, $mainImagePath);
                    $stmt->execute();
                } else {
                    $errors[] = "Failed to upload main image. Error: " . $_FILES['main_image']['error'];
                }
            }

            // Handle Carousel Images Upload
            if (isset($_FILES['carousel_images']) && is_array($_FILES['carousel_images']['name'])) {
                foreach ($_FILES['carousel_images']['name'] as $key => $name) {
                    if (!empty($name)) {
                        $carouselImageTitle = $_POST['carousel_titles'][$key] ?? '';
                        $carouselImageDescription = $_POST['carousel_descriptions'][$key] ?? '';
                        $carouselImagePath = 'uploads/carousel/' . basename($name);
                        if (move_uploaded_file($_FILES['carousel_images']['tmp_name'][$key], $carouselImagePath)) {
                            $stmt = $connection->prepare('INSERT INTO carouselimages (project_id, image_title, image_description, image_path, display_order) VALUES (?, ?, ?, ?, ?)');
                            
                            // Create fresh variables for bind_param to ensure they are passed by reference
                            $bp_projectId = $projectId;
                            $bp_carouselImageTitle = $carouselImageTitle;
                            $bp_carouselImageDescription = $carouselImageDescription;
                            $bp_carouselImagePath = $carouselImagePath;
                            $bp_displayOrder = $key + 1; // Assign expression to a variable

                            $stmt->bind_param("isssi", $bp_projectId, $bp_carouselImageTitle, $bp_carouselImageDescription, $bp_carouselImagePath, $bp_displayOrder);
                            $stmt->execute();
                        } else {
                            $errors[] = "Failed to upload carousel image: $name. Error: " . $_FILES['carousel_images']['error'][$key];
                        }
                    }
                }
            }

            // Redirect to avoid form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;

        } catch (mysqli_sql_exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Retrieve filter parameters from GET request
$searchTerm = $_GET['search_term'] ?? '';
$filterType = $_GET['filter_type'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';

// Build the WHERE clause dynamically
$whereClauses = [];
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $whereClauses[] = "(project_name LIKE ? OR project_description LIKE ? OR project_location LIKE ?)";
    $params[] = '%' . $searchTerm . '%';
    $params[] = '%' . $searchTerm . '%';
    $params[] = '%' . $searchTerm . '%';
    $types .= 'sss';
}

if (!empty($filterType)) {
    $whereClauses[] = "project_type = ?";
    $params[] = $filterType;
    $types .= 's';
}

if (!empty($filterStatus)) {
    $whereClauses[] = "project_status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

$sql = "SELECT * FROM projects";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY project_date DESC"; // Default sorting

// Fetch all projects
try {
    $stmt = $connection->prepare($sql);

    if ($stmt === false) {
        throw new mysqli_sql_exception("Failed to prepare statement: " . $connection->error);
    }

    if (!empty($params)) {
        // Use call_user_func_array to bind parameters dynamically
        $bindParams = [];
        $bindParams[] = &$types; // First element is the type string
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[] = &$params[$i]; // Pass parameters by reference
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $projects = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    $errors[] = "Database fetch error: " . $e->getMessage();
    $projects = []; // Ensure projects is an empty array on error
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

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?> mb-4">
                <?= $message ?>
            </div>
        <?php endif; ?>

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

            <button type="submit" class="bg-gray-700 text-white py-2 px-4 rounded transition duration-300 ease-in-out">Submit</button>
        </form>
    </div>

    <!-- Search and Filter Section -->
    <div class="bg-white p-6 rounded shadow-md mb-6">
        <h2 class="text-2xl text-center mb-4">Search and Filter Projects</h2>
        <form action="" method="GET" class="space-y-4">
            <div class="form-control">
                <label class="label" for="search_term"><span class="label-text">Search Projects</span></label>
                <input type="text" name="search_term" id="search_term" placeholder="Search by name, description, or location..." class="input input-bordered w-full" value="<?= htmlspecialchars($_GET['search_term'] ?? '') ?>">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label" for="filter_type"><span class="label-text">Project Type</span></label>
                    <select name="filter_type" id="filter_type" class="select select-bordered w-full">
                        <option value="">All Types</option>
                        <?php foreach ($projectTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= ((($_GET['filter_type'] ?? '') == $type) ? 'selected' : '') ?>>
                                <?= htmlspecialchars(ucfirst($type)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label" for="filter_status"><span class="label-text">Project Status</span></label>
                    <select name="filter_status" id="filter_status" class="select select-bordered w-full">
                        <option value="">All Statuses</option>
                        <?php foreach ($projectStatuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>" <?= ((($_GET['filter_status'] ?? '') == $status) ? 'selected' : '') ?>>
                                <?= htmlspecialchars(ucfirst($status)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-full">Apply Filters</button>
            <a href="projects.php" class="btn btn-ghost w-full">Clear Filters</a>
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
                <p class="text-sm text-gray-600 mb-2"><strong>Slug:</strong> <?= htmlspecialchars($project['project_slug']) ?></p>
                <p class="text-sm text-gray-600 mb-2"><strong>Location:</strong> <?= htmlspecialchars($project['project_location']) ?></p>
                <p class="text-sm text-gray-600 mb-2"><strong>Type:</strong> <?= htmlspecialchars(ucfirst($project['project_type'])) ?></p>
                <p class="text-sm text-gray-600 mb-2"><strong>Status:</strong> <span class="badge <?= $project['project_status'] == 'published' ? 'badge-success' : 'badge-warning' ?>"><?= htmlspecialchars(ucfirst($project['project_status'])) ?></span></p>
                <p class="text-sm text-gray-600 mb-4"><strong>Date:</strong> <?= htmlspecialchars($project['project_date']) ?></p>

                <div class="mt-4 flex gap-2">
                    <form action="delete_project.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this project and all its associated images? This action cannot be undone.');">
                        <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                        <button type="submit" class="btn btn-error btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
