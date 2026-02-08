<?php
define('ACCESS_ALLOWED', true); // Define this to allow access to included files

require_once 'connection.php';
require_once 'includes/auth_functions.php'; // We'll create this

// SECURITY: Only allow registration if no admin exists yet, or require super-admin auth
// Better approach: Make this a setup script, not a public page
$stmt = $connection->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
$stmt->execute();
$result = $stmt->get_result();
$adminCount = $result->fetch_assoc()['admin_count'];
$stmt->close();

// If admin exists, redirect to login or show error
// if ($adminCount > 0 && !isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit();
// }

// Initialize variables
$error = '';
$success = '';
$username = $email = $display_name = '';
$errors = []; // To collect validation errors

// Generate CSRF token for the form
// Moved this earlier to ensure it's generated before the form is rendered if not already
generateCsrfToken(); 

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Security error: Invalid CSRF token. Please refresh the page.";
    } else {
        // Sanitize and retrieve input
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '', 'email');
        $display_name = sanitizeInput($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);

        // Server-side validation
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        if (empty($display_name)) {
            $errors[] = "Display Name is required.";
        }

        if (empty($password)) {
            $errors[] = "Password is required.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        $passwordStrengthErrors = validatePasswordStrength($password);
        if (!empty($passwordStrengthErrors)) {
            $errors = array_merge($errors, $passwordStrengthErrors);
        }

        if (!$terms) {
            $errors[] = "You must agree to the Terms of Service and Privacy Policy.";
        }

        // Check if username or email already exists in DB
        // Assign to default organization (ID 1) for registration, or implement an organization selection in the form
        $registration_org_id = 1; 

        if (empty($errors) && userExists($username, $email, $registration_org_id)) { 
            $errors[] = "Username or email already registered.";
        }

        if (empty($errors)) {
            // Determine role: 'admin' if it's the first user, otherwise 'viewer'
            // Re-fetch admin count right before registration to prevent race conditions in a multi-user env
            $stmt = $connection->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin' AND organization_id = ?");
            $stmt->bind_param("i", $registration_org_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentAdminCount = $result->fetch_assoc()['admin_count'];
            $stmt->close();

            $role = ($currentAdminCount == 0) ? 'admin' : 'viewer';
            
            $registrationResult = registerUser($username, $email, $password, $display_name, $registration_org_id, $role);

            if ($registrationResult['success']) {
                $success = $registrationResult['message'] . " You can now log in.";
                // Clear form fields on successful registration
                $username = $email = $display_name = '';
                // Regenerate CSRF token after successful registration to prevent replay attacks
                unset($_SESSION['csrf_token']);
                generateCsrfToken();
            } else {
                $error = $registrationResult['message'];
            }
        } else {
            $error = implode('<br>', $errors); // Combine all errors into one message
        }
    }
}


// Include header with proper theming
include 'header.php';
?>

