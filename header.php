<?php
session_start();
include 'session.php';
require_once 'includes/auth_functions.php'; // Explicitly include for getCurrentUser and role checks

$isLoggedIn = isAuthenticated();
$currentUser = getCurrentUser(); // Get current user details

// Navigation links defined ONCE in a PHP array for single source of truth.
// This is a crucial step towards a configurable system.
$navLinks = [
    ['title' => 'Home', 'url' => 'index.php'],
    ['title' => 'Project Gallery', 'url' => 'gallery.php'],
    ['title' => 'Marketplace', 'url' => 'marketplace.php'] // Added marketplace link
];

if ($isLoggedIn) {
    $navLinks[] = ['title' => 'Manage Projects', 'url' => 'projects.php'];

    // Conditionally add dashboard links based on roles
    if ($currentUser && hasAnyRole(['super_admin'], $currentUser)) {
        $navLinks[] = ['title' => 'Overall Admin', 'url' => 'admin_dashboard.php'];
    } elseif ($currentUser && hasAnyRole(['admin'], $currentUser)) {
        // Assuming 'admin' is the role for organization admins
        $navLinks[] = ['title' => 'Org Admin Dashboard', 'url' => 'organization_admin_dashboard.php'];
    }
}
$navLinks[] = ['title' => 'About', 'url' => 'about.php'];
if ($isLoggedIn) {
    $navLinks[] = ['title' => 'Logout', 'url' => 'logout.php', 'class' => 'text-accent'];
}

$companyName = "Property Portfolio"; // Move this to a config file later
?>
<!DOCTYPE html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($companyName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DaisyUI includes Tailwind (load only this one for simplicity) -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.23/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            plugins: [require('daisyui')],
            daisyui: {
                themes: [{
                    corporate: {
                        "primary": "#4c7cf3",
                        "primary-content": "#ffffff",
                        "secondary": "#7266c5",
                        "secondary-content": "#ffffff",
                        "accent": "#3abff8",
                        "accent-content": "#ffffff",
                        "neutral": "#191d24",
                        "neutral-content": "#ffffff",
                        "base-100": "#ffffff",
                        "base-200": "#f3f4f6",
                        "base-300": "#d1d5db",
                        "base-content": "#111827",
                        "info": "#2094f3",
                        "success": "#009485",
                        "warning": "#ff9900",
                        "error": "#ff5724",
                    }
                }],
            }
        }
    </script>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="bg-neutral text-neutral-content shadow-lg sticky top-0 z-50">
        <div class="navbar max-w-7xl mx-auto px-4">
            <div class="navbar-start">
                <!-- Logo/Brand -->
                <a href="index.php" class="text-xl font-bold">
                    <?php echo htmlspecialchars($companyName); ?>
                </a>
            </div>

            <!-- Desktop Menu (Center) - hidden on mobile -->
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1 space-x-2">
                    <?php foreach ($navLinks as $link): ?>
                        <li>
                            <a href="<?php echo $link['url']; ?>"
                               class="<?php echo $link['class'] ?? ''; ?> hover:bg-base-100 hover:text-base-content rounded-md">
                                <?php echo htmlspecialchars($link['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="navbar-end">
                <!-- Mobile Menu Toggle Button -->
                <div class="dropdown dropdown-end lg:hidden">
                    <button tabindex="0" class="btn btn-ghost" aria-label="Navigation menu">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <!-- Mobile Dropdown Menu -->
                    <ul tabindex="0" class="dropdown-content menu p-4 shadow-lg bg-neutral text-neutral-content rounded-box w-56 mt-4">
                        <?php foreach ($navLinks as $link): ?>
                            <li>
                                <a href="<?php echo $link['url']; ?>"
                                   class="<?php echo $link['class'] ?? ''; ?> hover:bg-base-100 hover:text-base-content rounded-md">
                                    <?php echo htmlspecialchars($link['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        <!-- Page-specific content will be injected here by other files -->