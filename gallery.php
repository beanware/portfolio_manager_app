<?php
include 'connection.php';
include 'navbar.php';

// Fetch all projects
$query = "SELECT * FROM projects";
$result = $connection->query($query);

// Check for errors
if (!$result) {
    die("Query failed: " . $connection->error);
}

// Fetch the projects into an array
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

// Do not close the connection here, keep it open for the images
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Page</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <h1 style="font-family: 'Comic Sans MS', cursive; " class="text-3xl font-bold text-center text-black mb-8 pt-10">Project Gallery</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4" >
        <?php foreach ($projects as $project): ?>
                <div class="card bg-base-100 w-96 shadow-sm">
                <!-- Fetch and display main image -->
                <?php
                // Get the main image for the project
                $mainImageQuery = "SELECT * FROM mainimages WHERE project_id = " . intval($project['project_id']);
                $mainImageResult = $connection->query($mainImageQuery);
                
                // Check if the main image query was successful
                if ($mainImageResult) {
                    $mainImage = $mainImageResult->fetch_assoc();
                } else {
                    $mainImage = null; // If there is no main image
                }
                ?>
            
                <?php if ($mainImage): ?>
                <figure>
                    <img
                  src="<?php echo htmlspecialchars($mainImage['image_path']); ?>"
                  alt="<?php echo htmlspecialchars($mainImage['image_title']); ?>"
                  /></figure>

                <?php else: ?>
                <figure>
                    <img src="uploads/main/default.jpg" alt="Default image"></figure> <!-- Default image -->
                <?php endif; ?>
            
                <div class="card-body">
                <h2 style="font-family: 'Comic Sans MS', cursive; font-weight: bold;" class="card-title uppercase"><?php echo htmlspecialchars($project['project_name']); ?></h2>
                <p><?php echo htmlspecialchars($project['project_description']); ?></p>

                
                <!---->
                <div class="flex justify-center gap-4 mt-8">
      <a class="relative" href="project_details.php?id=<?php echo intval($project['project_id']); ?>">
        <span class="absolute top-0 left-0 mt-1 ml-1 h-full w-full rounded bg-black"></span>
        <span class="fold-bold relative inline-block h-full w-full rounded border-2 border-pt bg-white px-4 py-2 text-black font-bold transition duration-100 hover:bg-gray-900 hover:text-white transition duration-300">View Details</span>
    </a>  
    </div>
            </div>
        </div>
        <?php endforeach; ?>
        
    </div>


    <?php $connection->close(); // Close the connection after all queries are done ?>
    <?php include 'footer.php'; ?>
