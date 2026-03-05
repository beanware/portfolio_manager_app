<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'session.php';
require_once 'includes/auth_functions.php'; // Explicitly include for getCurrentUser and role checks

$isLoggedIn = isAuthenticated();
$currentUser = getCurrentUser(); // Get current user details

$hasOrg = ($currentUser && !empty($currentUser['organization_id']));

// Navigation links defined ONCE in a PHP array for single source of truth.
$navLinks = [
    ['title' => 'Home', 'url' => 'index.php']
];

if ($isLoggedIn && $hasOrg) {
    $navLinks[] = ['title' => 'Project Gallery', 'url' => 'gallery.php'];
    $navLinks[] = ['title' => 'Marketplace', 'url' => 'marketplace.php'];
    $navLinks[] = ['title' => 'Manage Projects', 'url' => 'projects.php'];

    // Conditionally add dashboard links based on roles
    if ($currentUser && hasAnyRole(['super_admin'], $currentUser)) {
        $navLinks[] = ['title' => 'Overall Admin', 'url' => 'admin_dashboard.php'];
    } elseif ($currentUser && hasAnyRole(['admin'], $currentUser)) {
        $navLinks[] = ['title' => 'Org Admin Dashboard', 'url' => 'organization_admin_dashboard.php'];
    }
}

if ($isLoggedIn && !$hasOrg) {
    $navLinks[] = ['title' => 'Setup Organization', 'url' => 'create_organization.php', 'class' => 'text-primary font-bold'];
}

if (!$isLoggedIn) {
    $navLinks[] = ['title' => 'Login', 'url' => 'login.php'];
    $navLinks[] = ['title' => 'Register', 'url' => 'register.php', 'class' => 'btn btn-primary btn-sm text-white hover:text-white'];
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
                <?php if ($isLoggedIn && hasAnyRole(['super_admin', 'admin'], $currentUser)): ?>
                    <?php
                        // Check for pending leads and new enquiries
                        $pendingLeadsCount = 0;
                        $newEnquiriesCount = 0;
                        try {
                            // Leads
                            $leadSql = "SELECT COUNT(*) FROM leads WHERE status = 'pending'";
                            if (!hasAnyRole(['super_admin'], $currentUser)) {
                                $leadSql .= " AND organization_id = " . intval($currentUser['organization_id']);
                            }
                            $leadResult = $connection->query($leadSql);
                            if ($leadResult) {
                                $pendingLeadsCount = $leadResult->fetch_row()[0];
                            }

                            // Enquiries
                            $enqSql = "SELECT COUNT(*) FROM enquiries WHERE status = 'new'";
                            if (!hasAnyRole(['super_admin'], $currentUser)) {
                                $enqSql .= " AND organization_id = " . intval($currentUser['organization_id']);
                            }
                            $enqResult = $connection->query($enqSql);
                            if ($enqResult) {
                                $newEnquiriesCount = $enqResult->fetch_row()[0];
                            }
                        } catch (Exception $e) {
                            error_log("Notification count error: " . $e->getMessage());
                        }
                    ?>
                    <div class="flex items-center gap-1">
                        <a href="manage_leads.php" class="btn btn-ghost btn-circle relative tooltip tooltip-bottom" data-tip="Bounty Leads">
                            <i class="fas fa-coins text-xl"></i>
                            <?php if ($pendingLeadsCount > 0): ?>
                                <span class="badge badge-secondary badge-xs absolute top-2 right-2 p-1.5 animate-pulse"><?= $pendingLeadsCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="manage_enquiries.php" class="btn btn-ghost btn-circle relative tooltip tooltip-bottom" data-tip="Property Enquiries">
                            <i class="fas fa-envelope text-xl"></i>
                            <?php if ($newEnquiriesCount > 0): ?>
                                <span class="badge badge-primary badge-xs absolute top-2 right-2 p-1.5 animate-pulse"><?= $newEnquiriesCount ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endif; ?>

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