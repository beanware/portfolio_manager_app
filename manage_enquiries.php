<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Require super_admin or organization_admin role
requireRole(['super_admin', 'admin']);

$currentUser = getCurrentUser();
$organization_id = $currentUser['organization_id'];
$isSuperAdmin = hasAnyRole(['super_admin']);

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_enquiry') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $enquiry_id = intval($_POST['enquiry_id']);
        $status = $_POST['status'];

        try {
            $stmt = $connection->prepare("UPDATE enquiries SET status = ? WHERE enquiry_id = ?");
            $stmt->bind_param("si", $status, $enquiry_id);
            if ($stmt->execute()) {
                $success = "Enquiry updated successfully.";
                logActivity('Updated Enquiry Status', 'Enquiry', $enquiry_id);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $error = "Error updating enquiry: " . $e->getMessage();
        }
    }
}

// Fetch Enquiries
$where = $isSuperAdmin ? "1=1" : "e.organization_id = " . intval($organization_id);
$sql = "SELECT e.*, p.project_name, o.organization_name 
        FROM enquiries e 
        LEFT JOIN projects p ON e.project_id = p.project_id 
        JOIN organizations o ON e.organization_id = o.organization_id 
        WHERE $where 
        ORDER BY e.created_at DESC";

$result = $connection->query($sql);
$enquiries = $result->fetch_all(MYSQLI_ASSOC);

$csrfToken = generateCsrfToken();
?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-black text-base-content">Property Enquiries</h1>
                <p class="text-base-content/60">Manage direct messages from interested buyers.</p>
            </div>
            <div class="badge badge-primary badge-lg p-4"><?= count($enquiries) ?> Total Enquiries</div>
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
                            <th>Enquirer</th>
                            <th>Property</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enquiries as $enq): ?>
                        <tr class="hover:bg-base-200/50 transition-colors">
                            <td>
                                <div class="font-bold"><?= htmlspecialchars($enq['name']) ?></div>
                                <div class="text-xs opacity-50"><?= htmlspecialchars($enq['email']) ?></div>
                                <div class="text-xs opacity-50"><?= htmlspecialchars($enq['phone']) ?></div>
                                <div class="text-[10px] mt-1 opacity-40"><?= date('M d, Y H:i', strtotime($enq['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="text-sm font-semibold"><?= htmlspecialchars($enq['project_name'] ?: 'General') ?></div>
                                <div class="text-xs opacity-50"><?= htmlspecialchars($enq['organization_name']) ?></div>
                                <div class="text-xs font-bold mt-1"><?= htmlspecialchars($enq['subject']) ?></div>
                            </td>
                            <td class="max-w-xs overflow-hidden text-ellipsis italic text-sm">
                                "<?= htmlspecialchars($enq['message']) ?>"
                            </td>
                            <td>
                                <?php 
                                    $statusClasses = [
                                        'new' => 'badge-primary',
                                        'read' => 'badge-ghost',
                                        'responded' => 'badge-success',
                                        'archived' => 'badge-error opacity-50'
                                    ];
                                ?>
                                <span class="badge <?= $statusClasses[$enq['status']] ?? 'badge-ghost' ?> badge-sm">
                                    <?= ucfirst($enq['status']) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <button onclick="openUpdateEnquiryModal(<?= htmlspecialchars(json_encode($enq)) ?>)" 
                                        class="btn btn-ghost btn-xs text-primary">
                                    <i class="fas fa-eye mr-1"></i> View/Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($enquiries)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-20 opacity-40 italic">
                                No enquiries received yet.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Enquiry Modal -->
<dialog id="modal-update-enquiry" class="modal">
    <div class="modal-box max-w-2xl">
        <h3 class="font-bold text-2xl mb-6">Enquiry Details</h3>
        <div id="enquiry-details-view" class="space-y-4 mb-6 bg-base-200 p-6 rounded-2xl border border-base-300">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><strong>From:</strong> <span id="view-enq-name"></span></div>
                <div><strong>Email:</strong> <span id="view-enq-email"></span></div>
                <div><strong>Phone:</strong> <span id="view-enq-phone"></span></div>
                <div><strong>Date:</strong> <span id="view-enq-date"></span></div>
                <div class="col-span-2"><strong>Property:</strong> <span id="view-enq-property"></span></div>
                <div class="col-span-2"><strong>Subject:</strong> <span id="view-enq-subject"></span></div>
            </div>
            <div class="divider">Message</div>
            <div id="view-enq-message" class="italic whitespace-pre-wrap"></div>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="update_enquiry">
            <input type="hidden" name="enquiry_id" id="edit-enquiry-id">
            
            <div class="form-control">
                <label class="label"><span class="label-text font-bold">Status</span></label>
                <select name="status" id="edit-enq-status" class="select select-bordered w-full">
                    <option value="new">New</option>
                    <option value="read">Mark as Read</option>
                    <option value="responded">Mark as Responded</option>
                    <option value="archived">Archive</option>
                </select>
            </div>

            <div class="modal-action">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-ghost">Close</button>
                <button type="submit" class="btn btn-primary px-8">Save Changes</button>
            </div>
        </form>
    </div>
</dialog>

<script>
function openUpdateEnquiryModal(enq) {
    document.getElementById('edit-enquiry-id').value = enq.enquiry_id;
    document.getElementById('edit-enq-status').value = enq.status;
    
    document.getElementById('view-enq-name').innerText = enq.name;
    document.getElementById('view-enq-email').innerText = enq.email;
    document.getElementById('view-enq-phone').innerText = enq.phone || 'N/A';
    document.getElementById('view-enq-date').innerText = enq.created_at;
    document.getElementById('view-enq-property').innerText = enq.project_name || 'General Inquiry';
    document.getElementById('view-enq-subject').innerText = enq.subject || 'No Subject';
    document.getElementById('view-enq-message').innerText = enq.message;

    document.getElementById('modal-update-enquiry').showModal();
}
</script>

<?php include 'footer.php'; ?>