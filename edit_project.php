<?php
define('ACCESS_ALLOWED', true);
include 'header.php';

// Authenticate and authorize admin access
requireRole('admin');

$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$currentUser = getCurrentUser();
$organization_id = $currentUser['organization_id'];
$isSuperAdmin = hasAnyRole(['super_admin']);

if ($projectId <= 0) {
    safeRedirect("projects.php");
}

// Fetch Project Details
$stmt = $connection->prepare("SELECT * FROM projects WHERE project_id = ? " . ($isSuperAdmin ? "" : "AND organization_id = $organization_id"));
$stmt->bind_param("i", $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    safeRedirect("403.php?message=" . urlencode("Project not found or access denied."));
}

// Fetch Enum Values
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

// Fetch Images
$mainImage = $connection->query("SELECT * FROM mainimages WHERE project_id = $projectId")->fetch_assoc();
$carouselImages = $connection->query("SELECT * FROM carouselimages WHERE project_id = $projectId ORDER BY display_order ASC")->fetch_all(MYSQLI_ASSOC);

$success = '';
$error = '';

// Handle Image Deletion
if (isset($_GET['delete_image'])) {
    $imgId = intval($_GET['delete_image']);
    $imgType = $_GET['img_type'] ?? '';
    
    if ($imgType === 'carousel') {
        $stmt = $connection->prepare("DELETE FROM carouselimages WHERE image_id = ? AND project_id = ?");
        $stmt->bind_param("ii", $imgId, $projectId);
        $stmt->execute();
        $stmt->close();
        safeRedirect("edit_project.php?project_id=$projectId&success=Image+deleted");
    }
}

if (isset($_GET['success'])) $success = $_GET['success'];

$csrfToken = generateCsrfToken();
?>

