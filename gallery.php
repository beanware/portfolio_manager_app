<?php
define('ACCESS_ALLOWED', true);
include 'connection.php';
include 'header.php'; // This now includes our unified DaisyUI setup

// SOLVE N+1 PROBLEM: Single query with JOIN
$query = "SELECT p.*, mi.image_path as main_image_path, mi.image_title as main_image_title 
          FROM projects p 
          LEFT JOIN mainimages mi ON p.project_id = mi.project_id
          ORDER BY p.project_date DESC";
$result = $connection->query($query);

if (!$result) {
    die("Query failed: " . $connection->error);
}

$projects = $result->fetch_all(MYSQLI_ASSOC);
$connection->close();
?>

<main class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-center text-base-content mb-12 pt-4">Project Portfolio</h1>
    
    <?php if (empty($projects)): ?>
        <div class="alert alert-info shadow-lg max-w-2xl mx-auto">
            <div>
                <i class="fas fa-info-circle"></i>
                <span>No projects yet. Add some through the management panel.</span>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($projects as $project): ?>
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300">
                <figure class="h-64 overflow-hidden">
                    <img 
                        src="<?php echo htmlspecialchars($project['main_image_path'] ?? 'uploads/main/default.jpg'); ?>" 
                        alt="<?php echo htmlspecialchars($project['main_image_title'] ?? $project['project_name']); ?>"
                        class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                        loading="lazy"
                    >
                </figure>
                <div class="card-body">
                    <h2 class="card-title text-base-content mb-2">
                        <?php echo htmlspecialchars($project['project_name']); ?>
                    </h2>
                    
                    <div class="flex items-center text-base-content/80 text-sm mb-3">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <span class="font-semibold"><?php echo htmlspecialchars($project['project_location'] ?? 'Location not specified'); ?></span>
                    </div>

                    <?php if (!empty($project['price_range'])): ?>
                        <p class="text-2xl font-bold text-primary mb-3">
                            <?php echo htmlspecialchars($project['price_range']); ?>
                        </p>
                    <?php endif; ?>

                    <div class="flex flex-wrap gap-4 text-sm text-base-content/70 mb-4">
                        <?php if (!empty($project['bedrooms'])): ?>
                            <div class="flex items-center">
                                <i class="fas fa-bed mr-1"></i> <?php echo htmlspecialchars($project['bedrooms']); ?> Beds
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($project['bathrooms'])): ?>
                            <div class="flex items-center">
                                <i class="fas fa-bath mr-1"></i> <?php echo htmlspecialchars($project['bathrooms']); ?> Baths
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($project['square_footage'])): ?>
                            <div class="flex items-center">
                                <i class="fas fa-ruler-combined mr-1"></i> <?php echo htmlspecialchars($project['square_footage']); ?> sqft
                            </div>
                        <?php endif; ?>
                    </div>

                    <p class="text-base-content/70 line-clamp-3 mb-4">
                        <?php echo htmlspecialchars($project['project_description'] ?? 'No description available.'); ?>
                    </p>

                    <div class="card-actions justify-end">
                        <a href="project_details.php?id=<?php echo intval($project['project_id']); ?>" 
                           class="btn btn-primary">
                            View Property <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>