<?php
require_once 'connection.php'; // Include the database connection file
include_once 'navbar.php';

// Enable error reporting (development only)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure $pdo is correctly initialized
if (!$pdo) {
    die('Database connection failed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize user input
    $name = trim($_POST['name']);
    $bio = trim($_POST['bio']);
    $education = trim($_POST['education']);
    $experience = trim($_POST['experience']);

    // Validate inputs
    if (empty($name) || empty($bio) || empty($education) || empty($experience)) {
        $error = "All fields are required.";
    } else {
        try {
            // Prepare and execute update statement only
            $stmt = $pdo->prepare("UPDATE resume SET name = ?, bio = ?, education = ?, experience = ? WHERE id = 1");
            $success = $stmt->execute([$name, $bio, $education, $experience]);

            // Check if any rows were actually updated
            if ($success && $stmt->rowCount() > 0) {
                // Redirect to about page after successful update
                header('Location: about.php');
                exit();
            } else {
                $error = "Failed to update resume. Please ensure the record exists and try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch the resume data to pre-fill the form
try {
    $query = $pdo->prepare("SELECT * FROM resume WHERE id = :id");
    $query->execute(['id' => 1]);
    $resume = $query->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error fetching resume data: ' . $e->getMessage());
}

// If no record exists, you may choose to initialize default values or handle the error as needed.
if (!$resume) {
    $resume = [
        'name' => '',
        'bio' => '',
        'education' => '',
        'experience' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resume</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-roboto">
    <div class="max-w-2xl mx-auto my-10 p-6 bg-white rounded shadow-md">
        <header class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Edit Resume</h1>
        </header>

        <?php if (isset($error)): ?>
            <div class="mb-4 p-4 text-red-700 bg-red-100 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <section id="resumeForm">
            <form method="POST" action="edit-about.php" class="space-y-4">
                <div>
                    <label for="name" class="block text-gray-700">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($resume['name']); ?>" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>

                <div>
                    <label for="bio" class="block text-gray-700">Bio:</label>
                    <textarea id="bio" name="bio" rows="4" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-600"><?php echo htmlspecialchars($resume['bio']); ?></textarea>
                </div>

                <div>
                    <label for="education" class="block text-gray-700">Education:</label>
                    <input type="text" id="education" name="education" value="<?php echo htmlspecialchars($resume['education']); ?>" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>

                <div>
                    <label for="experience" class="block text-gray-700">Experience:</label>
                    <input type="text" id="experience" name="experience" value="<?php echo htmlspecialchars($resume['experience']); ?>" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>

                <div class="flex space-x-4">
                    <button type="submit" class="px-6 py-2 text-white bg-blue-600 rounded hover:bg-blue-700 transition duration-300">Save Changes</button>
                    <a href="about.php" class="px-6 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300 transition duration-300">Cancel</a>
                </div>
            </form>
        </section>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
