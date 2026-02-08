<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Authenticate and authorize admin access
requireRole('admin');

$currentUser = getCurrentUser();
$organization_id = $currentUser['organization_id'];
$isSuperAdmin = hasAnyRole(['super_admin']);

// If the user has no organization, block access
if (!$organization_id && !$isSuperAdmin) {
    safeRedirect("403.php?message=" . urlencode("You are not associated with any organization."));
}

// Function to generate a unique slug
function generateSlug($string, $connection, $organization_id) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    $original_slug = $slug;
    $count = 1;
    
    while (true) {
        $stmt = $connection->prepare("SELECT COUNT(*) FROM projects WHERE project_slug = ? AND organization_id = ?");
        $stmt->bind_param("si", $slug, $organization_id);
        $stmt->execute();
        $stmt->bind_result($num_rows);
        $stmt->fetch();
        $stmt->close();
        
        if ($num_rows == 0) break;
        $slug = $original_slug . '-' . $count;
        $count++;
    }
    return $slug;
}

// Fetch Enum Values for filters/form
function getEnumValues($connection, $table, $column) {
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $connection->query($query);
    if ($row = $result->fetch_assoc()) {
        preg_match("/enum\('(.*)'\)/", $row['Type'], $matches);
        if (isset($matches[1])) {
            return explode("','", $matches[1]);
        }
    }
    return [];
}

$projectTypes = getEnumValues($connection, 'projects', 'project_type');
$projectStatuses = getEnumValues($connection, 'projects', 'project_status');

$success = '';
$error = '';

