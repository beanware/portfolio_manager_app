<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Require 'admin' role to access this dashboard
requireRole('admin');

$currentUser = getCurrentUser();
$organization_id = $currentUser['organization_id'];

// If the user has no organization, block access
if (!$organization_id) {
    safeRedirect("403.php?message=" . urlencode("Your admin account is not associated with any organization."), 403);
}

// Fetch organization details
$organizationName = "Your Organization";
$orgCreatedAt = "";
$logoPath = "";
try {
    $stmt = $connection->prepare("SELECT organization_name, created_at, logo_path FROM organizations WHERE organization_id = ?");
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $organizationName = $row['organization_name'];
        $orgCreatedAt = $row['created_at'];
        $logoPath = $row['logo_path'];
    }
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Failed to fetch organization info: " . $e->getMessage());
}

// --- Statistics Fetching Functions (Organization Specific) ---

function getOrganizationTotalProjects($connection, $organization_id) {
    $stmt = $connection->prepare("SELECT COUNT(*) FROM projects WHERE organization_id = ?");
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    return $count;
}

function getOrganizationProjectsCountByStatus($connection, $organization_id) {
    $stmt = $connection->prepare("SELECT project_status, COUNT(*) AS count FROM projects WHERE organization_id = ? GROUP BY project_status");
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

function getOrganizationTotalUsers($connection, $organization_id) {
    $stmt = $connection->prepare("SELECT COUNT(*) FROM users WHERE organization_id = ?");
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    return $count;
}

function getOrganizationTotalViews($connection, $organization_id) {
    $stmt = $connection->prepare("SELECT SUM(views) FROM projects WHERE organization_id = ?");
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    return $count ?? 0;
}

function getOrganizationTopProjects($connection, $organization_id, $limit = 5) {
    $stmt = $connection->prepare("SELECT project_name, views FROM projects WHERE organization_id = ? ORDER BY views DESC LIMIT ?");
    $stmt->bind_param("ii", $organization_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch Organization Specific Statistics
$orgTotalProjects = getOrganizationTotalProjects($connection, $organization_id);
$orgProjectsByStatus = getOrganizationProjectsCountByStatus($connection, $organization_id);
$orgTotalUsers = getOrganizationTotalUsers($connection, $organization_id);
$orgTotalViews = getOrganizationTotalViews($connection, $organization_id);
$orgTopProjects = getOrganizationTopProjects($connection, $organization_id);

?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Hero Header -->
        <div class="bg-primary text-primary-content rounded-3xl p-8 mb-10 shadow-xl flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-6">
                <div class="avatar">
                    <div class="bg-neutral text-neutral-content rounded-2xl w-20 h-20 shadow-2xl overflow-hidden">
                        <?php if ($logoPath && file_exists($logoPath)): ?>
                            <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo">
                        <?php else: ?>
                            <div class="flex items-center justify-center h-full text-3xl font-bold">
                                <?= strtoupper(substr($organizationName, 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <h1 class="text-3xl font-black"><?= htmlspecialchars($organizationName) ?></h1>
                    <p class="opacity-70">Organization Administration Dashboard</p>
                    <div class="badge badge-secondary mt-2">Partner since <?= date('M Y', strtotime($orgCreatedAt)) ?></div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="projects.php" class="btn btn-neutral btn-md rounded-xl">
                    <i class="fas fa-plus mr-2"></i> New Project
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="stats shadow bg-base-100 md:col-span-2">
                <div class="stat">
                    <div class="stat-figure text-primary">
                        <i class="fas fa-folder-open text-3xl opacity-30"></i>
                    </div>
                    <div class="stat-title">Portfolio Size</div>
                    <div class="stat-value text-primary"><?= $orgTotalProjects ?></div>
                    <div class="stat-desc">Total listings</div>
                </div>
                
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <i class="fas fa-users text-3xl opacity-30"></i>
                    </div>
                    <div class="stat-title">Team Members</div>
                    <div class="stat-value text-secondary"><?= $orgTotalUsers ?></div>
                    <div class="stat-desc">Active accounts</div>
                </div>

                <div class="stat">
                    <div class="stat-figure text-warning">
                        <i class="fas fa-eye text-3xl opacity-30"></i>
                    </div>
                    <div class="stat-title">Total Visits</div>
                    <div class="stat-value text-warning"><?= number_format($orgTotalViews) ?></div>
                    <div class="stat-desc">Across all properties</div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl">
                <div class="card-body py-4">
                    <h3 class="font-bold text-sm uppercase opacity-50 mb-4 tracking-widest">Most Viewed</h3>
                    <div class="space-y-3">
                        <?php foreach ($orgTopProjects as $top): ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="truncate w-40 font-medium"><?= htmlspecialchars($top['project_name']) ?></span>
                            <span class="badge badge-ghost font-bold"><?= number_format($top['views']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($orgTopProjects)): ?>
                            <p class="text-xs italic opacity-40">No view data yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body py-4">
                    <h3 class="font-bold text-sm uppercase opacity-50 mb-4 tracking-widest">Project Health</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php if (!empty($orgProjectsByStatus)): ?>
                            <?php foreach ($orgProjectsByStatus as $status): ?>
                                <div class="badge badge-lg p-4 badge-outline gap-2">
                                    <span class="opacity-60 text-xs font-bold"><?= strtoupper(htmlspecialchars($status['project_status'] ?: 'UNSET')) ?></span>
                                    <span class="font-black"><?= $status['count'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-xs italic opacity-40">No projects to display status.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="card bg-base-100 shadow-xl border-l-8 border-primary hover:shadow-2xl transition-all">
                <div class="card-body">
                    <h2 class="card-title text-2xl font-bold mb-4">Project Management</h2>
                    <p class="text-base-content/60 mb-6">Create, edit, and maintain your real estate listings and image galleries.</p>
                    <div class="card-actions">
                        <a href="projects.php" class="btn btn-primary btn-block rounded-xl">
                            Go to Projects List
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl border-l-8 border-warning hover:shadow-2xl transition-all">
                <div class="card-body">
                    <h2 class="card-title text-2xl font-bold mb-4">Referral Bounties</h2>
                    <p class="text-base-content/60 mb-6">Manage and reward your referral network and incoming leads.</p>
                    <div class="card-actions">
                        <a href="manage_leads.php" class="btn btn-warning btn-block rounded-xl">
                            Manage Bounties
                            <i class="fas fa-coins ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl border-l-8 border-info hover:shadow-2xl transition-all">
                <div class="card-body">
                    <h2 class="card-title text-2xl font-bold mb-4">Property Enquiries</h2>
                    <p class="text-base-content/60 mb-6">Respond to direct messages from interested buyers about your listings.</p>
                    <div class="card-actions">
                        <a href="manage_enquiries.php" class="btn btn-info btn-block rounded-xl">
                            View Enquiries
                            <i class="fas fa-envelope ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl border-l-8 border-secondary hover:shadow-2xl transition-all">
                <div class="card-body">
                    <h2 class="card-title text-2xl font-bold mb-4">Team Administration</h2>
                    <p class="text-base-content/60 mb-6">Manage user accounts and permissions for your organization's staff.</p>
                    <div class="card-actions">
                        <a href="manage_users.php" class="btn btn-secondary btn-block rounded-xl">
                            Manage Organization Users
                            <i class="fas fa-users-cog ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl border-l-8 border-accent hover:shadow-2xl transition-all md:col-span-2">
                <div class="card-body flex-row items-center justify-between">
                    <div>
                        <h2 class="card-title text-2xl font-bold">Company Profile</h2>
                        <p class="text-base-content/60">Update your business address, contact info, and licensing details.</p>
                    </div>
                    <div class="card-actions">
                        <a href="edit_organization.php" class="btn btn-accent px-8 rounded-xl">
                            Edit Profile
                            <i class="fas fa-edit ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 shadow-xl border-l-8 border-neutral hover:shadow-2xl transition-all md:col-span-2">
                <div class="card-body flex-row items-center justify-between">
                    <div>
                        <h2 class="card-title text-2xl font-bold">Audit & Logs</h2>
                        <p class="text-base-content/60">Track all staff actions and system changes within your organization.</p>
                    </div>
                    <div class="card-actions">
                        <a href="organization_activity.php" class="btn btn-neutral px-8 rounded-xl">
                            View Logs
                            <i class="fas fa-history ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
