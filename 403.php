<?php
// Define ACCESS_ALLOWED to allow inclusion of auth_functions if needed for shared headers/footers
define('ACCESS_ALLOWED', true);
require_once 'includes/auth_functions.php'; // For safeRedirect and potentially header/footer includes

// Optional: Include a header file if you have one
// include 'header.php';

http_response_code(403); // Set HTTP response code to 403 Forbidden
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f7f7f7;
            color: #333;
            font-family: sans-serif;
            text-align: center;
            flex-direction: column;
        }
        .container {
            background-color: #fff;
            padding: 40px 60px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 4em;
            margin-bottom: 10px;
            color: #e74c3c;
        }
        p {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>403</h1>
        <p>Access Denied!</p>
        <p>You do not have permission to view this resource.</p>
        <?php if (isset($_GET['message'])): ?>
            <p class="text-red-500"><?= htmlspecialchars($_GET['message']) ?></p>
        <?php endif; ?>
        <a href="index.php">Go to Homepage</a>
    </div>
</body>
</html>
