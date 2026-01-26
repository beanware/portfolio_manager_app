<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'connection.php';
include 'header.php';

// Validate and sanitize input
$projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Early validation
if ($projectId <= 0) {
    // Redirect or show error
    header('Location: gallery.php');
    exit();
}

// Single query for project + main image
$sql = "SELECT p.*, mi.image_path as main_image_path
        FROM projects p 
        LEFT JOIN mainimages mi ON p.project_id = mi.project_id
        WHERE p.project_id = ?";
$stmt = $connection->prepare($sql);

if (!$stmt) {
    die("Database error: " . $connection->error);
}

$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

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

    <!-- Back Navigation -->
    <div class="mb-8">
        <a href="gallery.php" class="btn btn-ghost">
            <i class="fas fa-arrow-left mr-2"></i> Back to Gallery
        </a>
    </div>

    <?php if (!$project): ?>
        <div class="alert alert-error shadow-lg">
            <div>
                <i class="fas fa-exclamation-triangle"></i>
                <span>Project not found. It may have been removed or the link is incorrect.</span>
            </div>
            <div class="mt-4">
                <a href="gallery.php" class="btn btn-primary">Browse Available Projects</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Project Header -->
        <div class="mb-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-5xl font-extrabold text-base-content leading-tight">
                        <?php echo safeOutput($project['project_name'], 'Unnamed Project'); ?>
                    </h1>
                    <?php if (!empty($project['project_location'])): ?>
                        <p class="text-lg text-base-content/80 flex items-center mt-2">
                            <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
                            <?php echo safeOutput($project['project_location']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($project['price_range'])): ?>
                    <div class="text-right lg:text-left">
                        <span class="text-4xl font-bold text-primary">
                            <?php echo htmlspecialchars($project['price_range']); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Key Property Facts Section -->
            <div class="bg-base-200 rounded-2xl p-6 shadow-md mb-10">
                <h3 class="text-xl font-bold text-base-content mb-4">Property Highlights</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-base-content/70">
                    <?php if (!empty($project['bedrooms'])): ?>
                        <div class="flex flex-col items-center justify-center p-3 bg-base-100 rounded-lg shadow-sm">
                            <i class="fas fa-bed text-2xl mb-1 text-primary"></i>
                            <span class="font-bold text-lg"><?php echo htmlspecialchars($project['bedrooms']); ?></span>
                            <span class="text-xs uppercase">Beds</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($project['bathrooms'])): ?>
                        <div class="flex flex-col items-center justify-center p-3 bg-base-100 rounded-lg shadow-sm">
                            <i class="fas fa-bath text-2xl mb-1 text-primary"></i>
                            <span class="font-bold text-lg"><?php echo htmlspecialchars($project['bathrooms']); ?></span>
                            <span class="text-xs uppercase">Baths</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($project['square_footage'])): ?>
                        <div class="flex flex-col items-center justify-center p-3 bg-base-100 rounded-lg shadow-sm">
                            <i class="fas fa-ruler-combined text-2xl mb-1 text-primary"></i>
                            <span class="font-bold text-lg"><?php echo htmlspecialchars($project['square_footage']); ?></span>
                            <span class="text-xs uppercase">Sqft</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($project['project_type'])): ?>
                        <div class="flex flex-col items-center justify-center p-3 bg-base-100 rounded-lg shadow-sm">
                            <i class="fas fa-building text-2xl mb-1 text-primary"></i>
                            <span class="font-bold text-lg capitalize"><?php echo htmlspecialchars($project['project_type']); ?></span>
                            <span class="text-xs uppercase">Type</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($project['project_date']) && $project['project_date'] !== '0000-00-00'): ?>
                        <div class="flex flex-col items-center justify-center p-3 bg-base-100 rounded-lg shadow-sm">
                            <i class="fas fa-calendar text-2xl mb-1 text-primary"></i>
                            <span class="font-bold text-lg"><?php echo formatDate($project['project_date']); ?></span>
                            <span class="text-xs uppercase">Date</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Image with Modal Trigger -->
        <?php if (!empty($project['main_image_path'])): ?>
        <div class="mb-10 relative">
            <figure class="rounded-2xl overflow-hidden shadow-2xl cursor-zoom-in hover:shadow-3xl transition-shadow duration-300"
                    onclick="document.getElementById('modal-main').showModal()">
                <img src="<?php echo htmlspecialchars($project['main_image_path']); ?>" 
                     alt="<?php echo safeOutput($project['project_name'], 'Project image'); ?>"
                     class="w-full h-auto max-h-[70vh] object-cover"
                     loading="eager">
                <div class="absolute inset-0 bg-gradient-to-t from-base-100/10 to-transparent"></div>
                <div class="absolute bottom-4 right-4 bg-base-100/90 backdrop-blur-sm rounded-full p-3 shadow-lg hover:scale-110 transition-transform">
                    <i class="fas fa-expand-alt text-base-content text-lg"></i>
                </div>
            </figure>
            
            <!-- DaisyUI Modal for Main Image -->
            <dialog id="modal-main" class="modal">
                <div class="modal-box max-w-6xl p-0 bg-transparent shadow-none overflow-visible">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute -right-3 -top-3 z-10 bg-base-100 shadow-lg hover:bg-base-200 border border-base-300"
                                aria-label="Close image view">
                            ✕
                        </button>
                    </form>
                    <img src="<?php echo htmlspecialchars($project['main_image_path']); ?>" 
                         alt="Full size view" 
                         class="w-full h-auto rounded-lg shadow-2xl">
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        </div>
        <?php endif; ?>

        <!-- Project Description -->
        <div class="prose prose-lg max-w-none mb-12">
            <h2 class="text-2xl font-bold text-base-content mb-4">Project Overview</h2>
            <div class="bg-base-100 rounded-2xl p-6 shadow-sm border border-base-300">
                <?php if (!empty($project['project_description'])): ?>
                    <p class="text-base-content/80 leading-relaxed">
                        <?php echo nl2br(safeOutput($project['project_description'])); ?>
                    </p>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-clipboard text-4xl text-base-content/30 mb-4"></i>
                        <p class="text-base-content/60">No description available for this project.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Image Gallery/Carousel -->
        <?php if (!empty($carouselImages)): ?>
        <div class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="m-4 text-2xl font-bold text-base-content">Project Gallery</h2>
                <div class="text-sm text-base-content/60">
                    <span><?php echo count($carouselImages); ?> images</span>
                </div>
            </div>
            
            <!-- Responsive Image Grid -->
            <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 m-2">
                <?php foreach ($carouselImages as $index => $image): ?>
                <div class="relative group rounded-xl overflow-hidden shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-300"
                     onclick="openImageModal(<?php echo $index; ?>)">
                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                         alt="<?php echo safeOutput($image['image_title'], 'Project image ' . ($index + 1)); ?>"
                         class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                         loading="lazy"
                    >
                    <?php if (!empty($image['image_title'])): ?>
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-base-100/90 to-transparent p-3 text-sm text-base-content opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <span class="font-medium truncate block"><?php echo safeOutput($image['image_title']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300">
                        <i class="fas fa-search-plus text-white text-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- DaisyUI Modal for Gallery Images -->
            <dialog id="modal-gallery" class="modal">
                <div class="modal-box max-w-6xl p-0 bg-transparent shadow-none overflow-visible">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute -right-3 -top-3 z-10 bg-base-100 shadow-lg hover:bg-base-200 border border-base-300"
                                aria-label="Close gallery">
                            ✕
                        </button>
                    </form>
                    <div id="modal-gallery-content" class="carousel w-full">
                        <?php foreach ($carouselImages as $index => $image): ?>
                        <div id="gallery-item-<?php echo $index; ?>" 
                             class="carousel-item w-full justify-center" style="scroll-snap-align: center;"> <!-- Removed 'hidden' and added justify-center, style for snap-align -->
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 alt="<?php echo safeOutput($image['image_title'], 'Gallery image ' . ($index + 1)); ?>"
                                 class="w-full h-auto max-h-[80vh] object-contain rounded-lg shadow-2xl">
                            <?php if (!empty($image['image_title'])): ?>
                            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-base-100/90 backdrop-blur-sm rounded-lg px-4 py-2 shadow-lg">
                                <span class="font-medium"><?php echo safeOutput($image['image_title']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Navigation dots -->
                    <?php if (count($carouselImages) > 1): ?>
                    <div class="flex justify-center w-full py-4 gap-2 mt-4"> <!-- Moved inside modal-box, added mt-4, removed absolute positioning -->
                        <?php foreach ($carouselImages as $index => $image): ?>
                        <button onclick="showGalleryImage(<?php echo $index; ?>)" 
                                class="btn btn-xs btn-circle <?php echo $index === 0 ? 'btn-primary' : 'btn-ghost'; ?>"
                                aria-label="View image <?php echo $index + 1; ?>">
                            <?php echo $index + 1; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>

            <script>
                function openImageModal(index) {
                    const modal = document.getElementById('modal-gallery');
                    showGalleryImage(index);
                    modal.showModal();
                }
                
                function showGalleryImage(index) {
                    const carouselContent = document.getElementById('modal-gallery-content');
                    const items = carouselContent.querySelectorAll('.carousel-item');
                    const dots = document.querySelectorAll('#modal-gallery .btn-circle');
                    
                    if (items.length === 0 || index < 0 || index >= items.length) return; // Guard against empty or invalid index

                    // Calculate scroll position
                    const targetItem = items[index];
                    
                    // The scrollLeft should be the offsetLeft of the target item relative to its parent's scrollable area
                    carouselContent.scrollTo({
                        left: targetItem.offsetLeft,
                        behavior: 'smooth'
                    });
                    
                    // Update active dot
                    dots.forEach((dot, i) => {
                        if (i === index) {
                            dot.classList.remove('btn-ghost');
                            dot.classList.add('btn-primary');
                        } else {
                            dot.classList.remove('btn-primary');
                            dot.classList.add('btn-ghost');
                        }
                    });
                }

                // New JavaScript for carousel scrolling
                function scrollCarousel(direction) {
                    const carousel = document.getElementById('image-carousel');
                    const scrollAmount = carousel.offsetWidth * 0.7; // Scroll by about 70% of carousel width
                    carousel.scrollBy({
                        left: direction * scrollAmount,
                        behavior: 'smooth'
                    });
                }
            </script>
        </div>
        <?php else: ?>
        <!-- Empty gallery state -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-base-content mb-6">Project Gallery</h2>
            <div class="bg-base-100 rounded-2xl p-12 text-center border border-base-300 border-dashed">
                <i class="fas fa-images text-5xl text-base-content/20 mb-4"></i>
                <h3 class="text-xl font-medium text-base-content/70 mb-2">No additional images</h3>
                <p class="text-base-content/50 max-w-md mx-auto">
                    This project doesn't have a gallery yet. Additional images can be added through the management panel.
                </p>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>