<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Access: super_admin or admin
$currentUser = getCurrentUser();
if (!hasAnyRole(['super_admin', 'admin'])) {
    safeRedirect('403.php');
}

$isSuperAdmin = hasAnyRole(['super_admin']);
$orgId = $isSuperAdmin ? null : $currentUser['organization_id'];

$success = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $action = $_POST['action'] ?? '';
        
        // ADD User
        if ($action === 'add') {
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $displayName = sanitizeInput($_POST['display_name']);
            $role = sanitizeInput($_POST['role']);
            $targetOrgId = $isSuperAdmin ? intval($_POST['organization_id']) : $orgId;

            $regResult = registerUser($username, $email, $password, $displayName, $targetOrgId, $role);
            if ($regResult['success']) {
                $success = "User '$username' created successfully.";
            } else {
                $error = $regResult['message'];
            }
        }
        
        // EDIT User
        elseif ($action === 'edit') {
            $id = intval($_POST['user_id']);
            $displayName = sanitizeInput($_POST['display_name']);
            $role = sanitizeInput($_POST['role']);
            $email = sanitizeInput($_POST['email']);
            
            // Check permissions for this specific user
            $canEdit = false;
            if ($isSuperAdmin) {
                $canEdit = true;
            } else {
                // Check if user belongs to the same organization
                $stmt = $connection->prepare("SELECT organization_id FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $userToEdit = $result->fetch_assoc();
                if ($userToEdit && $userToEdit['organization_id'] == $orgId) {
                    $canEdit = true;
                }
                $stmt->close();
            }

            if ($canEdit) {
                try {
                    $stmt = $connection->prepare("UPDATE users SET display_name = ?, role = ?, email = ? WHERE user_id = ?");
                    $stmt->bind_param("sssi", $displayName, $role, $email, $id);
                    $stmt->execute();
                    $success = "User updated successfully.";
                    logActivity('Updated User', 'User', $id);
                    $stmt->close();
                    
                    // Update password if provided
                    if (!empty($_POST['password'])) {
                        $passHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $connection->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                        $stmt->bind_param("si", $passHash, $id);
                        $stmt->execute();
                        $stmt->close();
                    }
                } catch (mysqli_sql_exception $e) {
                    $error = "Error updating user: " . $e->getMessage();
                }
            } else {
                $error = "Access denied. You cannot edit this user.";
            }
        }
        
        // DELETE User
        elseif ($action === 'delete') {
            $id = intval($_POST['user_id']);
            if ($id == $currentUser['id']) {
                $error = "You cannot delete your own account.";
            } else {
                try {
                    $stmt = $connection->prepare("DELETE FROM users WHERE user_id = ? " . ($isSuperAdmin ? "" : "AND organization_id = $orgId"));
                    $stmt->execute();
                    if ($stmt->affected_rows > 0) {
                        $success = "User deleted successfully.";
                        logActivity('Deleted User', 'User', $id);
                    } else {
                        $error = "User not found or access denied.";
                    }
                    $stmt->close();
                } catch (mysqli_sql_exception $e) {
                    $error = "Error deleting user: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Users
$users = [];
try {
    $sql = "SELECT u.*, o.organization_name 
            FROM users u 
            LEFT JOIN organizations o ON u.organization_id = o.organization_id";
    if (!$isSuperAdmin) {
        $sql .= " WHERE u.organization_id = " . intval($orgId);
    }
    $sql .= " ORDER BY u.user_id DESC";
    
    $result = $connection->query($sql);
    $users = $result->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    $error = "Failed to fetch users: " . $e->getMessage();
}

// Fetch Organizations for dropdown (super_admin only)
$organizations = [];
if ($isSuperAdmin) {
    $result = $connection->query("SELECT organization_id, organization_name FROM organizations ORDER BY organization_name ASC");
    $organizations = $result->fetch_all(MYSQLI_ASSOC);
}

$csrfToken = generateCsrfToken();
?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-4xl font-black text-base-content leading-tight">Manage Users</h1>
                <p class="text-base-content/60">
                    <?= $isSuperAdmin ? 'System-wide user administration.' : 'Manage users for ' . htmlspecialchars($users[0]['organization_name'] ?? 'your organization') . '.' ?>
                </p>
            </div>
            <div class="flex gap-2">
                <a href="<?= $isSuperAdmin ? 'admin_dashboard.php' : 'organization_admin_dashboard.php' ?>" class="btn btn-ghost btn-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Dashboard
                </a>
                <button onclick="document.getElementById('modal-add-user').showModal()" class="btn btn-primary btn-sm">
                    <i class="fas fa-user-plus mr-2"></i> Add User
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
                            <th>User</th>
                            <th>Role</th>
                            <th>Organization</th>
                            <th>Joined</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="avatar placeholder">
                                        <div class="bg-base-300 text-base-content rounded-full w-10 h-10 font-bold">
                                            <?= strtoupper(substr($user['display_name'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-bold"><?= htmlspecialchars($user['display_name']) ?></div>
                                        <div class="text-xs opacity-50">@<?= htmlspecialchars($user['username']) ?> | <?= htmlspecialchars($user['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-sm <?= $user['role'] === 'super_admin' ? 'badge-error' : ($user['role'] === 'admin' ? 'badge-primary' : 'badge-ghost') ?>">
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                            </td>
                            <td class="text-sm">
                                <?= htmlspecialchars($user['organization_name'] ?? 'System') ?>
                            </td>
                            <td class="text-xs opacity-60">
                                <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="text-right flex justify-end gap-1">
                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" 
                                        class="btn btn-ghost btn-xs text-info">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['user_id'] != $currentUser['id']): ?>
                                <button onclick="openDeleteModal(<?= $user['user_id'] ?>, '<?= addslashes($user['username']) ?>')" 
                                        class="btn btn-ghost btn-xs text-error">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<dialog id="modal-add-user" class="modal">
    <div class="modal-box max-w-2xl">
        <h3 class="font-bold text-lg mb-6">Create New User</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Username</span></label>
                    <input type="text" name="username" required class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Display Name</span></label>
                    <input type="text" name="display_name" required class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Email</span></label>
                    <input type="email" name="email" required class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Password</span></label>
                    <input type="password" name="password" required class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Role</span></label>
                    <select name="role" class="select select-bordered w-full">
                        <option value="viewer">Viewer</option>
                        <option value="editor">Editor</option>
                        <option value="admin">Organization Admin</option>
                        <?php if ($isSuperAdmin): ?>
                        <option value="super_admin">System Super Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if ($isSuperAdmin): ?>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Organization</span></label>
                    <select name="organization_id" class="select select-bordered w-full">
                        <?php foreach ($organizations as $org): ?>
                            <option value="<?= $org['organization_id'] ?>"><?= htmlspecialchars($org['organization_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="modal-action mt-8">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User Account</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Edit User Modal -->
<dialog id="modal-edit-user" class="modal">
    <div class="modal-box max-w-2xl">
        <h3 class="font-bold text-lg mb-6">Edit User Profile</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit-user-id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Username</span></label>
                    <input type="text" id="edit-username" disabled class="input input-bordered opacity-50" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Display Name</span></label>
                    <input type="text" name="display_name" id="edit-display-name" required class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Email</span></label>
                    <input type="email" name="email" id="edit-email" required class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Role</span></label>
                    <select name="role" id="edit-role" class="select select-bordered w-full">
                        <option value="viewer">Viewer</option>
                        <option value="editor">Editor</option>
                        <option value="admin">Organization Admin</option>
                        <?php if ($isSuperAdmin): ?>
                        <option value="super_admin">System Super Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-control md:col-span-2">
                    <label class="label">
                        <span class="label-text font-bold">New Password</span>
                        <span class="label-text-alt opacity-50 text-xs italic">Leave blank to keep current</span>
                    </label>
                    <input type="password" name="password" placeholder="••••••••" class="input input-bordered" />
                </div>
            </div>

            <div class="modal-action mt-8">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Delete User Modal -->
<dialog id="modal-delete-user" class="modal">
    <div class="modal-box border-2 border-error/20">
        <h3 class="font-bold text-lg text-error">Delete User Account?</h3>
        <p class="py-4">Are you sure you want to delete <span id="delete-username" class="font-bold"></span>? This action is permanent.</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="delete-user-id">
            <div class="modal-action">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-error text-white">Delete User</button>
            </div>
        </form>
    </div>
</dialog>

<script>
function openEditModal(user) {
    document.getElementById('edit-user-id').value = user.user_id;
    document.getElementById('edit-username').value = user.username;
    document.getElementById('edit-display-name').value = user.display_name;
    document.getElementById('edit-email').value = user.email;
    document.getElementById('edit-role').value = user.role;
    document.getElementById('modal-edit-user').showModal();
}

function openDeleteModal(id, username) {
    document.getElementById('delete-user-id').value = id;
    document.getElementById('delete-username').innerText = '@' + username;
    document.getElementById('modal-delete-user').showModal();
}
</script>

<?php include 'footer.php'; ?>
