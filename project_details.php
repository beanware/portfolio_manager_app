<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'connection.php'; // Include database connection

// Fetch project ID from URL
$projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$project = null;
$mainImage = null;
$carouselImages = [];

// Fetch project details
$sql = "SELECT project_name, project_description, project_location, project_date, project_type, documentation FROM projects WHERE project_id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $projectId);
$stmt->execute();
$projectResult = $stmt->get_result();

if ($projectResult->num_rows > 0) {
    $project = $projectResult->fetch_assoc();
}

// Fetch main image
$sql = "SELECT image_path FROM mainimages WHERE project_id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $projectId);
$stmt->execute();
$mainImageResult = $stmt->get_result();

if ($mainImageResult->num_rows > 0) {
    $mainImage = $mainImageResult->fetch_assoc();
}

// Fetch carousel images
$sql = "SELECT image_path, image_title FROM carouselimages WHERE project_id = ? ORDER BY display_order LIMIT 500";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $projectId);
$stmt->execute();
$carouselResult = $stmt->get_result();

while ($rowCarousel = $carouselResult->fetch_assoc()) {
    $carouselImages[] = $rowCarousel;
}

$stmt->close();
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        /* Popup overlay styles */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 50;
        }
        .popup-overlay.show {
            display: flex;
        }

        /* Image zooming and panning styles */
        .popup-image-container {
            max-width: 90%;
            max-height: 90%;
            overflow: hidden;
            position: relative;
            cursor: grab;
        }
        .popup-image {
            transition: transform 0.3s ease;
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
            transform-origin: center center;
            cursor: zoom-in;
        }
        .popup-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #fff;
            color: #000;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body >
   <div role="alert" class="alert bg-gray-900 text-white text-center items-center alert-vertical sm:alert-horizontal flex justify-center items-center m-4">
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-info h-6 w-6 shrink-0">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
  </svg>
  <div>
    <h3 class="font-bold">Helpful Tip!</h3>
    <div class="text-lg">Click Images To Expand View.</div>
  </div>
</div>
</div>
    <div class="container mx-auto max-w-4xl my-10 p-5 bg-white rounded-lg shadow-lg">
        <div class="flex justify-center gap-4 mt-8">
      <a class="relative" href="gallery.php">
        <span class="absolute top-0 left-0 mt-1 ml-1 h-full w-full rounded bg-black"></span>
        <span class="fold-bold relative inline-block h-full w-full rounded border-2 border-pt bg-white px-4 py-2 text-black font-bold transition duration-100 hover:bg-gray-900 hover:text-white transition duration-300 uppercase">Return</span>
    </a>  
    </div>
        <?php if ($project): ?>
            <h1 class="text-3xl font-bold text-gray-800 mt-5"><?php echo htmlspecialchars($project['project_name']); ?></h1>
            <?php if ($mainImage): ?>
                <img class="w-full h-auto max-h-96 object-cover rounded-lg shadow-md mt-4" src="<?php echo htmlspecialchars($mainImage['image_path']); ?>" alt="Main Image" onclick="openPopup('<?php echo htmlspecialchars($mainImage['image_path']); ?>')">
            <?php endif; ?>
            <p class="text-gray-600 mt-4"><?php echo nl2br(htmlspecialchars($project['project_description'])); ?></p>
            <p class="text-gray-700 mt-2"><strong>Location:</strong> <?php echo htmlspecialchars($project['project_location']); ?></p>
            <p class="text-gray-700 mt-2"><strong>Date:</strong> <?php echo htmlspecialchars($project['project_date']); ?></p>
            <p class="text-gray-700 mt-2"><strong>Type:</strong> <?php echo htmlspecialchars($project['project_type']); ?></p>
            <?php if (!empty($project['documentation'])): ?>
                <p class="text-gray-700 mt-2"><strong>Documentation:</strong> <a href="<?php echo htmlspecialchars($project['documentation']); ?>" target="_blank" class="text-blue-600 hover:underline">View PDF</a></p>
            <?php endif; ?>

            <div class="mt-8">
                <?php if (count($carouselImages) > 0): ?>
                    <div class="flex overflow-x-auto space-x-4 p-2">
                        <?php foreach ($carouselImages as $image): ?>
                            <img class="max-w-xs max-h-32 object-cover rounded-lg shadow-sm cursor-pointer transition-transform duration-200 hover:scale-105" src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['image_title']); ?>" onclick="openPopup('<?php echo htmlspecialchars($image['image_path']); ?>')">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-red-600">Project not found.</p>
        <?php endif; ?>
    </div>

    <!-- Popup Overlay -->
    <div id="popup-overlay" class="popup-overlay">
        <button class="popup-close" onclick="closePopup()">&times;</button>
        <div class="popup-image-container">
            <img id="popup-image" class="popup-image" src="" alt="Popup Image">
        </div>
    </div>

    <script>
        let scale = 1;
        let isPanning = false;
        let startX, startY;
        let translateX = 0, translateY = 0;

        function openPopup(imageSrc) {
            const popupImage = document.getElementById("popup-image");
            popupImage.src = imageSrc;
            document.getElementById("popup-overlay").classList.add("show");
            scale = 1;
            translateX = 0;
            translateY = 0;
            popupImage.style.transform = `scale(${scale}) translate(0px, 0px)`;
            popupImage.style.cursor = "zoom-in";
        }

        function closePopup() {
            document.getElementById("popup-overlay").classList.remove("show");
            document.getElementById("popup-image").src = "";
        }

        const popupImage = document.getElementById("popup-image");
        
        // Zoom in/out with mouse wheel
        popupImage.addEventListener("wheel", function(event) {
            event.preventDefault();
            scale += event.deltaY * -0.001;
            scale = Math.min(Math.max(1, scale), 3);
            popupImage.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;
            popupImage.style.cursor = scale > 1 ? "grab" : "zoom-in";
        });

        // Start panning on mouse down
        popupImage.addEventListener("mousedown", function(event) {
            if (scale > 1) {
                isPanning = true;
                startX = event.clientX - translateX;
                startY = event.clientY - translateY;
                popupImage.style.cursor = "grabbing";
                event.preventDefault();
            }
        });

        // Adjust translation while panning
        document.addEventListener("mousemove", function(event) {
            if (isPanning) {
                translateX = event.clientX - startX;
                translateY = event.clientY - startY;
                popupImage.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;
            }
        });

        // End panning on mouse up
        document.addEventListener("mouseup", function() {
            isPanning = false;
            popupImage.style.cursor = scale > 1 ? "grab" : "zoom-in";
        });

        // Reset zoom and pan on image click
        // popupImage.addEventListener("click", function() {
        //     if (scale === 1) return;
        //     scale = 1;
        //     translateX = 0;
        //     translateY = 0;
        //     popupImage.style.transform = `scale(${scale}) translate(0px, 0px)`;
        //     popupImage.style.cursor = "zoom-in";
        // });
    </script>
</body>
</html>
