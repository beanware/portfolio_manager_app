<?php
include_once 'navbar.php';
// Start output buffering
ob_start();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);
    
    $errors = [];

    // Validate inputs
    if (empty($name)) {
        $errors['name'] = "Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "A valid email is required.";
    }
    if (empty($message)) {
        $errors['message'] = "Message is required.";
    }

    if (empty($errors)) {
        // Set email parameters
        $to = 'samuelmikaye2000@gmail.com';
        $from = 'webuser@example.com';  // Replace with your default "from" email
        $subject = 'Contact Form Submission';
        $headers = "From: $from\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $email_body = "Name: $name\nEmail: $email\n\nMessage:\n$message";

        // Send email
        if (mail($to, $subject, $email_body, $headers)) {
            $_SESSION['message'] = "Message sent successfully!";
        } else {
            $_SESSION['message'] = "Failed to send message.";
        }
        
        // Redirect to the same page to reload the message
        header("Location: contact.php");
        exit();
    }
}

// End output buffering and flush
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form</title>
    <style>
        .form-wrapper {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            max-width: 600px;
            padding: 100px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background: #fff;
        }
        .form-container input, .form-container textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .form-container button {
            background-color: #0d1b2a;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s ease-in-out;
        }
        .form-container button:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #0d1b2a;
            backdrop-filter: blur(10px);
        }
        .form-container span {
            color: red;
        }
        .whatsapp-icon {
            margin-top: 20px;
            text-align: center;
        }
        .whatsapp-icon img {
            cursor: pointer;
            width: 32px;
            height: 32px;
            color: #25D366;
        }
    </style>
</head>
<body>
    <div class="form-wrapper">
    <div class="form-container">
        <?php
        // Display success or error messages
        if (isset($_SESSION['message'])) {
            echo "<p>{$_SESSION['message']}</p>";
            unset($_SESSION['message']);
        }
        ?>

        <form method="post" enctype="multipart/form-data">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES); ?>">
            <?php if (isset($errors['name'])): ?><span><?php echo $errors['name']; ?></span><?php endif; ?>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>">
            <?php if (isset($errors['email'])): ?><span><?php echo $errors['email']; ?></span><?php endif; ?>

            <label for="message">Message:</label>
            <textarea id="message" name="message"><?php echo htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES); ?></textarea>
            <?php if (isset($errors['message'])): ?><span><?php echo $errors['message']; ?></span><?php endif; ?>

            <button type="submit">Send Message</button>
        </form>

        <div class="whatsapp-icon">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6d/WhatsApp_Logo.svg" alt="WhatsApp" onclick="handleWhatsAppClick()">
        </div>
    </div>
    </div>
    <script>
        function handleWhatsAppClick() {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const message = document.getElementById('message').value;
            const whatsappMessage = `Name: ${name}%0AEmail: ${email}%0AMessage: ${message}`;
            const whatsappURL = `https://api.whatsapp.com/send?phone=+254792716948&text=${whatsappMessage}`;

            window.open(whatsappURL, '_blank');
        }
    </script>
<?php INCLUDE 'footer.php'; ?>
