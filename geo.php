<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geography Simulation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Ensure the body fills the screen and hides any overflow */
        body {
            font-family: Arial, sans-serif;
            height: 100vh;
            margin: 0;
            overflow: hidden; /* Prevent scrollbars from appearing */
            position: relative;
            background-color: #e0f7fa;
        }

        /* Full-screen Video Background */
        .video-background {
            position: fixed;  /* Fixed position to make it always cover the viewport */
            top: 0;
            left: 0;
            width: 100vw; /* Full viewport width */
            height: 100vh; /* Full viewport height */
            object-fit: fill; /* Ensures the video covers the entire screen */
            z-index: -1; /* Keep it in the background */
        }

        /* Title Styling */
        h1 {
            color: white;
            font-size: 32px;
            margin-top: 20px;
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
        }

        /* Button Container Styling */
        .button-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            z-index: 1; /* Ensure buttons appear on top of the video */
        }

        .geo-button {
            background-color: rgba(2, 136, 209, 0.8);
            color: white;
            border: none;
            padding: 15px;
            font-size: 20px;
            cursor: pointer;
            border-radius: 10px;
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 200px;
            text-align: center;
        }

        .geo-button img {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: transform 0.3s;
        }

        .geo-button:hover {
            transform: scale(1.1);
            box-shadow: 8px 8px 20px rgba(0, 0, 0, 0.4);
        }

        .geo-button:hover img {
            transform: rotate(5deg);
        }

        /* Video Section (Hidden by default) */
        .video-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 2;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .video-container video {
            width: 90%;
            max-width: 1000px;
            border-radius: 10px;
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3);
        }

        .back-button {
            position: absolute;
            top: 20px;
            right: 20px; /* Move button to the right */
            background-color: #007bff; /* Blue color */
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            box-shadow: 5px 0px 15px rgba(0, 123, 255, 0.6); /* Glow on the right */
        }

        .back-button:hover {
            background-color: #0056b3; /* Darker blue */
            transform: scale(1.05);
            box-shadow: 10px 0px 20px rgba(0, 123, 255, 0.8); /* Stronger glow on hover */
        }

    </style>
</head>
<body>
    <?php
    // Define geography simulations data
    $geographySimulations = [
        [
            'video' => 'mountain.mp4',
            'image' => 'mountain.png',
            'alt' => 'Mountain',
            'title' => 'Mountain Formation'
        ],
        [
            'video' => 'river.mp4',
            'image' => 'river.png',
            'alt' => 'River',
            'title' => 'River Formation'
        ],
        [
            'video' => 'volcano.mp4',
            'image' => 'volcano.png',
            'alt' => 'Volcano',
            'title' => 'Volcanic Eruption'
        ]
    ];
    ?>

    <a href="geography.php" class="back-button">
        Back
    </a>

    <!-- Full-Screen Video Background -->
    <video class="video-background" autoplay loop muted>
        <source src="background-video.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <h1>Geography Simulation</h1>

    <!-- Main Button Container -->
    <div id="mainContent">
        <div class="button-container">
            <?php
            // Generate buttons dynamically from PHP array
            foreach ($geographySimulations as $simulation) {
                echo "
                <button class='geo-button' onclick=\"showVideo('{$simulation['video']}')\">
                    <img src='{$simulation['image']}' alt='{$simulation['alt']}'>
                    {$simulation['title']}
                </button>";
            }
            ?>
        </div>
    </div>
    
    <!-- Video Container (Hidden initially) -->
    <div id="videoContainer" class="video-container">
        <video id="geoVideo" controls>
            <source id="videoSource" src="" type="video/mp4">
            Your browser does not support the video tag.
        </video>

        <button id="backButton" class="back-button" onclick="goBack()">Back to Main Menu</button>
    </div>

    <script>
        // Function to show the video container and play the video
        function showVideo(videoFile) {
            let mainContent = document.getElementById("mainContent");
            let videoContainer = document.getElementById("videoContainer");
            let videoElement = document.getElementById("geoVideo");
            let sourceElement = document.getElementById("videoSource");
            
            // Set the video source
            sourceElement.src = videoFile;
            videoElement.load();
            videoElement.play();
            
            // Hide the main content and show the video container
            mainContent.style.display = "none";
            videoContainer.style.display = "flex";
        }

        // Function to go back to the main content
        function goBack() {
            let mainContent = document.getElementById("mainContent");
            let videoContainer = document.getElementById("videoContainer");
            let videoElement = document.getElementById("geoVideo");
            
            // Pause the video and reset the source
            videoElement.pause();
            videoElement.currentTime = 0;
            
            // Hide the video container and show the main content
            mainContent.style.display = "block";
            videoContainer.style.display = "none";
        }

        // Show the back button after the video finishes
        document.getElementById("geoVideo").onended = function() {
            document.getElementById("backButton").style.display = "block";
        };
    </script>

</body>
</html>