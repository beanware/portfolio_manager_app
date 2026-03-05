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

function getTotalViews($connection) {
    $result = $connection->query("SELECT SUM(views) FROM projects");
    return $result->fetch_row()[0] ?? 0;
}

function getTopProjectsByViews($connection, $limit = 5) {
    $sql = "SELECT p.project_name, p.views, o.organization_name 
            FROM projects p 
            JOIN organizations o ON p.organization_id = o.organization_id 
            ORDER BY p.views DESC LIMIT ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch Overall Statistics
$totalOrganizations = getTotalCount($connection, 'organizations');
$totalProjects = getTotalCount($connection, 'projects');
$totalUsers = getTotalCount($connection, 'users');
$totalViews = getTotalViews($connection);
$projectsByStatus = getProjectsCountByStatus($connection);
$projectsByType = getProjectsCountByType($connection);
$topProjects = getTopProjectsByViews($connection);

?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-4xl font-black text-base-content mb-10 text-center">Super Admin Dashboard</h1>

        <!-- Overall Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
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

            <div class="stats shadow bg-base-100">
                <div class="stat">
                    <div class="stat-figure text-warning">
                        <i class="fas fa-eye text-3xl"></i>
                    </div>
                    <div class="stat-title">Total Visits</div>
                    <div class="stat-value text-warning"><?= number_format($totalViews) ?></div>
                    <div class="stat-desc">Property views</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
            <!-- Top Projects by Views -->
            <div class="card bg-base-100 shadow-xl lg:col-span-1">
                <div class="card-body">
                    <h2 class="card-title text-xl mb-4 font-bold border-b pb-2 text-warning">
                        <i class="fas fa-fire mr-2"></i>
                        Popular Properties
                    </h2>
                    <div class="space-y-4">
                        <?php foreach ($topProjects as $index => $top): ?>
                        <div class="flex items-center gap-4">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-8 h-8">
                                    <span class="text-xs"><?= $index + 1 ?></span>
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="text-sm font-bold truncate w-40"><?= htmlspecialchars($top['project_name']) ?></div>
                                <div class="text-[10px] opacity-50"><?= htmlspecialchars($top['organization_name']) ?></div>
                            </div>
                            <div class="badge badge-warning badge-sm font-black"><?= number_format($top['views']) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($topProjects)): ?>
                            <p class="text-center opacity-40 italic py-4">No view data yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

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
                    <a href="manage_leads.php" class="btn btn-outline btn-warning btn-lg flex flex-col items-center py-8 h-auto gap-4 group">
                        <i class="fas fa-coins text-4xl group-hover:scale-110 transition-transform"></i>
                        <span>Manage Referral Bounties</span>
                    </a>
                    <a href="manage_enquiries.php" class="btn btn-outline btn-primary btn-lg flex flex-col items-center py-8 h-auto gap-4 group">
                        <i class="fas fa-envelope text-4xl group-hover:scale-110 transition-transform"></i>
                        <span>Property Enquiries</span>
                    </a>
                    <a href="system_activity.php" class="btn btn-outline btn-neutral btn-lg flex flex-col items-center py-8 h-auto gap-4 group">
                        <i class="fas fa-history text-4xl group-hover:scale-110 transition-transform"></i>
                        <span>System Audit Logs</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
