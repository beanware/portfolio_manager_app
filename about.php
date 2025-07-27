<?php
include_once 'connection.php'; // Include the database connection file
include_once 'navbar.php';

// Ensure $pdo is correctly initialized
if (!$pdo) {
    die('Database connection failed.');
}

// Fetch the resume data
$query = $pdo->prepare("SELECT * FROM resume WHERE id = :id");
$query->execute(['id' => 1]);
$resume = $query->fetch(PDO::FETCH_ASSOC);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
    <div class=" max-w-4xl mx-auto py-20 px-20">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold">My Profile</h1>
            <?php if ($isLoggedIn): ?>
                <a href="edit-about.php" class="px-6 py-2 bg-primary text-white rounded-lg shadow-md hover:bg-blue-600 transition">Edit Resume</a>
            <?php endif; ?>
        </header>

        <section id="resumeDisplay" class="bg-gray-800 p-6 rounded-lg shadow-md">
            <h2 class="text-2xl text-center font-semibold mb-4">About Me</h2>
            <?php if ($resume): ?>
                <p class="mb-2"><strong class="font-bold">Name:</strong> <?php echo htmlspecialchars($resume['name']); ?></p>
                <p class="mb-2"><strong class="font-bold">Bio:</strong> <?php echo nl2br(htmlspecialchars($resume['bio'])); ?></p>
                <p class="mb-2"><strong class="font-bold">Education:</strong> <?php echo htmlspecialchars($resume['education']); ?></p>
                <p class="mb-2"><strong class="font-bold">Experience:</strong> <?php echo htmlspecialchars($resume['experience']); ?></p>
            <?php else: ?>
                <p class="text-red-500">No resume data found. Please log in to add your resume .</p>
            <?php endif; ?>
        </section>
    </div>
<?php include 'footer.php'; ?>