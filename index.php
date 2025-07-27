
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to My Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100">
<?php include 'navbar.php'; ?>
<div class="relative flex items-center justify-center h-screen bg-cover bg-center" style="background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('./img/arch1.jpg'); backdrop-filter: blur(10px);">
    <div class="absolute z-10 max-w-lg p-8 text-center text-white">
        <h1 style="font-family: 'Comic Sans MS', cursive; color: white;" class="text-4xl md:text-5xl font-bold">Welcome to My Portfolio</h1>
        <p class="mt-6 text-lg md:text-xl">I design and build beautiful spaces.</p>
        <a href="gallery.php" class="mt-8 inline-block px-6 py-3 text-gray-800 bg-gray-300 rounded font-bold hover:bg-gray-400 transition duration-200 ease-in-out">
            View My Work
        </a>
    </div>
</div>



<?php include 'footer.php'; ?>
</body>
</html>
