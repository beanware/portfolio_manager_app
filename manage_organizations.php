<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Only super_admin can manage organizations
requireRole('super_admin');

$success = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $action = $_POST['action'] ?? '';
        
        // ADD Organization
        if ($action === 'add') {
            $name = sanitizeInput($_POST['org_name']);
            if (empty($name)) {
                $error = "Organization name is required.";
            } else {
                try {
                    $stmt = $connection->prepare("INSERT INTO organizations (organization_name) VALUES (?)");
                    $stmt->bind_param("s", $name);
                    $stmt->execute();
                    $success = "Organization '$name' added successfully.";
                    logActivity('Created Organization', 'Organization', $stmt->insert_id);
                    $stmt->close();
                } catch (mysqli_sql_exception $e) {
                    $error = (strpos($e->getMessage(), 'Duplicate entry') !== false) 
                        ? "An organization with this name already exists." 
                        : "Error adding organization: " . $e->getMessage();
                }
            }
        }
        
        // EDIT Organization
        elseif ($action === 'edit') {
            $id = intval($_POST['org_id']);
            $name = sanitizeInput($_POST['org_name']);
            if (empty($name)) {
                $error = "Organization name is required.";
            } else {
                try {
                    $stmt = $connection->prepare("UPDATE organizations SET organization_name = ? WHERE organization_id = ?");
                    $stmt->bind_param("si", $name, $id);
                    $stmt->execute();
                    $success = "Organization updated successfully.";
                    logActivity('Updated Organization', 'Organization', $id);
                    $stmt->close();
                } catch (mysqli_sql_exception $e) {
                    $error = "Error updating organization: " . $e->getMessage();
                }
            }
        }
        
        // DELETE Organization
        elseif ($action === 'delete') {
            $id = intval($_POST['org_id']);
            try {
                // First check if it's the default organization (ID 1 usually)
                if ($id == 1) {
                    $error = "Cannot delete the default organization.";
                } else {
                    $stmt = $connection->prepare("DELETE FROM organizations WHERE organization_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $success = "Organization deleted successfully.";
                    logActivity('Deleted Organization', 'Organization', $id);
                    $stmt->close();
                }
            } catch (mysqli_sql_exception $e) {
                $error = "Error deleting organization. It may have associated projects or users. " . $e->getMessage();
            }
        }
    }
}

// Fetch Organizations
$organizations = [];
try {
    $result = $connection->query("SELECT * FROM organizations ORDER BY organization_id ASC");
    $organizations = $result->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    $error = "Failed to fetch organizations: " . $e->getMessage();
}

$csrfToken = generateCsrfToken();
?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-4xl font-black text-base-content leading-tight">Manage Organizations</h1>
                <p class="text-base-content/60">Add, edit, or remove organizations from the system.</p>
            </div>
            <div class="flex gap-2">
                <a href="admin_dashboard.php" class="btn btn-ghost btn-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Dashboard
                </a>
                <button onclick="document.getElementById('modal-add-org').showModal()" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-2"></i> Add Organization
                </button>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success shadow-lg mb-6">
                <i class="fas fa-check-circle text-2xl"></i>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error shadow-lg mb-6">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-base-100 rounded-3xl shadow-sm border border-base-300 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr class="bg-base-200">
                            <th>ID</th>
                            <th>Organization Name</th>
                            <th>Created At</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($organizations as $org): ?>
                        <tr>
                            <td class="font-mono text-xs opacity-50"><?= $org['organization_id'] ?></td>
                            <td>
                                <div class="font-bold"><?= htmlspecialchars($org['organization_name']) ?></div>
                            </td>
                            <td class="text-sm opacity-60">
                                <?= date('M d, Y', strtotime($org['created_at'])) ?>
                            </td>
                            <td class="text-right flex justify-end gap-2">
                                <button onclick="openEditModal(<?= $org['organization_id'] ?>, '<?= addslashes($org['organization_name']) ?>')" 
                                        class="btn btn-ghost btn-xs text-info">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($org['organization_id'] != 1): ?>
                                <button onclick="openDeleteModal(<?= $org['organization_id'] ?>, '<?= addslashes($org['organization_name']) ?>')" 
                                        class="btn btn-ghost btn-xs text-error">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($organizations)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-10 opacity-50">No organizations found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Organization Modal -->
<dialog id="modal-add-org" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Add New Organization</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="add">
            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text font-bold">Organization Name</span>
                </label>
                <input type="text" name="org_name" required placeholder="e.g. Skyline Realty" class="input input-bordered w-full" />
            </div>
            <div class="modal-action">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Organization</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<!-- Edit Organization Modal -->
<dialog id="modal-edit-org" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Edit Organization</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="org_id" id="edit-org-id">
            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text font-bold">Organization Name</span>
                </label>
                <input type="text" name="org_name" id="edit-org-name" required class="input input-bordered w-full" />
            </div>
            <div class="modal-action">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Organization</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<!-- Delete Confirmation Modal -->
<dialog id="modal-delete-org" class="modal">
    <div class="modal-box border-2 border-error/20">
        <h3 class="font-bold text-lg text-error flex items-center gap-2">
            <i class="fas fa-trash"></i> Delete Organization?
        </h3>
        <p class="py-4">
            Are you sure you want to delete <span id="delete-org-name" class="font-bold text-base-content"></span>? 
            This action cannot be undone and may fail if projects or users are still assigned to it.
        </p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="org_id" id="delete-org-id">
            <div class="modal-action">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-error text-white">Yes, Delete Forever</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<script>
function openEditModal(id, name) {
    document.getElementById('edit-org-id').value = id;
    document.getElementById('edit-org-name').value = name;
    document.getElementById('modal-edit-org').showModal();
}

function openDeleteModal(id, name) {
    document.getElementById('delete-org-id').value = id;
    document.getElementById('delete-org-name').innerText = name;
    document.getElementById('modal-delete-org').showModal();
}
</script>

<?php include 'footer.php'; ?>
