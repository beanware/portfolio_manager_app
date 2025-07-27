<?php
session_start(); // Start the session

include 'session.php';
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- For icons -->
    <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.tailwindcss.com"></script>

</head>
<body class="bg-gray-100 font-roboto">
    
    
    <!---->
    <div class="navbar bg-gray-900 shadow-sm">
  <div class="navbar-start">
    <div class="dropdown">
      <div tabindex="0" role="button" class="btn btn-ghost text-gray-400 lg:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" /> </svg>
      </div>
      <ul
        tabindex="0"
        class="menu menu-sm dropdown-content bg-base-100 rounded-box z-1 mt-3 w-52 p-2 shadow">
        <li><a href="index.php">Home</a></li>
      <li><a href="gallery.php">Project Gallery</a></li>

                <?php if (!$isLoggedIn): ?>
                    <!--<a href="contact.php" class="text-gray-400 hover:text-white">Contact</a>-->
                    <!--    <a href="create-admin.php" class="text-red-300 hover:text-red-600">-->
                    <!--      <i class="fas fa-cog"></i>-->
                    <!--    </a>-->

                <?php else: ?>
                    <a href="logout.php" class="text-red-300 hover:text-red-600">Logout</a>
                <?php endif; ?>
          </ul>
        </li>
      </ul>
    </div>
    <span style="font-family: 'Comic Sans MS', cursive; color: white; font-size: 1.125rem; font-weight: bold;">Mikaye's Gallery</span>
  </div>
  <div class="navbar-center hidden lg:flex">
    <ul class="menu menu-horizontal px-1 font-bold text-white">
      <li><a href="index.php">Home</a></li>
      <li><a href="gallery.php">Project Gallery</a></li>
    </ul>
  </div>
  <div class="navbar-end">
    <?php if($isLoggedIn): ?>
                    <a href="logout.php" class="text-red-300 hover:text-red-600">Logout</a>
                <?php endif; ?>
  </div>
</div>
    <!---->

    <!--<div id="mobile-menu" class="md:hidden hidden">-->
    <!--    <div class="flex flex-col space-y-2 p-4">-->
    <!--        <a href="index.php" class="text-gray-400 hover:text-white">Home</a>-->
    <!--        <a href="gallery.php" class="text-gray-400 hover:text-white">Project Gallery</a>-->

    <!--        <?php if ($isLoggedIn): ?>-->
    <!--            <a href="projects.php" class="text-gray-400 hover:text-white">Manage Projects</a>-->
    <!--        <?php endif; ?>-->

    <!--        <a href="about.php" class="text-gray-400 hover:text-white">About</a>-->

    <!--        <?php if (!$isLoggedIn): ?>-->
                <!--<a href="contact.php" class="text-gray-400 hover:text-white">Contact</a>-->
    <!--        <?php else: ?>-->
    <!--            <a href="logout.php" class="text-red-300 hover:text-red-600">Logout</a>-->
    <!--        <?php endif; ?>-->
    <!--    </div>-->
    <!--</div>-->

    <script>
        // Toggle mobile menu
        const menuToggle = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');

        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>

</body>
</html>
