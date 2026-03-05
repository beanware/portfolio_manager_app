<?php
define('ACCESS_ALLOWED', true);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'connection.php';

// Validate and sanitize input
$projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Early validation
if ($projectId <= 0) {
    // Redirect or show error
    safeRedirect('gallery.php');
    exit();
}

include 'header.php';

// Single query for project + main image + organization info
$sql = "SELECT p.*, mi.image_path as main_image_path, o.organization_name
        FROM projects p 
        LEFT JOIN mainimages mi ON p.project_id = mi.project_id
        LEFT JOIN organizations o ON p.organization_id = o.organization_id
        WHERE p.project_id = ?";
$stmt = $connection->prepare($sql);

if (!$stmt) {
    die("Database error: " . $connection->error);
}

$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

// --- Visit Tracking Logic ---
if ($project) {
    // Prevent double counting within same session for same project
    if (!isset($_SESSION['viewed_projects'])) {
        $_SESSION['viewed_projects'] = [];
    }

    if (!in_array($projectId, $_SESSION['viewed_projects'])) {
        // 1. Increment total view count in projects table
        $connection->query("UPDATE projects SET views = views + 1 WHERE project_id = " . $projectId);
        
        // 2. Record detailed visit (with basic security)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $visitStmt = $connection->prepare("INSERT INTO project_visits (project_id, ip_address, user_agent) VALUES (?, ?, ?)");
        $visitStmt->bind_param("iss", $projectId, $ip, $ua);
        $visitStmt->execute();
        $visitStmt->close();
        
        // Mark as viewed in session
        $_SESSION['viewed_projects'][] = $projectId;
    }
}

// Separate query for carousel images
$carouselImages = [];
if ($project) {
    $carouselSql = "SELECT image_path, image_title 
                    FROM carouselimages 
                    WHERE project_id = ? 
                    ORDER BY display_order";
    $carouselStmt = $connection->prepare($carouselSql);
    $carouselStmt->bind_param("i", $projectId);
    $carouselStmt->execute();
    $carouselResult = $carouselStmt->get_result();
    $carouselImages = $carouselResult->fetch_all(MYSQLI_ASSOC);
    $carouselStmt->close();
}

$stmt->close();
$connection->close();

// Helper function to safely output data
function safeOutput($data, $default = 'Not specified') {
    if (is_null($data) || trim($data) === '') {
        return htmlspecialchars($default);
    }
    return htmlspecialchars($data);
}

