<?php
define('ACCESS_ALLOWED', true); // Define this to allow access to included files
include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to <?php echo htmlspecialchars($companyName ?? "Property Portfolio"); ?></title>
    <!-- NO EXTRA TAILWIND LINK HERE. IT'S IN HEADER.PHP -->
</head>
<!-- Body uses theme's base-100 for background, ensuring consistency -->
<body class="bg-base-100">

<!-- Hero Section: Uses theme colors and DaisyUI components -->
<div class="hero min-h-screen" style="background-image: url('./img/arch1.jpg');">
    <!-- DaisyUI's `hero-overlay` class for the gradient/darkening -->
    <div class="hero-overlay bg-opacity-60"></div>
    <div class="hero-content text-center text-neutral-content">
        <div class="max-w-lg">
            <!-- Using theme's size and weight scales -->
            <h1 class="text-5xl font-bold">Welcome to <?php echo htmlspecialchars($companyName ?? "Property Portfolio"); ?></h1>
            <p class="py-6 text-xl">Discover exceptional properties and visionary real estate solutions.</p>
            <!-- DaisyUI button component, using theme's primary color -->
            <a href="gallery.php" class="mt-8 inline-block px-6 py-3 text-gray-800 bg-gray-300 rounded hover:bg-gray-400 transition duration-200 ease-in-out">
            View our Portfolio
        </a>
        <a href="marketplace.php" class="mt-8 ml-4 inline-block px-6 py-3 text-white bg-blue-600 rounded hover:bg-blue-700 transition duration-200 ease-in-out">
            Explore Organizations
        </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>