<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

$organization = null;
$projects = [];
$orgId = null;

// Get organization ID from URL
if (isset($_GET['org_id']) && is_numeric($_GET['org_id'])) {
    $orgId = intval($_GET['org_id']);

    // Fetch organization details
    try {
        $stmt = $connection->prepare("SELECT organization_id, organization_name, created_at FROM organizations WHERE organization_id = ?");
        $stmt->bind_param("i", $orgId);
        $stmt->execute();
        $result = $stmt->get_result();
        $organization = $result->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to fetch organization details: " . $e->getMessage());
        $organization = null;
    }

    // Fetch projects for this organization
    if ($organization) {
        try {
            $sql = "SELECT p.*, mi.image_path AS main_image_path FROM projects p
                    LEFT JOIN mainimages mi ON p.project_id = mi.project_id
                    WHERE p.organization_id = ? ORDER BY p.project_date DESC";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("i", $orgId);
            $stmt->execute();
            $result = $stmt->get_result();
            $projects = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Failed to fetch projects for organization: " . $e->getMessage());
            $projects = [];
        }
    }
}
?>

<div class="bg-base-200 min-h-screen">
    <?php if ($organization): ?>
        <!-- Organization Hero Section -->
        <div class="bg-primary text-primary-content py-16">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="avatar placeholder">
                        <div class="bg-neutral text-neutral-content rounded-xl w-24 h-24 text-3xl font-bold">
                            <?= strtoupper(substr($organization['organization_name'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="text-center md:text-left">
                        <div class="text-sm opacity-80 mb-2">
                            <a href="marketplace.php" class="hover:underline">Marketplace</a> / Organization
                        </div>
                        <h1 class="text-5xl font-black mb-2"><?= htmlspecialchars($organization['organization_name']) ?></h1>
                        <p class="text-lg opacity-90">
                            Browsing <?= count($projects) ?> properties from this organization.
                            <span class="mx-2 opacity-50">|</span>
                            Member since <?= date('F Y', strtotime($organization['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-12">
            <!-- Breadcrumbs -->
            <div class="text-sm breadcrumbs mb-8">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="marketplace.php">Marketplace</a></li>
                    <li><?= htmlspecialchars($organization['organization_name']) ?></li>
                </ul>
            </div>

            <div class="flex justify-between items-end mb-10 border-b border-base-300 pb-4">
                <h2 class="text-3xl font-bold">Available Properties</h2>
                <div class="badge badge-lg badge-outline"><?= count($projects) ?> Results</div>
            </div>

            <?php if (!empty($projects)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($projects as $project): ?>
                        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300 group">
                            <figure class="relative h-64 overflow-hidden">
                                <?php $imagePath = $project['main_image_path'] ?: 'uploads/main/default.jpg'; ?>
                                <img src="<?= htmlspecialchars($imagePath) ?>" 
                                     alt="<?= htmlspecialchars($project['project_name']) ?>" 
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                <?php if (!empty($project['project_type'])): ?>
                                    <div class="absolute top-4 left-4">
                                        <div class="badge badge-secondary shadow-lg"><?= htmlspecialchars($project['project_type']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </figure>
                            <div class="card-body p-6">
                                <h2 class="card-title text-2xl font-bold mb-2">
                                    <?= htmlspecialchars($project['project_name']) ?>
                                </h2>
                                <p class="text-base-content/70 flex items-center gap-2 mb-4">
                                    <i class="fas fa-map-marker-alt text-primary"></i>
                                    <?= htmlspecialchars($project['project_location']) ?>
                                </p>
                                
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <?php if (!empty($project['bedrooms'])): ?>
                                        <div class="badge badge-ghost gap-1"><i class="fas fa-bed text-xs"></i> <?= $project['bedrooms'] ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($project['bathrooms'])): ?>
                                        <div class="badge badge-ghost gap-1"><i class="fas fa-bath text-xs"></i> <?= $project['bathrooms'] ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-actions justify-between items-center mt-auto pt-4 border-t border-base-200">
                                    <?php if (!empty($project['price_range'])): ?>
                                        <div class="text-xl font-bold text-primary">
                                            <?= htmlspecialchars($project['price_range']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div></div>
                                    <?php endif; ?>
                                    <a href="project_details.php?id=<?= $project['project_id'] ?>" 
                                       class="btn btn-primary btn-sm rounded-full px-6 shadow-md hover:shadow-lg">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card bg-base-100 shadow-xl py-20 text-center">
                    <div class="flex flex-col items-center">
                        <div class="bg-base-200 rounded-full p-6 mb-6">
                            <i class="fas fa-building-circle-exclamation text-5xl opacity-20"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">No Properties Found</h3>
                        <p class="text-base-content/60 max-w-md mx-auto">
                            <?= htmlspecialchars($organization['organization_name']) ?> hasn't listed any properties in the marketplace yet.
                        </p>
                        <a href="marketplace.php" class="btn btn-ghost mt-8">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Marketplace
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    <?php else: ?>
        <div class="flex flex-col items-center justify-center py-32 px-4">
            <div class="text-9xl font-black text-base-300 mb-8">404</div>
            <h1 class="text-4xl font-bold text-center mb-4">Organization Not Found</h1>
            <p class="text-lg text-base-content/60 text-center max-w-lg mb-12">
                The organization you are looking for might have been removed or the link is incorrect.
            </p>
            <a href="marketplace.php" class="btn btn-primary btn-lg px-12 rounded-full shadow-xl">
                Return to Marketplace
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
