<?php

// Ensure Composer autoloader is included
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email using PHPMailer with predefined SMTP settings.
 *
 * @param string $to Email address of the recipient.
 * @param string $subject Subject of the email.
 * @param string $body HTML or plain text body of the email.
 * @param string $altBody Optional plain text alternative body for HTML emails.
 * @param array $attachments Optional array of file paths to attach.
 * @return bool True on success, false on failure.
 */
function sendEmail($to, $subject, $body, $altBody = '', $attachments = []) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0; // Disable verbose debug output
        $mail->isSMTP();                            
        $mail->Host       = 'mail.triadsoftware.africa'; 
        $mail->SMTPAuth   = true;                   
        $mail->Username   = 'support@triadsoftware.africa'; 
        $mail->Password   = 'domuk@661942';       
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465;                    

        // Recipients
        $mail->setFrom('support@triadsoftware.africa', 'Property Portfolio Bounties');
        $mail->addAddress($to);                      // Add a recipient

        // Content
        $mail->isHTML(true);                        // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;

        // Attachments
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $mail->addAttachment($attachment);
            } else {
                error_log("PHPMailer: Attachment file not found: " . $attachment);
            }
        }

        $mail->send();
        error_log("Email sent successfully to: " . $to);
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent to " . $to . ". Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

?>