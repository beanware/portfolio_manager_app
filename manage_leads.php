<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Require super_admin or organization_admin role
requireRole(['super_admin', 'organization_admin']);

$currentUser = getCurrentUser();
$organization_id = $currentUser['organization_id'];
$isSuperAdmin = hasAnyRole(['super_admin']);

require_once 'includes/send_email.php';

// Handle Status/Reward Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_lead') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $lead_id = intval($_POST['lead_id']);
        $status = $_POST['status'];
        $reward = floatval($_POST['reward_amount']);
        $notes = $_POST['notes'];

        try {
            // Fetch lead and project info before update for email
            $leadQuery = "SELECT l.*, p.project_name FROM leads l JOIN projects p ON l.project_id = p.project_id WHERE l.lead_id = $lead_id";
            $leadData = $connection->query($leadQuery)->fetch_assoc();

            $stmt = $connection->prepare("UPDATE leads SET status = ?, reward_amount = ?, notes = ? WHERE lead_id = ?");
            $stmt->bind_param("sdsi", $status, $reward, $notes, $lead_id);
            if ($stmt->execute()) {
                $success = "Lead updated successfully.";
                logActivity('Updated Lead Status', 'Lead', $lead_id);

                // Send email to referrer if status changed
                if ($leadData && $leadData['status'] !== $status) {
                    $statusLabel = ucfirst(str_replace('_', ' ', $status));
                    $emailSubject = "Update on your referral: " . $leadData['project_name'];
                    $emailBody = "<h3>Hello {$leadData['referrer_name']},</h3>
                                  <p>There has been an update on the status of your referral for <strong>{$leadData['project_name']}</strong>.</p>
                                  <p><strong>Lead Name:</strong> {$leadData['buyer_name']}</p>
                                  <p><strong>New Status:</strong> <span style='color: #4c7cf3; font-weight: bold;'>{$statusLabel}</span></p>";
                    
                    if ($status === 'paid') {
                        $emailBody .= "<p>Your bounty of <strong>KES " . number_format($reward, 2) . "</strong> has been processed! Thank you for your partnership.</p>";
                    } elseif ($status === 'dead_end' || $status === 'rejected') {
                        $emailBody .= "<p>Unfortunately, this lead has been marked as {$statusLabel}. We appreciate your effort and hope to work with you on future referrals.</p>";
                    } else {
                        $emailBody .= "<p>We are currently processing this lead and will update you further as progress is made.</p>";
                    }

                    sendEmail($leadData['referrer_email'], $emailSubject, $emailBody);
                }
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $error = "Error updating lead: " . $e->getMessage();
        }
    }
}

// Fetch Leads
$where = $isSuperAdmin ? "1=1" : "l.organization_id = " . intval($organization_id);
$sql = "SELECT l.*, p.project_name, o.organization_name 
        FROM leads l 
        JOIN projects p ON l.project_id = p.project_id 
        JOIN organizations o ON l.organization_id = o.organization_id 
        WHERE $where 
        ORDER BY l.created_at DESC";

$result = $connection->query($sql);
$leads = $result->fetch_all(MYSQLI_ASSOC);

$csrfToken = generateCsrfToken();
?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-black text-base-content">Referral Bounties</h1>
                <p class="text-base-content/60">Track and reward users for providing interested buyer leads.</p>
            </div>
            <div class="badge badge-primary badge-lg p-4"><?= count($leads) ?> Total Leads</div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success shadow-lg mb-6">
                <i class="fas fa-check-circle"></i>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error shadow-lg mb-6">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <div class="card bg-base-100 shadow-xl overflow-hidden border border-base-300">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr class="bg-base-200">
                            <th>Referrer</th>
                            <th>Buyer Lead</th>
                            <th>Project / Organization</th>
                            <th>Status</th>
                            <th>Reward</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                        <tr class="hover:bg-base-200/50 transition-colors">
                            <td>
                                <div class="font-bold"><?= htmlspecialchars($lead['referrer_name']) ?></div>
                                <div class="text-xs opacity-50"><?= htmlspecialchars($lead['referrer_email']) ?></div>
                                <div class="text-xs opacity-50"><?= htmlspecialchars($lead['referrer_phone']) ?></div>
                            </td>
                            <td>
                                <div class="font-bold text-secondary"><?= htmlspecialchars($lead['buyer_name']) ?></div>
                                <div class="text-xs opacity-60"><?= htmlspecialchars($lead['buyer_contact']) ?></div>
                                <div class="text-[10px] mt-1 opacity-40"><?= date('M d, Y', strtotime($lead['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="text-sm font-semibold"><?= htmlspecialchars($lead['project_name']) ?></div>
                                <div class="text-xs opacity-50"><?= htmlspecialchars($lead['organization_name']) ?></div>
                            </td>
                            <td>
                                <?php 
                                    $statusClasses = [
                                        'pending' => 'badge-ghost',
                                        'verified' => 'badge-info',
                                        'deal_closed' => 'badge-success',
                                        'paid' => 'badge-primary',
                                        'rejected' => 'badge-error',
                                        'dead_end' => 'badge-error opacity-50'
                                    ];
                                ?>
                                <span class="badge <?= $statusClasses[$lead['status']] ?? 'badge-ghost' ?> badge-sm">
                                    <?= ucfirst(str_replace('_', ' ', $lead['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="font-black text-primary">
                                    <?= $lead['reward_amount'] > 0 ? 'KES ' . number_format($lead['reward_amount'], 2) : '-' ?>
                                </div>
                            </td>
                            <td class="text-right">
                                <button onclick="openUpdateModal(<?= htmlspecialchars(json_encode($lead)) ?>)" 
                                        class="btn btn-ghost btn-xs text-primary">
                                    <i class="fas fa-edit mr-1"></i> Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-20 opacity-40 italic">
                                No referral leads submitted yet.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Lead Modal -->
<dialog id="modal-update-lead" class="modal">
    <div class="modal-box max-w-lg">
        <h3 class="font-bold text-2xl mb-6">Update Referral Lead</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="update_lead">
            <input type="hidden" name="lead_id" id="edit-lead-id">
            
            <div class="form-control">
                <label class="label"><span class="label-text font-bold">Status</span></label>
                <select name="status" id="edit-status" class="select select-bordered w-full">
                    <option value="pending">Pending Verification</option>
                    <option value="verified">Verified Lead</option>
                    <option value="deal_closed">Deal Closed</option>
                    <option value="paid">Bounty Paid</option>
                    <option value="rejected">Rejected / Invalid</option>
                    <option value="dead_end">Dead End (Archive)</option>
                </select>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-bold">Reward Amount (Custom)</span></label>
                <div class="join">
                    <span class="join-item btn btn-disabled">KES</span>
                    <input type="number" step="0.01" name="reward_amount" id="edit-reward" class="input input-bordered join-item w-full" placeholder="0.00" />
                </div>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-bold">Internal Notes</span></label>
                <textarea name="notes" id="edit-notes" class="textarea textarea-bordered h-24" placeholder="Update progress, payment details, etc."></textarea>
            </div>

            <div class="modal-action">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary px-8">Save Changes</button>
            </div>
        </form>
    </div>
</dialog>

<script>
function openUpdateModal(lead) {
    document.getElementById('edit-lead-id').value = lead.lead_id;
    document.getElementById('edit-status').value = lead.status;
    document.getElementById('edit-reward').value = lead.reward_amount;
    document.getElementById('edit-notes').value = lead.notes || '';
    document.getElementById('modal-update-lead').showModal();
}
</script>

<?php include 'footer.php'; ?>