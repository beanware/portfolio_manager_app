<?php
define('ACCESS_ALLOWED', true);
require_once 'connection.php';
require_once 'includes/auth_functions.php';

// Must be logged in
requireAuth();

$user = getCurrentUser();

// If user already has an organization, redirect to index
if (!empty($user['organization_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

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
        
        if (empty($org_name)) {
            $error = "Organization name is required.";
        } else {
            $connection->begin_transaction();
            try {
                // 1. Create the organization with detailed info
                $stmt = $connection->prepare("INSERT INTO organizations (organization_name, company_address, contact_email, contact_phone, website, license_number, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $org_name, $address, $email, $phone, $website, $license, $description);
                $stmt->execute();
                $organization_id = $connection->insert_id;
                $stmt->close();
                
                // 2. Assign user to this organization and ensure they are admin
                $stmt = $connection->prepare("UPDATE users SET organization_id = ?, role = 'admin' WHERE user_id = ?");
                $stmt->bind_param("ii", $organization_id, $user['id']);
                $stmt->execute();
                $stmt->close();
                
                $connection->commit();
                
                // 3. Update session
                $_SESSION['organization_id'] = $organization_id;
                $_SESSION['role'] = 'admin';
                
                logActivity('Created Detailed Organization', 'Organization', $organization_id);
                
                header('Location: index.php');
                exit();
            } catch (Exception $e) {
                $connection->rollback();
                if ($connection->errno === 1062) {
                    $error = "An organization with this name already exists.";
                } else {
                    $error = "Failed to create organization: " . $e->getMessage();
                }
            }
        }
    }
}

$csrfToken = generateCsrfToken();
include 'header.php';
?>

<div class="bg-base-200 min-h-screen py-12">
    <div class="max-w-3xl mx-auto px-4">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-black text-base-content mb-3">Company Onboarding</h1>
            <p class="text-base-content/60">Set up your property company profile to start managing your portfolio.</p>
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-2xl mb-6 border-b pb-2">Business Details</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error mb-6">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="create_organization.php" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Org Name -->
                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text font-bold">Company / Agency Name <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="organization_name" placeholder="e.g. Elite Shelter Properties" class="input input-bordered w-full" required autofocus />
                        </div>

                        <!-- Email -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold">Business Email</span>
                            </label>
                            <input type="email" name="contact_email" placeholder="contact@company.com" class="input input-bordered w-full" />
                        </div>

                        <!-- Phone -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold">Phone Number</span>
                            </label>
                            <input type="text" name="contact_phone" placeholder="+1 (555) 000-0000" class="input input-bordered w-full" />
                        </div>

                        <!-- Website -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold">Website URL</span>
                            </label>
                            <input type="url" name="website" placeholder="https://www.company.com" class="input input-bordered w-full" />
                        </div>

                        <!-- License -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold">Real Estate License #</span>
                            </label>
                            <input type="text" name="license_number" placeholder="RE-123456789" class="input input-bordered w-full" />
                        </div>

                        <!-- Address -->
                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text font-bold">Physical Address</span>
                            </label>
                            <textarea name="company_address" placeholder="123 Business Ave, Suite 100..." class="textarea textarea-bordered h-24" ></textarea>
                        </div>

                        <!-- Description -->
                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text font-bold">About the Company</span>
                            </label>
                            <textarea name="description" placeholder="Briefly describe your agency's focus and expertise..." class="textarea textarea-bordered h-32" ></textarea>
                        </div>
                    </div>

                    <div class="pt-6 border-t mt-6">
                        <button type="submit" class="btn btn-primary btn-lg w-full">
                            Complete Setup & Start Managing
                            <i class="fas fa-check-circle ml-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-8">
            <a href="logout.php" class="text-sm link link-hover opacity-60">Cancel and Logout</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>