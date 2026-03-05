<?php
define('ACCESS_ALLOWED', true);
require_once 'connection.php';
require_once 'includes/auth_functions.php';

// Only super_admin
requireRole('super_admin');

// Pagination
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch logs with Organization name
$logs = [];
try {
    $stmt = $connection->prepare("
        SELECT al.*, u.display_name, u.username, o.organization_name 
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.user_id
        LEFT JOIN organizations o ON al.organization_id = o.organization_id
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Total count for pagination
    $totalLogs = $connection->query("SELECT COUNT(*) FROM audit_log")->fetch_row()[0];
    $totalPages = ceil($totalLogs / $limit);
} catch (mysqli_sql_exception $e) {
    error_log("Failed to fetch system logs: " . $e->getMessage());
}

include 'header.php';
?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-[95%] mx-auto py-8">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="admin_dashboard.php" class="btn btn-ghost btn-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Dashboard
                </a>
                <h1 class="text-3xl font-black">System-Wide Audit Logs</h1>
            </div>
            <div class="badge badge-neutral p-4"><?= number_format($totalLogs) ?> Total Events</div>
        </div>

        <div class="bg-base-100 rounded-3xl shadow-sm border border-base-300 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full table-xs md:table-sm">
                    <thead>
                        <tr class="bg-base-200">
                            <th>Date & Time</th>
                            <th>Organization</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Details</th>
                            <th>Origin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-xs font-mono opacity-60">
                                <?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <span class="badge badge-ghost badge-sm font-bold">
                                    <?= htmlspecialchars($log['organization_name'] ?? 'System / Global') ?>
                                </span>
                            </td>
                            <td>
                                <div class="font-bold"><?= htmlspecialchars($log['display_name'] ?? 'System') ?></div>
                                <div class="text-[10px] opacity-50">@<?= htmlspecialchars($log['username'] ?? 'sys') ?></div>
                            </td>
                            <td>
                                <span class="badge badge-primary badge-sm"><?= htmlspecialchars($log['action']) ?></span>
                            </td>
                            <td>
                                <div class="font-bold text-[10px]"><?= htmlspecialchars($log['entity_type'] ?? '-') ?></div>
                                <div class="text-[10px] opacity-50">ID: <?= htmlspecialchars($log['entity_id'] ?? '-') ?></div>
                            </td>
                            <td class="max-w-xs overflow-hidden text-ellipsis whitespace-nowrap italic text-xs">
                                <?= htmlspecialchars($log['details'] ?? '-') ?>
                            </td>
                            <td class="text-[10px] opacity-40">
                                <?= htmlspecialchars($log['ip_address']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-8">
            <div class="join">
                <?php 
                $start = max(1, $page - 5);
                $end = min($totalPages, $page + 5);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="?page=<?= $i ?>" class="join-item btn btn-sm <?= $page == $i ? 'btn-active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>