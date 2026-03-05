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
$project_name = clean($_POST['project_name'] ?? 'Property');
$name = clean($_POST['name'] ?? '');
$email = clean($_POST['email'] ?? '');
$phone = clean($_POST['phone'] ?? '');
$subject = clean($_POST['subject'] ?? 'Property Inquiry');
$message = clean($_POST['message'] ?? '');

if (!$organization_id || !$name || !$email || !$message) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields (Name, Email, and Message).']);
    exit;
}

try {
    // 1. Fetch Organization Email
    $orgStmt = $connection->prepare("SELECT organization_name, contact_email FROM organizations WHERE organization_id = ?");
    $orgStmt->bind_param("i", $organization_id);
    $orgStmt->execute();
    $orgData = $orgStmt->get_result()->fetch_assoc();
    $orgName = $orgData['organization_name'] ?? 'Organization';
    $orgEmail = $orgData['contact_email'] ?? '';
    $orgStmt->close();

    // 2. Insert Enquiry into Database
    $stmt = $connection->prepare("INSERT INTO enquiries (organization_id, project_id, name, email, phone, subject, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'new')");
    $p_id = ($project_id > 0) ? $project_id : NULL;
    $stmt->bind_param("iisssss", $organization_id, $p_id, $name, $email, $phone, $subject, $message);
    
    if ($stmt->execute()) {
        $enquiry_id = $connection->insert_id;
        
        // 3. Send Notification Email to Organization
        if ($orgEmail) {
            $notifSubject = "New Property Enquiry: " . ($project_id ? $project_name : "General");
            $notifBody = "<h2>New Enquiry Received</h2>
                          <p><strong>Property:</strong> {$project_name}</p>
                          <hr>
                          <h3>Enquirer Info:</h3>
                          <p><strong>Name:</strong> {$name}<br>
                          <strong>Email:</strong> {$email}<br>
                          <strong>Phone:</strong> {$phone}</p>
                          <hr>
                          <h3>Message:</h3>
                          <p>{$message}</p>
                          <p>Please log in to your dashboard to manage this enquiry.</p>";
            
            sendEmail($orgEmail, $notifSubject, $notifBody);
        }

        // 4. Send Acknowledgment Email to Enquirer
        $ackSubject = "We received your inquiry for " . $project_name;
        $ackBody = "<h3>Hello {$name},</h3>
                    <p>Thank you for contacting us regarding <strong>{$project_name}</strong>.</p>
                    <p>This is to confirm that we have received your message and a representative from <strong>{$orgName}</strong> will get back to you shortly.</p>
                    <hr>
                    <p><strong>Your Message:</strong></p>
                    <p><em>\"{$message}\"</em></p>
                    <hr>
                    <p>Best Regards,<br>{$orgName} via Property Portfolio</p>";
        
        sendEmail($email, $ackSubject, $ackBody);

        echo json_encode(['success' => true, 'message' => 'Your inquiry has been sent successfully. We will get back to you soon.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save inquiry. Please try again later.']);
    }
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Enquiry submission error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}

$connection->close();
?>