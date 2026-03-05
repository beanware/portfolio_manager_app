<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Get filter parameters
$selected_org_id = isset($_GET['org_id']) ? intval($_GET['org_id']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// 1. Fetch all organizations (excluding 'Default Organization')
try {
    $stmt = $connection->prepare("SELECT organization_id, organization_name FROM organizations WHERE organization_name != 'Default Organization' ORDER BY organization_name ASC");
    $stmt->execute();
    $org_result = $stmt->get_result();
    $organizations = $org_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Failed to fetch organizations: " . $e->getMessage());
    $organizations = []; 
}

// 2. Fetch properties (excluding 'Default Organization' and optionally filtering by org)
try {
    $query = "SELECT p.*, o.organization_name, mi.image_path as main_image 
              FROM projects p 
              LEFT JOIN organizations o ON p.organization_id = o.organization_id
              LEFT JOIN mainimages mi ON p.project_id = mi.project_id
              WHERE o.organization_name != 'Default Organization' AND p.project_status = 'published'";
    
    $params = [];
    $types = "";

    if ($selected_org_id > 0) {
        $query .= " AND p.organization_id = ?";
        $params[] = $selected_org_id;
        $types .= "i";
    }

    if ($search_query !== '') {
        $query .= " AND (p.project_name LIKE ? OR p.project_location LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $types .= "ss";
    }

    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $connection->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Failed to fetch projects: " . $e->getMessage());
    $projects = [];
}
?>

<div class="bg-base-200 min-h-screen">
    <!-- Marketplace Hero -->
    <div class="bg-neutral text-neutral-content py-20">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-6xl font-black mb-6">Real Estate Marketplace</h1>
            <p class="text-xl opacity-70 max-w-2xl mx-auto">
                Explore premium properties from our trusted partners. Refer a buyer and earn rewards!
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-12">
        <!-- Filters Section -->
        <div class="card bg-base-100 shadow-xl mb-12 border border-base-300">
            <div class="card-body p-6">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <div class="form-control flex-grow min-w-[250px]">
                        <label class="label"><span class="label-text font-bold">Search Properties</span></label>
                        <div class="join w-full">
                            <input type="text" name="search" placeholder="Search by name or location..." 
                                   class="input input-bordered join-item w-full" value="<?= htmlspecialchars($search_query) ?>" />
                            <button type="submit" class="btn btn-primary join-item"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    
                    <div class="form-control w-full md:w-64">
                        <label class="label"><span class="label-text font-bold">Filter by Organization</span></label>
                        <select name="org_id" class="select select-bordered w-full" onchange="this.form.submit()">
                            <option value="0">All Organizations</option>
                            <?php foreach ($organizations as $org): ?>
                                <option value="<?= $org['organization_id'] ?>" <?= $selected_org_id == $org['organization_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($org['organization_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($selected_org_id > 0 || $search_query !== ''): ?>
                        <a href="marketplace.php" class="btn btn-ghost">Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-extrabold">Featured Properties</h2>
            <div class="badge badge-lg badge-primary p-4"><?= count($projects) ?> Listings Found</div>
        </div>

        <?php if (!empty($projects)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($projects as $project): ?>
                    <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300 border border-base-300 group overflow-hidden">
                        <!-- Project Image -->
                        <figure class="relative h-64 overflow-hidden">
                            <img src="<?= htmlspecialchars($project['main_image'] ?: 'img/arch1.jpg') ?>" 
                                 alt="<?= htmlspecialchars($project['project_name']) ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                            <div class="absolute top-4 right-4">
                                <span class="badge badge-primary badge-lg shadow-lg"><?= ucfirst($project['project_type']) ?></span>
                            </div>
                            <?php if ($project['price']): ?>
                                <div class="absolute bottom-4 left-4">
                                    <div class="bg-base-100/90 backdrop-blur px-4 py-2 rounded-xl font-black text-primary shadow-lg">
                                        <?= htmlspecialchars($project['price']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </figure>

                        <div class="card-body p-6">
                            <div class="flex items-center gap-2 text-xs font-bold text-primary uppercase tracking-widest mb-2">
                                <i class="fas fa-building"></i>
                                <?= htmlspecialchars($project['organization_name']) ?>
                            </div>
                            <h2 class="card-title text-2xl font-bold mb-1"><?= htmlspecialchars($project['project_name']) ?></h2>
                            <p class="text-base-content/60 text-sm mb-4 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-error"></i>
                                <?= htmlspecialchars($project['project_location']) ?>
                            </p>
                            
                            <div class="divider my-2"></div>
                            
                            <div class="flex justify-between items-center mb-6">
                                <div class="text-xs opacity-50">
                                    Listed on <?= date('M d, Y', strtotime($project['created_at'])) ?>
                                </div>
                                <div class="tooltip" data-tip="<?= htmlspecialchars($project['bounty_info'] ?: 'Earn reward for referral') ?>">
                                    <span class="badge badge-outline badge-secondary text-[10px] py-3">
                                        <i class="fas fa-coins mr-1"></i> <?= htmlspecialchars($project['bounty_info'] ?: 'Bounty') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="card-actions flex-nowrap gap-2">
                                <a href="project_details.php?id=<?= $project['project_id'] ?>" 
                                   class="btn btn-primary flex-grow rounded-xl shadow-md">
                                    View Details
                                </a>
                                <a href="organization_details.php?org_id=<?= $project['organization_id'] ?>" 
                                   class="btn btn-ghost btn-square rounded-xl tooltip" data-tip="View Organization">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info shadow-lg py-12">
                <div class="flex flex-col items-center w-full gap-4">
                    <i class="fas fa-search-minus text-6xl opacity-20"></i>
                    <div class="text-center">
                        <h3 class="text-2xl font-bold">No properties found</h3>
                        <p class="opacity-70">Try adjusting your search or filters to find what you're looking for.</p>
                        <a href="marketplace.php" class="btn btn-primary mt-6">Show All Properties</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-20 text-center">
            <h3 class="text-2xl font-bold mb-4">Want to browse by organization?</h3>
            <div class="flex flex-wrap justify-center gap-4">
                <?php foreach ($organizations as $org): ?>
                    <a href="organization_details.php?org_id=<?= $org['organization_id'] ?>" 
                       class="btn btn-outline btn-sm rounded-full">
                        <?= htmlspecialchars($org['organization_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