// Helper function for date formatting
function formatDate($dateString, $format = 'F Y') {
    if (empty($dateString) || $dateString === '0000-00-00') {
        return 'Date not specified';
    }
    try {
        $date = new DateTime($dateString);
        return $date->format($format);
    } catch (Exception $e) {
        return 'Invalid date';
    }
}
?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Breadcrumbs & Navigation -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="text-sm breadcrumbs">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="marketplace.php">Marketplace</a></li>
                    <?php if ($project && !empty($project['organization_name'])): ?>
                        <li>
                            <a href="organization_details.php?org_id=<?php echo $project['organization_id']; ?>">
                                <?php echo htmlspecialchars($project['organization_name']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="font-bold text-primary"><?php echo safeOutput($project['project_name'], 'Project Details'); ?></li>
                </ul>
            </div>
            <a href="gallery.php" class="btn btn-ghost btn-sm gap-2">
                <i class="fas fa-arrow-left"></i> Back to All Projects
            </a>
        </div>

        <?php if (!$project): ?>
            <div class="alert alert-error shadow-lg max-w-2xl mx-auto">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
                <div>
                    <h3 class="font-bold">Project Not Found</h3>
                    <div class="text-xs">The project may have been removed or the link is incorrect.</div>
                </div>
                <div class="flex-none">
                    <a href="gallery.php" class="btn btn-sm">Go to Gallery</a>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Project Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Media & Description (66%) -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Title & Header -->
                    <div class="bg-base-100 p-8 rounded-3xl shadow-sm border border-base-300">
                        <div class="flex justify-between items-start flex-wrap gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <h1 class="text-4xl font-black text-base-content leading-tight">
                                        <?php echo safeOutput($project['project_name'], 'Unnamed Project'); ?>
                                    </h1>
                                    <?php if (!empty($project['project_type'])): ?>
                                        <div class="badge badge-secondary badge-lg"><?php echo htmlspecialchars($project['project_type']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-lg text-base-content/60 flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
                                    <?php echo safeOutput($project['project_location'], 'Location not provided'); ?>
                                </p>
                            </div>
                            <?php if (!empty($project['price'])): ?>
                                <div class="bg-primary/10 px-6 py-3 rounded-2xl border border-primary/20">
                                    <span class="text-3xl font-black text-primary">
                                        <?php echo htmlspecialchars($project['price']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Main Image -->
                    <?php if (!empty($project['main_image_path'])): ?>
                    <div class="group relative rounded-3xl overflow-hidden shadow-2xl cursor-zoom-in"
                            onclick="document.getElementById('modal-main').showModal()">
                        <img src="<?php echo htmlspecialchars($project['main_image_path']); ?>" 
                             alt="<?php echo safeOutput($project['project_name']); ?>"
                             class="w-full h-auto max-h-[70vh] object-cover transition-transform duration-700 group-hover:scale-105"
                             loading="eager">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="absolute bottom-6 right-6 bg-base-100/90 backdrop-blur shadow-xl rounded-full p-4">
                            <i class="fas fa-expand-alt text-xl"></i>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <div class="bg-base-100 p-8 rounded-3xl shadow-sm border border-base-300">
                        <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <i class="fas fa-info-circle text-primary"></i>
                            Project Overview
                        </h2>
                        <div class="text-base-content/80 leading-relaxed text-lg mb-8">
                            <?php if (!empty($project['project_description'])): ?>
                                <?php echo nl2br(safeOutput($project['project_description'])); ?>
                            <?php else: ?>
                                <p class="italic opacity-50">No description available for this project.</p>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <?php if (!empty($project['amenities'])): ?>
                            <div>
                                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                                    <i class="fas fa-list-ul text-primary"></i>
                                    Amenities
                                </h3>
                                <div class="text-base-content/70">
                                    <?php echo nl2br(safeOutput($project['amenities'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($project['perks'])): ?>
                            <div>
                                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                                    <i class="fas fa-star text-primary"></i>
                                    Perks & Features
                                </h3>
                                <div class="text-base-content/70">
                                    <?php echo nl2br(safeOutput($project['perks'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Gallery Section -->
                    <?php if (!empty($carouselImages)): ?>
                    <div class="bg-base-100 p-8 rounded-3xl shadow-sm border border-base-300">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold flex items-center gap-3">
                                <i class="fas fa-images text-primary"></i>
                                Photo Gallery
                            </h2>
                            <span class="badge badge-outline"><?php echo count($carouselImages); ?> Photos</span>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach ($carouselImages as $index => $image): ?>
                            <div class="relative group rounded-2xl overflow-hidden h-32 md:h-40 cursor-pointer shadow-md hover:shadow-xl transition-all"
                                 onclick="openImageModal(<?php echo $index; ?>)">
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="Gallery Image"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <i class="fas fa-plus text-white text-2xl"></i>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Sidebar (33%) -->
                <div class="space-y-8">
                    
                    <!-- Managed By Organization Card (CRUCIAL ADDITION) -->
                    <div class="card bg-neutral text-neutral-content shadow-xl overflow-hidden border border-neutral-focus">
                        <div class="card-body p-8">
                            <h3 class="text-xs uppercase tracking-widest opacity-60 font-bold mb-4">Presented By</h3>
                            <div class="flex items-center gap-5 mb-6">
                                <div class="avatar placeholder">
                                    <div class="bg-primary text-primary-content rounded-xl w-16 h-16 text-2xl font-bold shadow-lg">
                                        <?php echo strtoupper(substr($project['organization_name'] ?? 'P', 0, 1)); ?>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-2xl font-bold">
                                        <?php echo htmlspecialchars($project['organization_name'] ?? 'Independent Listing'); ?>
                                    </h4>
                                    <div class="badge badge-primary badge-sm mt-1">Verified Partner</div>
                                </div>
                            </div>
                            <div class="divider opacity-10"></div>
                            <div class="card-actions">
                                <a href="organization_details.php?org_id=<?php echo $project['organization_id']; ?>" 
                                   class="btn btn-primary btn-block rounded-xl">
                                    View Organization Profile
                                    <i class="fas fa-external-link-alt ml-2 text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Property Features List -->
                    <div class="bg-base-100 p-8 rounded-3xl shadow-sm border border-base-300">
                        <h3 class="text-xl font-bold mb-6">Property Details</h3>
                        <div class="space-y-4">
                            <?php 
                            $details = [
                                ['icon' => 'fa-tag', 'label' => 'Price', 'value' => $project['price'] ?? null],
                                ['icon' => 'fa-bed', 'label' => 'Bedrooms', 'value' => $project['bedrooms'] ?? null],
                                ['icon' => 'fa-bath', 'label' => 'Bathrooms', 'value' => $project['bathrooms'] ?? null],
                                ['icon' => 'fa-ruler-combined', 'label' => 'Square Footage', 'value' => !empty($project['square_footage']) ? $project['square_footage'] . ' sqft' : null],
                                ['icon' => 'fa-building', 'label' => 'Property Type', 'value' => $project['project_type'] ?? null],
                                ['icon' => 'fa-calendar', 'label' => 'Year Built/Listed', 'value' => formatDate($project['project_date'] ?? null)]
                            ];

                            foreach ($details as $detail):
                                if ($detail['value']): ?>
                                <div class="flex items-center justify-between p-3 bg-base-200 rounded-xl">
                                    <div class="flex items-center gap-3 text-base-content/70">
                                        <i class="fas <?php echo $detail['icon']; ?> w-5 text-primary"></i>
                                        <span class="text-sm font-medium"><?php echo $detail['label']; ?></span>
                                    </div>
                                    <span class="font-bold text-base-content"><?php echo htmlspecialchars($detail['value']); ?></span>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>

                    <!-- CTA / Action Card -->
                    <div class="card bg-primary text-primary-content shadow-2xl mb-6">
                        <div class="card-body p-8 text-center">
                            <h3 class="text-2xl font-black mb-2">Interested?</h3>
                            <p class="opacity-80 mb-6">Contact the organization directly to learn more about this property.</p>
                            <button onclick="document.getElementById('modal-enquiry').showModal()" class="btn btn-neutral btn-lg rounded-full w-full shadow-lg">
                                Send Inquiry
                                <i class="fas fa-paper-plane ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Bounty System Card -->
                    <div class="card bg-secondary text-secondary-content shadow-xl">
                        <div class="card-body p-8 text-center">
                            <div class="flex justify-center mb-4">
                                <div class="bg-white/20 p-4 rounded-full">
                                    <i class="fas fa-coins text-4xl"></i>
                                </div>
                            </div>
                            <h3 class="text-2xl font-black mb-2">Refer & Earn!</h3>
                            <div class="badge badge-outline badge-white mb-4 p-4 font-bold">
                                <i class="fas fa-gift mr-2"></i>
                                <?= htmlspecialchars($project['bounty_info'] ?: 'Rewards available') ?>
                            </div>
                            <p class="opacity-80 mb-6 text-sm">Know someone interested in this property? Provide their contact info and get rewarded if the deal closes!</p>
                            <button onclick="document.getElementById('modal-bounty').showModal()" class="btn btn-white btn-outline btn-lg rounded-full w-full shadow-lg hover:bg-white hover:text-secondary">
                                Submit a Lead
                                <i class="fas fa-hand-holding-usd ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals (Main Image) -->
            <dialog id="modal-main" class="modal">
                <div class="modal-box max-w-6xl p-0 bg-transparent shadow-none">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 z-10 bg-base-100/50 backdrop-blur">✕</button>
                    </form>
                    <img src="<?php echo htmlspecialchars($project['main_image_path']); ?>" class="w-full rounded-2xl">
                </div>
                <form method="dialog" class="modal-backdrop bg-black/80"><button>close</button></form>
            </dialog>

            <!-- Gallery Modal -->
            <dialog id="modal-gallery" class="modal">
                <div class="modal-box max-w-6xl p-0 bg-transparent shadow-none">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 z-10 bg-base-100/50 backdrop-blur">✕</button>
                    </form>
                    <div id="modal-gallery-content" class="carousel w-full">
                        <?php foreach ($carouselImages as $index => $image): ?>
                        <div id="gallery-item-<?php echo $index; ?>" class="carousel-item w-full justify-center">
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 class="max-h-[85vh] object-contain rounded-2xl">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($carouselImages) > 1): ?>
                    <div class="flex justify-center w-full py-6 gap-3 bg-black/40 backdrop-blur-md rounded-2xl mt-4">
                        <?php foreach ($carouselImages as $index => $image): ?>
                        <button onclick="showGalleryImage(<?php echo $index; ?>)" 
                                class="btn btn-sm md:btn-md btn-circle gallery-dot shadow-xl border-none transition-all duration-300 <?php echo $index === 0 ? 'btn-primary' : 'bg-white/20 text-white hover:bg-white/40'; ?>">
                            <?php echo $index + 1; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <form method="dialog" class="modal-backdrop bg-black/90"><button>close</button></form>
            </dialog>

            <!-- Bounty/Lead Modal -->
            <dialog id="modal-bounty" class="modal">
                <div class="modal-box max-w-2xl border-t-8 border-secondary">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="font-black text-3xl text-secondary">Referral Bounty</h3>
                            <p class="text-base-content/60">Help us find a buyer and get rewarded.</p>
                        </div>
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost">✕</button>
                        </form>
                    </div>
                    
                    <div id="bounty-status" class="mb-4"></div>

                    <form id="bounty-form" method="POST" class="space-y-6">
                        <input type="hidden" name="project_id" value="<?php echo safeOutput($projectId); ?>">
                        <input type="hidden" name="organization_id" value="<?php echo safeOutput($project['organization_id']); ?>">

                        <div class="bg-base-200 p-6 rounded-2xl border border-base-300">
                            <h4 class="font-bold mb-4 flex items-center gap-2">
                                <i class="fas fa-user-tag text-secondary"></i>
                                Your Information (Referrer)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-semibold">Full Name</span></label>
                                    <input type="text" name="referrer_name" placeholder="Your Name" class="input input-bordered w-full" required>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-semibold">Email Address</span></label>
                                    <input type="email" name="referrer_email" placeholder="your@email.com" class="input input-bordered w-full" required>
                                </div>
                                <div class="form-control md:col-span-2">
                                    <label class="label"><span class="label-text font-semibold">Phone Number</span></label>
                                    <input type="tel" name="referrer_phone" placeholder="e.g. +254..." class="input input-bordered w-full" required>
                                </div>
                            </div>
                        </div>

                        <div class="bg-base-200 p-6 rounded-2xl border border-base-300">
                            <h4 class="font-bold mb-4 flex items-center gap-2">
                                <i class="fas fa-user-check text-success"></i>
                                Interested Buyer Details
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-semibold">Buyer Name</span></label>
                                    <input type="text" name="buyer_name" placeholder="Lead's Full Name" class="input input-bordered w-full" required>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-semibold">Buyer Contact (Phone/Email)</span></label>
                                    <input type="text" name="buyer_contact" placeholder="Phone or Email" class="input input-bordered w-full" required>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning text-sm">
                            <i class="fas fa-info-circle"></i>
                            <span>You will be contacted if the deal closes successfully. Reward amounts are determined by the organization based on the deal value.</span>
                        </div>

                        <div class="modal-action">
                            <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal-bounty').close()">Cancel</button>
                            <button type="submit" id="bounty-submit-btn" class="btn btn-secondary px-8">
                                <i class="fas fa-paper-plane mr-2"></i>Submit Lead
                            </button>
                        </div>
                    </form>
                </div>
            </dialog>

            <script>
                // Bounty Form Handling
                document.addEventListener('DOMContentLoaded', () => {
                    const bountyForm = document.getElementById('bounty-form');
                    const bountySubmitBtn = document.getElementById('bounty-submit-btn');
                    const bountyStatus = document.getElementById('bounty-status');

                    if (bountyForm) {
                        bountyForm.addEventListener('submit', async (e) => {
                            e.preventDefault();
                            bountySubmitBtn.disabled = true;
                            bountySubmitBtn.innerHTML = '<span class="loading loading-spinner"></span> Submitting...';
                            bountyStatus.innerHTML = '';

                            const formData = new FormData(bountyForm);
                            
                            try {
                                const response = await fetch('process_lead.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();

                                if (result.success) {
                                    bountyStatus.innerHTML = '<div class="alert alert-success shadow-lg"><i class="fas fa-check-circle"></i><span>' + result.message + '</span></div>';
                                    bountyForm.reset();
                                    setTimeout(() => {
                                        document.getElementById('modal-bounty').close();
                                        bountyStatus.innerHTML = '';
                                    }, 3000);
                                } else {
                                    bountyStatus.innerHTML = '<div class="alert alert-error shadow-lg"><i class="fas fa-exclamation-triangle"></i><span>' + result.message + '</span></div>';
                                }
                            } catch (error) {
                                bountyStatus.innerHTML = '<div class="alert alert-error shadow-lg"><i class="fas fa-exclamation-triangle"></i><span>An unexpected error occurred. Please try again.</span></div>';
                                console.error('Error:', error);
                            } finally {
                                bountySubmitBtn.disabled = false;
                                bountySubmitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Submit Lead';
                            }
                        });
                    }
                });
            </script>

            <script>
                function openImageModal(index) {
                    const modal = document.getElementById('modal-gallery');
                    modal.showModal();
                    // Ensure the modal is layout-ready before scrolling
                    setTimeout(() => {
                        showGalleryImage(index, 'auto');
                    }, 50);
                }
                
                function showGalleryImage(index, behavior = 'smooth') {
                    const carousel = document.getElementById('modal-gallery-content');
                    const items = carousel.querySelectorAll('.carousel-item');
                    const dots = document.querySelectorAll('.gallery-dot');
                    
                    if (!items[index]) return;

                    carousel.scrollTo({
                        left: items[index].offsetLeft,
                        behavior: behavior
                    });
                    
                    dots.forEach((dot, i) => {
                        if (i === index) {
                            dot.className = "btn btn-sm md:btn-md btn-circle gallery-dot shadow-xl border-none transition-all duration-300 btn-primary";
                        } else {
                            dot.className = "btn btn-sm md:btn-md btn-circle gallery-dot shadow-xl border-none transition-all duration-300 bg-white/20 text-white hover:bg-white/40";
                        }
                    });
                }

                // Function to handle enquiry form submission
                document.addEventListener('DOMContentLoaded', () => {
                    const enquiryForm = document.getElementById('enquiry-form');
                    const enquirySubmitBtn = document.getElementById('enquiry-submit-btn');
                    const enquiryStatus = document.getElementById('enquiry-status');

                    if (enquiryForm) {
                        enquiryForm.addEventListener('submit', async (e) => {
                            e.preventDefault();
                            enquirySubmitBtn.disabled = true;
                            enquirySubmitBtn.classList.add('loading');
                            enquiryStatus.innerHTML = ''; // Clear previous status

                            const formData = new FormData(enquiryForm);
                            
                            try {
                                const response = await fetch('process_enquiry.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const result = await response.json();

                                if (result.success) {
                                    enquiryStatus.innerHTML = '<div class="alert alert-success shadow-lg"><i class="fas fa-check-circle"></i><span>' + result.message + '</span></div>';
                                    enquiryForm.reset();
                                } else {
                                    enquiryStatus.innerHTML = '<div class="alert alert-error shadow-lg"><i class="fas fa-exclamation-triangle"></i><span>' + result.message + '</span></div>';
                                }
                            } catch (error) {
                                enquiryStatus.innerHTML = '<div class="alert alert-error shadow-lg"><i class="fas fa-exclamation-triangle"></i><span>An unexpected error occurred. Please try again.</span></div>';
                                console.error('Error:', error);
                            } finally {
                                enquirySubmitBtn.disabled = false;
                                enquirySubmitBtn.classList.remove('loading');
                            }
                        });
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</div>

<!-- Enquiry Modal -->
<dialog id="modal-enquiry" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box">
        <h3 class="font-bold text-lg">Inquire About This Property</h3>
        <p class="py-4">Fill out the form below and the organization will get back to you shortly.</p>
        
        <div id="enquiry-status" class="mb-4"></div>

        <form id="enquiry-form" method="POST">
            <input type="hidden" name="project_id" value="<?php echo safeOutput($projectId); ?>">
            <input type="hidden" name="organization_id" value="<?php echo safeOutput($project['organization_id']); ?>">
            <input type="hidden" name="project_name" value="<?php echo safeOutput($project['project_name']); ?>">

            <div class="form-control mb-4">
                <label class="label"><span class="label-text">Your Name</span></label>
                <input type="text" name="name" placeholder="John Doe" class="input input-bordered" required>
            </div>
            <div class="form-control mb-4">
                <label class="label"><span class="label-text">Your Email</span></label>
                <input type="email" name="email" placeholder="you@example.com" class="input input-bordered" required>
            </div>
            <div class="form-control mb-4">
                <label class="label"><span class="label-text">Your Phone (Optional)</span></label>
                <input type="tel" name="phone" placeholder="+1234567890" class="input input-bordered">
            </div>
            <div class="form-control mb-4">
                <label class="label"><span class="label-text">Subject</span></label>
                <input type="text" name="subject" value="Inquiry about <?php echo safeOutput($project['project_name']); ?>" class="input input-bordered" required>
            </div>
            <div class="form-control mb-4">
                <label class="label"><span class="label-text">Message</span></label>
                <textarea name="message" class="textarea textarea-bordered h-24" placeholder="I am interested in this property..." required></textarea>
            </div>
            <div class="modal-action">
                <button type="button" class="btn" onclick="document.getElementById('modal-enquiry').close()">Cancel</button>
                <button type="submit" id="enquiry-submit-btn" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-2"></i>Send Inquiry
                </button>
            </div>
        </form>
    </div>
</dialog>

<?php include 'footer.php'; ?>