<main class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="mb-8">
        <a href="index.php" class="btn btn-ghost">
            <i class="fas fa-arrow-left mr-2"></i> Back to Home
        </a>
    </div>

    <div class="card bg-base-100 shadow-2xl">
        <div class="card-body">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-base-content">
                    <?php echo ($adminCount == 0) ? 'Setup Administrator Account' : 'Register New User'; ?>
                </h1>
                <p class="text-base-content/70 mt-2">
                    <?php echo ($adminCount == 0) 
                        ? 'Create the first administrator account for the system.' 
                        : 'Create a new user account with appropriate permissions.'; ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success; ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary btn-sm">Go to Login</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$success || $error): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Username -->
                    <div class="form-control">
                        <label class="label" for="username">
                            <span class="label-text font-semibold">Username *</span>
                            <span class="label-text-alt text-xs">For login</span>
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               value="<?php echo htmlspecialchars($username); ?>"
                               placeholder="e.g., john_doe" 
                               class="input input-bordered <?php echo (!empty($error) && strpos($error, 'Username') !== false) ? 'input-error' : ''; ?>"
                               required
                               minlength="3"
                               maxlength="50"
                               pattern="[a-zA-Z0-9_]+"
                               title="Letters, numbers, and underscores only">
                        <label class="label">
                            <span class="label-text-alt text-base-content/50">3-50 characters, no spaces</span>
                        </label>
                    </div>

                    <!-- Email -->
                    <div class="form-control">
                        <label class="label" for="email">
                            <span class="label-text font-semibold">Email Address *</span>
                            <span class="label-text-alt text-xs">For notifications</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email); ?>"
                               placeholder="name@company.com" 
                               class="input input-bordered <?php echo (!empty($error) && strpos($error, 'email') !== false) ? 'input-error' : ''; ?>"
                               required>
                    </div>
                </div>

                <!-- Display Name -->
                <div class="form-control">
                    <label class="label" for="display_name">
                        <span class="label-text font-semibold">Display Name *</span>
                        <span class="label-text-alt text-xs">Shown to other users</span>
                    </label>
                    <input type="text" 
                           id="display_name" 
                           name="display_name" 
                           value="<?php echo htmlspecialchars($display_name); ?>"
                           placeholder="John Doe" 
                           class="input input-bordered"
                           required
                           minlength="2"
                           maxlength="100">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Password -->
                    <div class="form-control">
                        <label class="label" for="password">
                            <span class="label-text font-semibold">Password *</span>
                            <span class="label-text-alt text-xs">Minimum 8 characters</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="••••••••" 
                                   class="input input-bordered w-full pr-12"
                                   required
                                   minlength="8"
                                   pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$"
                                   title="Must contain uppercase, lowercase, and number">
                            <button type="button" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-base-content/50 hover:text-base-content"
                                    onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="mt-2 space-y-1">
                            <div class="flex items-center text-xs">
                                <span class="w-24 text-base-content/60">Must contain:</span>
                                <span id="req-upper" class="ml-2 text-error">Uppercase</span>
                                <span id="req-lower" class="ml-2 text-error">Lowercase</span>
                                <span id="req-number" class="ml-2 text-error">Number</span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-control">
                        <label class="label" for="confirm_password">
                            <span class="label-text font-semibold">Confirm Password *</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="••••••••" 
                                   class="input input-bordered w-full pr-12"
                                   required
                                   minlength="8">
                            <button type="button" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-base-content/50 hover:text-base-content"
                                    onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <span id="password-match" class="text-xs text-error">Passwords must match</span>
                        </div>
                    </div>
                </div>

                <!-- Password Requirements Info -->
                <div class="bg-base-200 rounded-lg p-4">
                    <h3 class="font-semibold text-base-content mb-2">Password Requirements</h3>
                    <ul class="text-sm text-base-content/70 space-y-1">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-success mr-2"></i>
                            At least 8 characters long
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-success mr-2"></i>
                            Contains uppercase and lowercase letters
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-success mr-2"></i>
                            Includes at least one number
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-success mr-2"></i>
                            Special characters are allowed but not required
                        </li>
                    </ul>
                </div>

                <!-- Terms and Conditions -->
                <div class="form-control">
                    <label class="cursor-pointer label justify-start">
                        <input type="checkbox" 
                               name="terms" 
                               class="checkbox checkbox-primary mr-3" 
                               required>
                        <span class="label-text">
                            I agree to the 
                            <a href="terms.php" class="link link-primary" target="_blank">Terms of Service</a>
                            and 
                            <a href="privacy.php" class="link link-primary" target="_blank">Privacy Policy</a>
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="form-control pt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?php echo ($adminCount == 0) ? 'Create Administrator Account' : 'Register Account'; ?>
                        <i class="fas fa-user-plus ml-2"></i>
                    </button>
                </div>

                <!-- Login Link -->
                <div class="text-center pt-4 border-t border-base-300">
                    <p class="text-base-content/70">
                        Already have an account? 
                        <a href="login.php" class="link link-primary font-semibold">Login here</a>
                    </p>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Password visibility toggle
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Real-time password validation
const passwordField = document.getElementById('password');
const confirmField = document.getElementById('confirm_password');
const reqUpper = document.getElementById('req-upper');
const reqLower = document.getElementById('req-lower');
const reqNumber = document.getElementById('req-number');
const passwordMatch = document.getElementById('password-match');

function updatePasswordRequirements(password) {
    reqUpper.className = /[A-Z]/.test(password) ? 'ml-2 text-success' : 'ml-2 text-error';
    reqLower.className = /[a-z]/.test(password) ? 'ml-2 text-success' : 'ml-2 text-error';
    reqNumber.className = /\d/.test(password) ? 'ml-2 text-success' : 'ml-2 text-error';
}

function validatePassword() {
    const password = passwordField.value;
    const confirm = confirmField.value;
    
    updatePasswordRequirements(password);
    
    // Check match only if confirm field has input
    if (confirm.length > 0) {
        passwordMatch.textContent = password === confirm ? 'Passwords match!' : 'Passwords do not match';
        passwordMatch.className = password === confirm ? 'text-xs text-success' : 'text-xs text-error';
    } else {
        passwordMatch.textContent = 'Passwords must match';
        passwordMatch.className = 'text-xs text-error';
    }
}

passwordField.addEventListener('input', validatePassword);
confirmField.addEventListener('input', validatePassword);

// Initial call to set password requirements on load if there's a pre-filled password (e.g., browser autofill)
document.addEventListener('DOMContentLoaded', () => {
    if (passwordField.value) {
        updatePasswordRequirements(passwordField.value);
    }
});


// Form validation (client-side, for UX - server-side is primary)
document.querySelector('form').addEventListener('submit', function(e) {
    const password = passwordField.value;
    const confirm = confirmField.value;
    const terms = document.querySelector('input[name="terms"]');
    
    let clientErrors = [];

    if (password !== confirm) {
        clientErrors.push('Passwords do not match. Please check and try again.');
        confirmField.focus();
    }
    
    if (!terms.checked) {
        clientErrors.push('You must agree to the Terms of Service and Privacy Policy.');
    }
    
    // Password strength check
    if (password.length < 8) {
        clientErrors.push('Password must be at least 8 characters long.');
        passwordField.focus();
    }
    
    if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/\d/.test(password)) {
        clientErrors.push('Password must contain uppercase letters, lowercase letters, and numbers.');
        passwordField.focus();
    }

    if (clientErrors.length > 0) {
        e.preventDefault(); // Stop form submission
        alert(clientErrors.join('\n'));
        return false;
    }
});
</script>

<?php include 'footer.php'; ?>