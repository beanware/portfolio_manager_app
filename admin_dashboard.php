<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Require super_admin role to access this dashboard
requireRole('super_admin');

// --- Statistics Fetching Functions (Overall) ---

function getTotalCount($connection, $table) {
    $stmt = $connection->prepare("SELECT COUNT(*) FROM {$table}");
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    return $count;
}

function getProjectsCountByStatus($connection) {
    $stmt = $connection->prepare("SELECT project_status, COUNT(*) AS count FROM projects GROUP BY project_status");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

function getProjectsCountByType($connection) {
    $stmt = $connection->prepare("SELECT project_type, COUNT(*) AS count FROM projects GROUP BY project_type");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Fetch Overall Statistics
$totalOrganizations = getTotalCount($connection, 'organizations');
$totalProjects = getTotalCount($connection, 'projects');
$totalUsers = getTotalCount($connection, 'users');
$projectsByStatus = getProjectsCountByStatus($connection);
$projectsByType = getProjectsCountByType($connection);

?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-4xl font-black text-base-content mb-10 text-center">Super Admin Dashboard</h1>

        <!-- Overall Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="stats shadow bg-base-100">
                <div class="stat">
                    <div class="stat-figure text-primary">
                        <i class="fas fa-sitemap text-3xl"></i>
                    </div>
                    <div class="stat-title">Organizations</div>
                    <div class="stat-value text-primary"><?= $totalOrganizations ?></div>
                    <div class="stat-desc">Active in system</div>
                </div>
            </div>
            
            <div class="stats shadow bg-base-100">
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <i class="fas fa-project-diagram text-3xl"></i>
                    </div>
                    <div class="stat-title">Total Projects</div>
                    <div class="stat-value text-secondary"><?= $totalProjects ?></div>
                    <div class="stat-desc">Across all orgs</div>
                </div>
            </div>

            <div class="stats shadow bg-base-100">
                <div class="stat">
                    <div class="stat-figure text-accent">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                    <div class="stat-title">Total Users</div>
                    <div class="stat-value text-accent"><?= $totalUsers ?></div>
                    <div class="stat-desc">Registered accounts</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            <!-- Projects by Status -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-xl mb-4 font-bold border-b pb-2">
                        <i class="fas fa-chart-pie text-primary mr-2"></i>
                        Projects by Status
                    </h2>
                    <ul class="space-y-3">
                        <?php if (!empty($projectsByStatus)): ?>
                            <?php foreach ($projectsByStatus as $status): ?>
                                <li class="flex justify-between items-center p-3 bg-base-200 rounded-lg">
                                    <span class="font-medium"><?= htmlspecialchars(ucfirst($status['project_status'] ?: 'Unset')) ?></span>
                                    <span class="badge badge-primary badge-lg"><?= $status['count'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-6 opacity-50 italic">No projects found.</div>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Projects by Type -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-xl mb-4 font-bold border-b pb-2">
                        <i class="fas fa-building text-secondary mr-2"></i>
                        Projects by Type
                    </h2>
                    <ul class="space-y-3">
                        <?php if (!empty($projectsByType)): ?>
                            <?php foreach ($projectsByType as $type): ?>
                                <li class="flex justify-between items-center p-3 bg-base-200 rounded-lg">
                                    <span class="font-medium"><?= htmlspecialchars(ucfirst($type['project_type'] ?: 'Unset')) ?></span>
                                    <span class="badge badge-secondary badge-lg"><?= $type['count'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-6 opacity-50 italic">No projects found.</div>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Admin Functions -->
        <div class="card bg-base-100 shadow-xl border-t-4 border-primary">
            <div class="card-body">
                <h2 class="card-title text-2xl font-black mb-6">System Administration</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="manage_organizations.php" class="btn btn-outline btn-primary btn-lg flex flex-col items-center py-8 h-auto gap-4 group">
                        <i class="fas fa-sitemap text-4xl group-hover:scale-110 transition-transform"></i>
                        <span>Manage Organizations</span>
                    </a>
                    <a href="manage_users.php" class="btn btn-outline btn-secondary btn-lg flex flex-col items-center py-8 h-auto gap-4 group">
                        <i class="fas fa-users-cog text-4xl group-hover:scale-110 transition-transform"></i>
                        <span>Manage All Users</span>
                    </a>
                    <a href="projects.php" class="btn btn-outline btn-accent btn-lg flex flex-col items-center py-8 h-auto gap-4 group">
                        <i class="fas fa-folder-open text-4xl group-hover:scale-110 transition-transform"></i>
                        <span>System Wide Projects</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
