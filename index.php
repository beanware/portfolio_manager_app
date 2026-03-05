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
    <div class="hero-overlay bg-opacity-70"></div>
    <div class="hero-content text-center text-neutral-content">
        <div class="max-w-2xl">
            <!-- Using theme's size and weight scales -->
            <h1 class="text-6xl font-black mb-4 leading-tight">The Ultimate Hub for Property Portfolios</h1>
            <p class="py-6 text-xl font-medium opacity-90">
                A unified marketplace for real estate agencies. <br>
                <strong>Manage listings. Showcase your brand. Close more deals.</strong>
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center mt-8">
                <a href="marketplace.php" class="btn btn-primary btn-lg rounded-xl px-10 shadow-xl">
                    <i class="fas fa-search mr-2"></i> Explore Marketplace
                </a>
                <?php if (!$isLoggedIn): ?>
                    <a href="register.php" class="btn btn-outline btn-neutral btn-lg rounded-xl px-10 border-2">
                        <i class="fas fa-building mr-2"></i> Join as an Agency
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8 opacity-80">
                <div class="flex flex-col items-center">
                    <div class="bg-primary/20 p-4 rounded-2xl mb-3">
                        <i class="fas fa-chart-line text-2xl text-primary"></i>
                    </div>
                    <span class="text-sm font-bold uppercase tracking-wider">Growth</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="bg-secondary/20 p-4 rounded-2xl mb-3">
                        <i class="fas fa-shield-alt text-2xl text-secondary"></i>
                    </div>
                    <span class="text-sm font-bold uppercase tracking-wider">Trusted</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="bg-accent/20 p-4 rounded-2xl mb-3">
                        <i class="fas fa-bolt text-2xl text-accent"></i>
                    </div>
                    <span class="text-sm font-bold uppercase tracking-wider">Efficiency</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>