// Handle Form Submission (Add Project)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $projectName = sanitizeInput($_POST['project_name']);
        $projectDescription = $_POST['project_description']; // Keep HTML if needed, or sanitize
        $projectLocation = sanitizeInput($_POST['project_location']);
        $projectDate = $_POST['project_date'];
        $projectType = sanitizeInput($_POST['project_type']);
        $projectStatus = sanitizeInput($_POST['project_status'] ?? 'draft');
        $targetOrgId = $isSuperAdmin ? intval($_POST['organization_id']) : $organization_id;

        if ($projectName) {
            try {
                $projectSlug = generateSlug($projectName, $connection, $targetOrgId);
                $stmt = $connection->prepare('INSERT INTO projects (project_name, project_slug, project_description, project_location, project_date, project_type, project_status, organization_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param("sssssssi", $projectName, $projectSlug, $projectDescription, $projectLocation, $projectDate, $projectType, $projectStatus, $targetOrgId);
                $stmt->execute();
                $projectId = $connection->insert_id;
                $stmt->close();

                // Handle Main Image
                if (!empty($_FILES['main_image']['name'])) {
                    $mainImageTitle = sanitizeInput($_POST['main_image_title'] ?? $projectName);
                    $filename = time() . '_' . basename($_FILES['main_image']['name']);
                    $mainImagePath = 'uploads/main/' . $filename;
                    
                    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $mainImagePath)) {
                        $stmt = $connection->prepare('INSERT INTO mainimages (project_id, image_title, image_path, organization_id) VALUES (?, ?, ?, ?)');
                        $stmt->bind_param("issi", $projectId, $mainImageTitle, $mainImagePath, $targetOrgId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                $success = "Project created successfully.";
                logActivity('Created Project', 'Project', $projectId);
            } catch (mysqli_sql_exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Search & Filtering
$searchTerm = $_GET['search'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$where = ["1=1"];
$params = [];
$types = "";

if (!$isSuperAdmin) {
    $where[] = "p.organization_id = ?";
    $params[] = $organization_id;
    $types .= "i";
}

if ($searchTerm) {
    $where[] = "(p.project_name LIKE ? OR p.project_location LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $types .= "ss";
}

if ($filterType) {
    $where[] = "p.project_type = ?";
    $params[] = $filterType;
    $types .= "s";
}

if ($filterStatus) {
    $where[] = "p.project_status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

// Fetch Projects with Organization Info
$sql = "SELECT p.*, o.organization_name, mi.image_path as main_image 
        FROM projects p 
        LEFT JOIN organizations o ON p.organization_id = o.organization_id
        LEFT JOIN mainimages mi ON p.project_id = mi.project_id
        WHERE " . implode(" AND ", $where) . " 
        ORDER BY p.project_id DESC";

$stmt = $connection->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch Organizations for Super Admin
$organizations = [];
if ($isSuperAdmin) {
    $organizations = $connection->query("SELECT organization_id, organization_name FROM organizations")->fetch_all(MYSQLI_ASSOC);
}

$csrfToken = generateCsrfToken();
?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-4xl font-black text-base-content">Project Portfolio</h1>
                <p class="text-base-content/60">Manage your listings, images, and property details.</p>
            </div>
            <div class="flex gap-2">
                <button onclick="document.getElementById('modal-add-project').showModal()" class="btn btn-primary rounded-xl shadow-lg">
                    <i class="fas fa-plus mr-2"></i> Add New Project
                </button>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success shadow-lg mb-6">
                <i class="fas fa-check-circle"></i>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error shadow-lg mb-6">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <div class="card bg-base-100 shadow-sm border border-base-300 mb-8">
            <div class="card-body p-4">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <div class="form-control flex-grow min-w-[200px]">
                        <label class="label"><span class="label-text-alt font-bold">Search</span></label>
                        <div class="join w-full">
                            <input type="text" name="search" placeholder="Project name or location..." 
                                   class="input input-bordered join-item w-full" value="<?= htmlspecialchars($searchTerm) ?>" />
                            <button type="submit" class="btn btn-primary join-item"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    
                    <div class="form-control w-full md:w-auto">
                        <label class="label"><span class="label-text-alt font-bold">Type</span></label>
                        <select name="type" class="select select-bordered" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <?php foreach ($projectTypes as $type): ?>
                                <option value="<?= $type ?>" <?= $filterType === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-control w-full md:w-auto">
                        <label class="label"><span class="label-text-alt font-bold">Status</span></label>
                        <select name="status" class="select select-bordered" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <?php foreach ($projectStatuses as $status): ?>
                                <option value="<?= $status ?>" <?= $filterStatus === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <a href="projects.php" class="btn btn-ghost">Reset</a>
                </form>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr class="bg-base-200">
                            <th>Project</th>
                            <th>Location</th>
                            <th>Type / Status</th>
                            <?php if ($isSuperAdmin): ?><th>Organization</th><?php endif; ?>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr class="hover:bg-base-200/50 transition-colors">
                            <td>
                                <div class="flex items-center gap-4">
                                    <div class="mask mask-squircle w-16 h-16 bg-base-200 overflow-hidden shadow-inner">
                                        <img src="<?= htmlspecialchars($project['main_image'] ?: 'uploads/main/default.jpg') ?>" 
                                             class="w-full h-full object-cover" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-lg"><?= htmlspecialchars($project['project_name']) ?></div>
                                        <div class="text-xs opacity-50 font-mono"><?= htmlspecialchars($project['project_slug']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm">
                                <i class="fas fa-map-marker-alt text-primary mr-1"></i>
                                <?= htmlspecialchars($project['project_location'] ?: 'Not Set') ?>
                            </td>
                            <td>
                                <div class="flex flex-col gap-1">
                                    <span class="badge badge-ghost badge-sm"><?= ucfirst($project['project_type']) ?></span>
                                    <span class="badge badge-sm <?= $project['project_status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= ucfirst($project['project_status']) ?>
                                    </span>
                                </div>
                            </td>
                            <?php if ($isSuperAdmin): ?>
                            <td class="text-sm font-semibold">
                                <?= htmlspecialchars($project['organization_name'] ?: 'System') ?>
                            </td>
                            <?php endif; ?>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="project_details.php?id=<?= $project['project_id'] ?>" class="btn btn-ghost btn-xs text-primary" title="View Public Page">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <a href="edit_project.php?project_id=<?= $project['project_id'] ?>" class="btn btn-ghost btn-xs text-info" title="Edit Content">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="openDeleteModal(<?= $project['project_id'] ?>, '<?= addslashes($project['project_name']) ?>')" 
                                            class="btn btn-ghost btn-xs text-error" title="Delete Project">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="<?= $isSuperAdmin ? 5 : 4 ?>" class="text-center py-20 opacity-40 italic">
                                No projects found matching your criteria.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Project Modal -->
<dialog id="modal-add-project" class="modal">
    <div class="modal-box max-w-4xl">
        <h3 class="font-bold text-2xl mb-6">Add New Property Project</h3>
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Project Name *</span></label>
                        <input type="text" name="project_name" required class="input input-bordered" placeholder="e.g. Skyline Apartments" />
                    </div>
                    
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Location</span></label>
                        <input type="text" name="project_location" class="input input-bordered" placeholder="City, Neighborhood" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold">Type</span></label>
                            <select name="project_type" class="select select-bordered">
                                <?php foreach ($projectTypes as $type): ?>
                                    <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold">Status</span></label>
                            <select name="project_status" class="select select-bordered">
                                <?php foreach ($projectStatuses as $status): ?>
                                    <option value="<?= $status ?>"><?= ucfirst($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Date</span></label>
                        <input type="date" name="project_date" class="input input-bordered" />
                    </div>

                    <?php if ($isSuperAdmin): ?>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Assign to Organization</span></label>
                        <select name="organization_id" class="select select-bordered">
                            <?php foreach ($organizations as $org): ?>
                                <option value="<?= $org['organization_id'] ?>"><?= htmlspecialchars($org['organization_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Media & Description -->
                <div class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold">Description</span></label>
                        <textarea name="project_description" class="textarea textarea-bordered h-32" placeholder="Write about the project..."></textarea>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-bold">Main Image</span>
                            <span class="label-text-alt opacity-50">Cover photo</span>
                        </label>
                        <input type="file" name="main_image" class="file-input file-input-bordered w-full" accept="image/*" />
                        <input type="text" name="main_image_title" class="input input-bordered input-sm mt-2" placeholder="Image caption" />
                    </div>
                </div>
            </div>

            <div class="modal-action">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary px-10">Create Project</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Delete Project Modal -->
<dialog id="modal-delete-project" class="modal">
    <div class="modal-box border-2 border-error/20">
        <h3 class="font-bold text-lg text-error flex items-center gap-2">
            <i class="fas fa-trash-alt"></i> Delete Project?
        </h3>
        <p class="py-4 text-base-content/80">
            Are you sure you want to delete <span id="delete-project-name" class="font-bold text-base-content"></span>? 
            This will also permanently remove all associated gallery images. This action cannot be undone.
        </p>
        <form action="delete_project.php" method="POST">
            <input type="hidden" name="project_id" id="delete-project-id">
            <div class="modal-action">
                <form method="dialog"><button class="btn btn-ghost">Cancel</button></form>
                <button type="submit" class="btn btn-error text-white">Yes, Delete Project</button>
            </div>
        </form>
    </div>
</dialog>

<script>
function openDeleteModal(id, name) {
    document.getElementById('delete-project-id').value = id;
    document.getElementById('delete-project-name').innerText = name;
    document.getElementById('modal-delete-project').showModal();
}
</script>

<?php include 'footer.php'; ?>