<div class="bg-base-200 min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Breadcrumbs -->
        <div class="text-sm breadcrumbs mb-8">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="projects.php">Projects</a></li>
                <li>Edit Project</li>
                <li class="font-bold"><?= htmlspecialchars($project['project_name']) ?></li>
            </ul>
        </div>

        <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-black">Edit Project</h1>
            <div class="flex gap-2">
                <a href="project_details.php?id=<?= $projectId ?>" class="btn btn-ghost" target="_blank">
                    <i class="fas fa-eye mr-2"></i> View Public Page
                </a>
                <a href="projects.php" class="btn btn-outline">Cancel</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success shadow-lg mb-6">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Settings Form (66%) -->
            <div class="lg:col-span-2 space-y-8">
                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <h2 class="card-title text-2xl font-bold mb-6">Project Details</h2>
                        <form action="update_project.php" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="project_id" value="<?= $projectId ?>">

                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Project Name</span></label>
                                <input type="text" name="project_name" required value="<?= htmlspecialchars($project['project_name']) ?>" class="input input-bordered" />
                            </div>

                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Location</span></label>
                                <input type="text" name="project_location" value="<?= htmlspecialchars($project['project_location']) ?>" class="input input-bordered" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-bold">Type</span></label>
                                    <select name="project_type" class="select select-bordered">
                                        <?php foreach ($projectTypes as $type): ?>
                                            <option value="<?= $type ?>" <?= $project['project_type'] === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-bold">Status</span></label>
                                    <select name="project_status" class="select select-bordered">
                                        <?php foreach ($projectStatuses as $status): ?>
                                            <option value="<?= $status ?>" <?= $project['project_status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-bold">Date</span></label>
                                    <input type="date" name="project_date" value="<?= $project['project_date'] ?>" class="input input-bordered" />
                                </div>
                            </div>

                            <div class="form-control">
                                <label class="label"><span class="label-text font-bold">Description</span></label>
                                <textarea name="project_description" class="textarea textarea-bordered h-48"><?= htmlspecialchars($project['project_description']) ?></textarea>
                            </div>

                            <div class="card-actions justify-end mt-6">
                                <button type="submit" class="btn btn-primary px-10 rounded-full">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Gallery Management -->
                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="card-title text-2xl font-bold">Carousel Gallery</h2>
                            <button onclick="document.getElementById('modal-add-carousel').showModal()" class="btn btn-sm btn-outline btn-primary">
                                <i class="fas fa-plus mr-2"></i> Add Photos
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach ($carouselImages as $img): ?>
                            <div class="relative group rounded-xl overflow-hidden shadow-md aspect-square bg-base-200">
                                <img src="<?= htmlspecialchars($img['image_path']) ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <a href="edit_project.php?project_id=<?= $projectId ?>&delete_image=<?= $img['image_id'] ?>&img_type=carousel" 
                                       onclick="return confirm('Remove this image from gallery?')"
                                       class="btn btn-circle btn-error btn-sm text-white">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-black/50 p-1 text-[10px] text-white text-center truncate">
                                    <?= htmlspecialchars($img['image_title'] ?: 'No title') ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($carouselImages)): ?>
                                <div class="col-span-full py-10 text-center opacity-40 border-2 border-dashed border-base-300 rounded-xl">
                                    <i class="fas fa-images text-4xl mb-2"></i>
                                    <p>No gallery images yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Media Sidebar (33%) -->
            <div class="space-y-8">
                <!-- Main Image Management -->
                <div class="card bg-base-100 shadow-xl border border-base-300 overflow-hidden">
                    <div class="card-body">
                        <h3 class="font-bold text-lg mb-4">Cover Photo</h3>
                        <div class="relative rounded-2xl overflow-hidden shadow-lg mb-4 aspect-video bg-base-200">
                            <?php if ($mainImage): ?>
                                <img src="<?= htmlspecialchars($mainImage['image_path']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="flex flex-col items-center justify-center h-full opacity-30">
                                    <i class="fas fa-image text-5xl mb-2"></i>
                                    <span class="text-sm">No cover photo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button onclick="document.getElementById('modal-edit-main-image').showModal()" class="btn btn-outline btn-sm btn-block">
                            <?= $mainImage ? 'Change Image' : 'Upload Image' ?>
                        </button>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="alert shadow-lg bg-info/10 border-info/20 text-info-content">
                    <i class="fas fa-info-circle text-info"></i>
                    <div class="text-sm">
                        <h4 class="font-bold">Pro Tip</h4>
                        <p class="opacity-80">Images should be high-resolution (at least 1920x1080) for the best appearance on the public marketplace.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Gallery Photos -->
<dialog id="modal-add-carousel" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Add Gallery Photos</h3>
        <form action="update_project.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="project_id" value="<?= $projectId ?>">
            <input type="hidden" name="action" value="add_carousel">
            
            <div class="form-control mb-4">
                <label class="label"><span class="label-text">Select Images (Multiple allowed)</span></label>
                <input type="file" name="carousel_images[]" multiple required class="file-input file-input-bordered w-full" accept="image/*" />
            </div>

            <div class="modal-action">
                <form method="dialog"><button class="btn btn-ghost">Cancel</button></form>
                <button type="submit" class="btn btn-primary">Upload Gallery</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Modal: Edit Main Image -->
<dialog id="modal-edit-main-image" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Update Cover Photo</h3>
        <form action="update_project.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="project_id" value="<?= $projectId ?>">
            <input type="hidden" name="action" value="update_main_image">
            
            <div class="form-control mb-4">
                <label class="label"><span class="label-text">New Cover Image</span></label>
                <input type="file" name="main_image" required class="file-input file-input-bordered w-full" accept="image/*" />
            </div>

            <div class="form-control mb-4">
                <label class="label"><span class="label-text">Caption (Optional)</span></label>
                <input type="text" name="main_image_title" value="<?= htmlspecialchars($mainImage['image_title'] ?? '') ?>" class="input input-bordered" />
            </div>

            <div class="modal-action">
                <form method="dialog"><button class="btn btn-ghost">Cancel</button></form>
                <button type="submit" class="btn btn-primary">Update Photo</button>
            </div>
        </form>
    </div>
</dialog>

<?php include 'footer.php'; ?>
