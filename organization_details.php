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
        $stmt = $connection->prepare("SELECT * FROM organizations WHERE organization_id = ?");
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
                    <div class="avatar">
                        <div class="bg-neutral text-neutral-content rounded-xl w-24 h-24 shadow-2xl">
                            <?php if (!empty($organization['logo_path']) && file_exists($organization['logo_path'])): ?>
                                <img src="<?= htmlspecialchars($organization['logo_path']) ?>" alt="<?= htmlspecialchars($organization['organization_name']) ?>">
                            <?php else: ?>
                                <div class="flex items-center justify-center h-full text-3xl font-bold">
                                    <?= strtoupper(substr($organization['organization_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
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
                        <?php if (!empty($organization['license_number'])): ?>
                            <div class="mt-4 badge badge-outline badge-lg text-primary-content border-primary-content/30 py-4 px-6">
                                <i class="fas fa-id-card mr-2"></i> License: <?= htmlspecialchars($organization['license_number']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-12">
            <!-- Organization Info Bar -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <?php if (!empty($organization['company_address'])): ?>
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 flex-row items-center gap-4">
                        <div class="bg-primary/10 text-primary rounded-xl p-3">
                            <i class="fas fa-map-marked-alt text-xl"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase opacity-50">Location</div>
                            <div class="text-sm"><?= nl2br(htmlspecialchars($organization['company_address'])) ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($organization['contact_email']) || !empty($organization['contact_phone'])): ?>
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 flex-row items-center gap-4">
                        <div class="bg-secondary/10 text-secondary rounded-xl p-3">
                            <i class="fas fa-headset text-xl"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase opacity-50">Contact Info</div>
                            <div class="text-sm">
                                <?php if ($organization['contact_email']): ?>
                                    <a href="mailto:<?= htmlspecialchars($organization['contact_email']) ?>" class="hover:underline block"><?= htmlspecialchars($organization['contact_email']) ?></a>
                                <?php endif; ?>
                                <?php if ($organization['contact_phone']): ?>
                                    <div class="font-bold"><?= htmlspecialchars($organization['contact_phone']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($organization['website'])): ?>
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 flex-row items-center gap-4">
                        <div class="bg-accent/10 text-accent rounded-xl p-3">
                            <i class="fas fa-globe text-xl"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase opacity-50">Official Website</div>
                            <div class="text-sm">
                                <a href="<?= htmlspecialchars($organization['website']) ?>" target="_blank" class="link link-primary font-bold">Visit Website</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($organization['description'])): ?>
            <div class="card bg-base-100 shadow-sm border border-base-200 mb-12">
                <div class="card-body">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-primary"></i> Company Profile
                    </h3>
                    <div class="text-base-content/80 leading-relaxed">
                        <?= nl2br(htmlspecialchars($organization['description'])) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                                    <?php if (!empty($project['price'])): ?>
                                        <div class="text-xl font-bold text-primary">
                                            <?= htmlspecialchars($project['price']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-xs opacity-50 italic">Price on request</div>
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
