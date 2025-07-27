<?php
include 'connection.php';

// Initialize variables
$image_id = $project_id = $image_title = $image_description = $image_data = $display_order = '';

// Handle Delete for Carousel Images
if (isset($_GET['delete_id'])) {
    $image_id = $_GET['delete_id'];
    $stmt = $pdo->prepare('DELETE FROM carouselimages WHERE image_id = ?');
    $stmt->execute([$image_id]);

    // Clear cache after deletion
    clearCache('carouselimages');

    // Redirect to avoid resubmission
    header('Location: edit_image.php');
    exit;
}

// Handle Delete for Main Images
if (isset($_GET['delete_main_id'])) {
    $image_id = $_GET['delete_main_id'];
    $stmt = $pdo->prepare('DELETE FROM mainimages WHERE image_id = ?');
    $stmt->execute([$image_id]);

    // Clear cache after deletion
    clearCache('mainimages');

    // Redirect to avoid resubmission
    header('Location: edit_image.php');
    exit;
}

// Handle Edit
if (isset($_GET['edit_id'])) {
    $image_id = $_GET['edit_id'];
    $stmt = $pdo->prepare('SELECT * FROM carouselimages WHERE image_id = ?');
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($image) {
        $project_id = $image['project_id'];
        $image_title = $image['image_title'];
        $image_description = $image['image_description'];
        $image_data = $image['image_data'];
        $display_order = $image['display_order'];
    }
}

// Handle Search
$search_term = $_GET['search_term'] ?? '';
$project_info = [];

// Fetch project details based on search
if ($search_term) {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE 
        project_id LIKE ? OR 
        project_name LIKE ? OR 
        project_description LIKE ? OR 
        project_location LIKE ? OR 
        project_date LIKE ? OR 
        project_type LIKE ? OR 
        created_at LIKE ?'
    );
    $like_search_term = '%' . $search_term . '%';
    $stmt->execute([
        $like_search_term,
        $like_search_term,
        $like_search_term,
        $like_search_term,
        $like_search_term,
        $like_search_term,
        $like_search_term
    ]);
    $project_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to clear cache data
define('CACHE_DIR', __DIR__ . '/cache');

function clearCache($key) {
    $cacheFile = CACHE_DIR . '/' . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        unlink($cacheFile); // Delete the cache file
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Images</title>
    <style>
        body, h1, h2, p, input, textarea, button, a {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            text-transform: uppercase; 
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            height: 100vh; 
            display: flex;
            text-align: center;
        }

        .container {
            width: 80%;
            max-width: 800px; /* Adjusted for better centering */
            margin: auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        header {
            padding-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }

        header h1 {
            font-size: 24px;
            color: #0d1b2a;
        }

        .button, a.button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            color: white;
            background-color: #0d1b2a; /* Updated to match the form theme */
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .button:hover, a.button:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #0d1b2a;
            backdrop-filter: blur(10px);
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center; /* Center items horizontally */
        }

        form label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        form input, form textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        form textarea {
            resize: vertical;
        }

        form button {
            margin: 20px;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            color: white;
            background-color: #0d1b2a; /* Updated to match the form theme */
            transition: background-color 0.3s ease;
        }

        form button:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #0d1b2a;
            backdrop-filter: blur(10px);
        }

        span {
            color: red;
        }

        .image-item img {
            max-width: 100%;
            height: auto;
        }

        .delete-link {
            color: red;
            text-decoration: none;
            font-weight: bold;
        }

        .delete-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Images</h1>

        <h2>Search Projects</h2>
        <form method="GET">
            <label for="search_term">Search Term:</label>
            <input type="text" id="search_term" name="search_term" placeholder="Search projects using id or any key words..." value="<?= htmlspecialchars($search_term) ?>">
            <input type="submit" value="Search">
            <a href="projects.php">Back to Projects</a>
        </form>

        <?php if ($search_term): ?>
            <?php if ($project_info): ?>
                <h2>Project Information</h2>
                <?php foreach ($project_info as $project): ?>
                    <div class="project-info">
                        <hr>
                        <h3><?= htmlspecialchars($project['project_name']) ?></h3>
                        <p><strong>Project Id:</strong> <?= htmlspecialchars($project['project_id']) ?></p>
                        <p><strong>Description:</strong> <?= htmlspecialchars($project['project_description']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($project['project_location']) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars($project['project_date']) ?></p>
                        <p><strong>Type:</strong> <?= htmlspecialchars($project['project_type']) ?></p>
                        <p><strong>Created At:</strong> <?= htmlspecialchars($project['created_at']) ?></p>
                        <hr>

                        <!-- Main Image -->
                        <?php
                        $stmt = $pdo->prepare('SELECT * FROM mainimages WHERE project_id = ?');
                        $stmt->execute([$project['project_id']]);
                        $main_image = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <h3>Main Image</h3>
                        <?php if ($main_image): ?>
                            <div class="image-info">
                                <h4><?= htmlspecialchars($main_image['image_title']) ?></h4>
                                <p><?= htmlspecialchars($main_image['image_description']) ?></p>
                                <a href="?delete_main_id=<?= htmlspecialchars($main_image['image_id']) ?>" class="delete-link" onclick="return confirm('Are you sure?')">Delete Main Image</a>
                            </div>
                        <?php else: ?>
                            <p>No main image found for this project.</p>
                        <?php endif; ?>

                        <!-- Carousel Images -->
                        <?php
                        $stmt = $pdo->prepare('SELECT * FROM carouselimages WHERE project_id = ? ORDER BY display_order');
                        $stmt->execute([$project['project_id']]);
                        $carousel_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <h3>Carousel Images</h3>
                        <?php if ($carousel_images): ?>
                            <div class="carousel-images">
                                <?php foreach ($carousel_images as $image): ?>
                                    <div class="image-item">
                                        <h4><?= htmlspecialchars($image['image_title']) ?></h4>
                                        <p><?= htmlspecialchars($image['image_description']) ?></p>
                                        <a href="?delete_id=<?= htmlspecialchars($image['image_id']) ?>" class="delete-link" onclick="return confirm('Are you sure?')">Delete Carousel Image</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No carousel images found for this project.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No projects found matching your search.</p>
            <?php endif; ?>
        <?php endif; ?>

        <h2>Edit Carousel Image</h2>
        <form method="POST" action="edit_image_process.php">
            <input type="hidden" name="image_id" value="<?= htmlspecialchars($image_id) ?>">
            <label for="project_id">Project ID:</label>
            <input type="text" id="project_id" name="project_id" required value="<?= htmlspecialchars($project_id) ?>">

            <label for="image_title">Image Title:</label>
            <input type="text" id="image_title" name="image_title" required value="<?= htmlspecialchars($image_title) ?>">

            <label for="image_description">Image Description:</label>
            <textarea id="image_description" name="image_description" required><?= htmlspecialchars($image_description) ?></textarea>

            <label for="display_order">Display Order:</label>
            <input type="number" id="display_order" name="display_order" required value="<?= htmlspecialchars($display_order) ?>">

            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>
