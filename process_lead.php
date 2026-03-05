<?php
define('ACCESS_ALLOWED', true);
require_once 'connection.php';
require_once 'includes/auth_functions.php';
require_once 'includes/send_email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$project_id = intval($_POST['project_id'] ?? 0);
$organization_id = intval($_POST['organization_id'] ?? 0);
$referrer_name = clean($_POST['referrer_name'] ?? '');
$referrer_email = clean($_POST['referrer_email'] ?? '');
$referrer_phone = clean($_POST['referrer_phone'] ?? '');
$buyer_name = clean($_POST['buyer_name'] ?? '');
$buyer_contact = clean($_POST['buyer_contact'] ?? '');

if (!$project_id || !$organization_id || !$referrer_name || !$referrer_email || !$referrer_phone || !$buyer_name || !$buyer_contact) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

try {
    // 1. Redundancy Check: Prevent same buyer contact for same project
    $checkStmt = $connection->prepare("SELECT lead_id FROM leads WHERE project_id = ? AND (buyer_contact = ? OR buyer_name = ?)");
    $checkStmt->bind_param("iss", $project_id, $buyer_contact, $buyer_name);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This buyer lead has already been submitted for this property.']);
        exit;
    }
    $checkStmt->close();

    // 2. Fetch Project and Org names for email
    $infoStmt = $connection->prepare("SELECT p.project_name, o.organization_name FROM projects p JOIN organizations o ON p.organization_id = o.organization_id WHERE p.project_id = ?");
    $infoStmt->bind_param("i", $project_id);
    $infoStmt->execute();
    $info = $infoStmt->get_result()->fetch_assoc();
    $projectName = $info['project_name'] ?? 'Property';
    $orgName = $info['organization_name'] ?? 'Organization';
    $infoStmt->close();

    // 3. Insert Lead
    $stmt = $connection->prepare("INSERT INTO leads (project_id, organization_id, referrer_name, referrer_email, referrer_phone, buyer_name, buyer_contact, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iisssss", $project_id, $organization_id, $referrer_name, $referrer_email, $referrer_phone, $buyer_name, $buyer_contact);
    
    if ($stmt->execute()) {
        // 4. Send Email Notifications
        $subject = "New Referral Lead: " . $buyer_name . " for " . $projectName;
        $body = "<h2>New Referral Submission</h2>
                 <p><strong>Property:</strong> {$projectName}</p>
                 <p><strong>Organization:</strong> {$orgName}</p>
                 <hr>
                 <h3>Referrer Info:</h3>
                 <p><strong>Name:</strong> {$referrer_name}<br>
                 <strong>Email:</strong> {$referrer_email}<br>
                 <strong>Phone:</strong> {$referrer_phone}</p>
                 <hr>
                 <h3>Buyer Lead Info:</h3>
                 <p><strong>Name:</strong> {$buyer_name}<br>
                 <strong>Contact:</strong> {$buyer_contact}</p>
                 <p>Please log in to the admin dashboard to verify this lead.</p>";
        
        // Notify Referrer
        $refSubject = "Referral Submitted: " . $projectName;
        $refBody = "<h3>Thank you for your referral!</h3>
                    <p>Hi {$referrer_name}, we have received your lead for <strong>{$projectName}</strong>.</p>
                    <p>Our team will verify the contact info and update you on the status of the bounty.</p>
                    <p>Lead: {$buyer_name}</p>";
        
        sendEmail($referrer_email, $refSubject, $refBody);
        
        // Notify System/Org Admin (In real use, fetch admin email)
        // sendEmail('admin@example.com', $subject, $body);

        echo json_encode(['success' => true, 'message' => 'Lead submitted successfully! Check your email for confirmation.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save lead. Please try again later.']);
    }
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Lead submission error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}

$connection->close();
?>