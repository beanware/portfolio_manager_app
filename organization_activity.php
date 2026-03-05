<?php
define('ACCESS_ALLOWED', true);
require_once 'connection.php';
require_once 'includes/auth_functions.php';

// Must be admin
requireRole('admin');

$user = getCurrentUser();
$orgId = $user['organization_id'];

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch logs
$logs = [];
try {
    $stmt = $connection->prepare("
        SELECT al.*, u.display_name, u.username 
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.user_id
        WHERE al.organization_id = ?
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $orgId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Total count for pagination
    $stmt = $connection->prepare("SELECT COUNT(*) FROM audit_log WHERE organization_id = ?");
    $stmt->bind_param("i", $orgId);
    $stmt->execute();
    $totalLogs = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    $totalPages = ceil($totalLogs / $limit);
} catch (mysqli_sql_exception $e) {
    error_log("Failed to fetch logs: " . $e->getMessage());
}

include 'header.php';
?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex items-center gap-4 mb-8">
            <a href="organization_admin_dashboard.php" class="btn btn-ghost btn-sm">
                <i class="fas fa-arrow-left mr-2"></i> Dashboard
            </a>
            <h1 class="text-3xl font-black">Activity Logs</h1>
        </div>

        <div class="bg-base-100 rounded-3xl shadow-sm border border-base-300 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr class="bg-base-200">
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-xs font-mono">
                                <?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <div class="font-bold"><?= htmlspecialchars($log['display_name'] ?? 'System') ?></div>
                                <div class="text-xs opacity-50">@<?= htmlspecialchars($log['username'] ?? 'sys') ?></div>
                            </td>
                            <td>
                                <span class="badge badge-outline"><?= htmlspecialchars($log['action']) ?></span>
                            </td>
                            <td>
                                <div class="text-sm"><?= htmlspecialchars($log['entity_type'] ?? '-') ?></div>
                                <div class="text-xs opacity-50">ID: <?= htmlspecialchars($log['entity_id'] ?? '-') ?></div>
                            </td>
                            <td class="text-sm italic opacity-70"><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                            <td class="text-xs opacity-60"><?= htmlspecialchars($log['ip_address']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-20 opacity-50">No activity logs found for your organization.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-8">
            <div class="join">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="join-item btn <?= $page == $i ? 'btn-active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>