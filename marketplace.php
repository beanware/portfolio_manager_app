<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Fetch all organizations
try {
    $stmt = $connection->prepare("SELECT organization_id, organization_name, created_at FROM organizations ORDER BY organization_name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $organizations = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Failed to fetch organizations: " . $e->getMessage());
    $organizations = []; 
}

?>

<div class="bg-base-200 min-h-screen">
    <!-- Marketplace Hero -->
    <div class="bg-neutral text-neutral-content py-20">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-6xl font-black mb-6">Real Estate Marketplace</h1>
            <p class="text-xl opacity-70 max-w-2xl mx-auto">
                Explore properties from trusted organizations. Find your next investment or dream home in our curated marketplace.
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-16">
        <div class="flex justify-between items-center mb-12">
            <h2 class="text-4xl font-extrabold">All Organizations</h2>
            <div class="badge badge-lg badge-primary p-4"><?= count($organizations) ?> Partners</div>
        </div>

        <?php if (!empty($organizations)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($organizations as $org): ?>
                    <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300 border border-base-300">
                        <div class="card-body items-center text-center p-10">
                            <div class="avatar placeholder mb-4">
                                <div class="bg-primary text-primary-content rounded-full w-20 h-20 text-2xl font-bold ring ring-primary ring-offset-base-100 ring-offset-2">
                                    <?= strtoupper(substr($org['organization_name'], 0, 1)) ?>
                                </div>
                            </div>
                            <h2 class="card-title text-3xl font-bold mb-2"><?= htmlspecialchars($org['organization_name']) ?></h2>
                            <p class="text-base-content/60 mb-6 italic">
                                Partner since <?= date('M Y', strtotime($org['created_at'])) ?>
                            </p>
                            
                            <!-- Simple stats placeholder (could be dynamic in future) -->
                            <div class="flex gap-4 mb-8">
                                <div class="text-center">
                                    <div class="text-sm font-bold">Trusted</div>
                                    <div class="text-xs opacity-50 uppercase">Status</div>
                                </div>
                                <div class="divider divider-horizontal"></div>
                                <div class="text-center">
                                    <div class="text-sm font-bold">Verify</div>
                                    <div class="text-xs opacity-50 uppercase">Identity</div>
                                </div>
                            </div>

                            <div class="card-actions w-full">
                                <a href="organization_details.php?org_id=<?= $org['organization_id'] ?>" 
                                   class="btn btn-primary btn-block rounded-full shadow-lg">
                                    View Organization Properties
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info shadow-lg">
                <i class="fas fa-info-circle text-2xl"></i>
                <div>
                    <h3 class="font-bold">No organizations found</h3>
                    <div class="text-xs">Check back later for new partners.</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-20 text-center">
            <a href="index.php" class="btn btn-ghost gap-2">
                <i class="fas fa-home"></i>
                Back to Homepage
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
