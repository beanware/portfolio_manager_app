<?php
define('ACCESS_ALLOWED', true);
require_once 'connection.php';
require_once 'includes/auth_functions.php';

// Must be logged in and an admin
requireAuth();
requireRole('admin');

$user = getCurrentUser();
$orgId = $user['organization_id'];

$error = '';
$success = '';

// Fetch current organization details
try {
    $stmt = $connection->prepare("SELECT * FROM organizations WHERE organization_id = ?");
    $stmt->bind_param("i", $orgId);
    $stmt->execute();
    $result = $stmt->get_result();
    $organization = $result->fetch_assoc();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Failed to fetch organization: " . $e->getMessage());
    die("Database error occurred.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $org_name = sanitizeInput($_POST['organization_name'] ?? '');
        $address = sanitizeInput($_POST['company_address'] ?? '');
        $email = sanitizeInput($_POST['contact_email'] ?? '', 'email');
        $phone = sanitizeInput($_POST['contact_phone'] ?? '');
        $website = sanitizeInput($_POST['website'] ?? '', 'url');
        $license = sanitizeInput($_POST['license_number'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $tax_id = sanitizeInput($_POST['tax_id'] ?? '');
        
        $logo_path = $organization['logo_path'];

        // Handle Logo Upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = 'logo_' . $orgId . '_' . time() . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                    // Delete old logo if exists
                    if ($logo_path && file_exists($logo_path)) {
                        unlink($logo_path);
                    }
                    $logo_path = $targetPath;
                } else {
                    $error = "Failed to upload logo.";
                }
            } else {
                $error = "Invalid file type for logo. Only JPG, PNG, and WEBP allowed.";
            }
        }

        if (empty($error) && empty($org_name)) {
            $error = "Organization name is required.";
        }

        if (empty($error)) {
            try {
                $stmt = $connection->prepare("UPDATE organizations SET 
                    organization_name = ?, 
                    company_address = ?, 
                    contact_email = ?, 
                    contact_phone = ?, 
                    website = ?, 
                    license_number = ?, 
                    description = ?,
                    tax_id = ?,
                    logo_path = ?
                    WHERE organization_id = ?");
                
                $stmt->bind_param("sssssssssi", 
                    $org_name, $address, $email, $phone, 
                    $website, $license, $description, $tax_id, $logo_path, $orgId
                );
                
                if ($stmt->execute()) {
                    $success = "Organization details updated successfully.";
                    // Refresh data
                    $organization['organization_name'] = $org_name;
                    $organization['company_address'] = $address;
                    $organization['contact_email'] = $email;
                    $organization['contact_phone'] = $phone;
                    $organization['website'] = $website;
                    $organization['license_number'] = $license;
                    $organization['description'] = $description;
                    $organization['tax_id'] = $tax_id;
                    $organization['logo_path'] = $logo_path;
                    
                    logActivity('Updated Organization Details', 'Organization', $orgId);
                } else {
                    $error = "Failed to update organization.";
                }
                $stmt->close();
            } catch (Exception $e) {
                if ($connection->errno === 1062) {
                    $error = "An organization with this name already exists.";
                } else {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

$csrfToken = generateCsrfToken();
include 'header.php';
?>

<div class="bg-base-200 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4">
        <div class="flex items-center gap-4 mb-8">
            <a href="organization_admin_dashboard.php" class="btn btn-ghost btn-sm">
                <i class="fas fa-arrow-left mr-2"></i> Dashboard
            </a>
            <h1 class="text-3xl font-black">Edit Company Profile</h1>
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

        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body p-8">
                <form method="POST" action="edit_organization.php" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Logo Upload -->
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-bold">Company Logo</span></label>
                            <div class="flex items-center gap-6">
                                <div class="avatar">
                                    <div class="w-24 h-24 rounded-xl border-2 border-base-300 bg-base-200">
                                        <?php if (!empty($organization['logo_path']) && file_exists($organization['logo_path'])): ?>
                                            <img src="<?= htmlspecialchars($organization['logo_path']) ?>" alt="Logo">
                                        <?php else: ?>
                                            <div class="flex items-center justify-center h-full text-4xl font-black opacity-20">
                                                <?= strtoupper(substr($organization['organization_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="logo" class="file-input file-input-bordered file-input-primary w-full max-w-xs" accept="image/*" />
                                    <label class="label">
                                        <span class="label-text-alt opacity-50 text-xs">Recommended: Square PNG or WEBP, max 2MB.</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Org Name -->
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-bold">Company / Agency Name <span class="text-error">*</span></span></label>
                            <input type="text" name="organization_name" value="<?= htmlspecialchars($organization['organization_name']) ?>" class="input input-bordered w-full" required />
                        </div>

                        <!-- Email -->
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold">Business Email</span></label>
                            <input type="email" name="contact_email" value="<?= htmlspecialchars($organization['contact_email'] ?? '') ?>" class="input input-bordered w-full" />
                        </div>

                        <!-- Phone -->
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold">Phone Number</span></label>
                            <input type="text" name="contact_phone" value="<?= htmlspecialchars($organization['contact_phone'] ?? '') ?>" class="input input-bordered w-full" />
                        </div>

                        <!-- Website -->
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold">Website URL</span></label>
                            <input type="url" name="website" value="<?= htmlspecialchars($organization['website'] ?? '') ?>" class="input input-bordered w-full" />
                        </div>

                        <!-- License -->
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold">Real Estate License #</span></label>
                            <input type="text" name="license_number" value="<?= htmlspecialchars($organization['license_number'] ?? '') ?>" class="input input-bordered w-full" />
                        </div>
                        
                        <!-- Tax ID -->
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-bold">Tax ID / Business Registration #</span></label>
                            <input type="text" name="tax_id" value="<?= htmlspecialchars($organization['tax_id'] ?? '') ?>" class="input input-bordered w-full" />
                        </div>

                        <!-- Address -->
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-bold">Physical Address</span></label>
                            <textarea name="company_address" class="textarea textarea-bordered h-24"><?= htmlspecialchars($organization['company_address'] ?? '') ?></textarea>
                        </div>

                        <!-- Description -->
                        <div class="form-control md:col-span-2">
                            <label class="label"><span class="label-text font-bold">About the Company</span></label>
                            <textarea name="description" class="textarea textarea-bordered h-32"><?= htmlspecialchars($organization['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="pt-6 border-t mt-6 flex gap-4">
                        <button type="submit" class="btn btn-primary btn-lg flex-1">
                            Save Changes
                            <i class="fas fa-save ml-2"></i>
                        </button>
                        <a href="organization_admin_dashboard.php" class="btn btn-ghost btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>