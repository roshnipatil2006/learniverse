<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geography Identification Game</title>
    <style>
        body { 
            text-align: center; 
            font-family: Arial, sans-serif; 
            background-color: #000000;
            padding: 20px;
        }
        h1 {
            color: #85a8cc;
            margin-bottom: 10px;
        }
        p{
            color: #85a8cc;
        }
        #game-container { 
            position: relative; 
            display: inline-block;
            margin: 20px auto;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        #map-img { 
            width: 100%;
            display: block;
            border: 1px solid #ccc;
        }
        .input-box {
            position: absolute;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px 12px;
            border: 2px solid #3498db;
            border-radius: 20px;
            width: 150px;
            text-align: center;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .input-box:focus {
            outline: none;
            border-color: #2ecc71;
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.6);
        }
        .input-box.correct {
            border-color: #2ecc71;
            background-color: rgba(46, 204, 113, 0.2);
        }
        .input-box.incorrect {
            border-color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.2);
        }
        .info-box {
            position: absolute;
            display: none;
            background: rgba(0, 0, 0, 0.85);
            color: white;
            padding: 15px;
            border-radius: 8px;
            width: 250px;
            z-index: 100;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            font-size: 14px;
            line-height: 1.5;
        }
        .hint-box {
            position: absolute;
            display: none;
            background: rgba(52, 152, 219, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            z-index: 50;
            pointer-events: none;
            white-space: nowrap;
        }
        #score-display {
            margin: 15px 0;
            font-size: 18px;
            font-weight: bold;
            color: #5b6f84;
        }
        #progress-bar {
            width: 100%;
            max-width: 800px;
            height: 10px;
            background-color: #ecf0f1;
            border-radius: 5px;
            margin: 10px auto;
            overflow: hidden;
        }
        #progress {
            height: 100%;
            background-color: #2ecc71;
            width: 0%;
            transition: width 0.5s ease;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #2980b9;
        }
        .feature-marker {
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: rgba(231, 76, 60, 0.7);
            border-radius: 50%;
            border: 2px solid white;
            transform: translate(-10px, -10px);
            pointer-events: none;
            display: none;
        }
    </style>
</head>
<body>
    
    <h1>Geo-objects</h1>
    <p>Identify the geographical features by typing their names in the boxes.</p>
    <div id="score-display">Score: 0 / 0</div>
    <div id="progress-bar"><div id="progress"></div></div>
    
    <div id="game-container">
        <img id="map-img" src="bg.png" alt="Geography Map">
        
        <!-- Feature markers (position these over the actual features on your map) -->
        <div class="feature-marker" style="top: 40%; left: 30%;"></div>
        <div class="feature-marker" style="top: 80%; left: 0%;"></div>
        <div class="feature-marker" style="top: 40%; left: 85%;"></div>
        <div class="feature-marker" style="top: 22%; left: 15%;"></div>
        <div class="feature-marker" style="top: 35%; left: 10%;"></div>
        <div class="feature-marker" style="top: 10%; left: 75%;"></div>
        <div class="feature-marker" style="top: 65%; left: 75%;"></div>
        
        <?php
        // Define geography features data
        $features = [
            "mountain" => [
                "correctAnswers" => ["mountain", "mount", "peak"],
                "description" => "Mountains are elevated landforms that rise prominently above their surroundings. They typically have steep slopes and significant local relief.",
                "hint" => "This landform rises high above the surrounding terrain",
                "position" => ["top" => "40%", "left" => "30%"]
            ],
            "ocean" => [
                "correctAnswers" => ["ocean", "sea"],
                "description" => "Oceans cover about 71% of Earth's surface and contain 97% of Earth's water. The major oceans are the Pacific, Atlantic, Indian, and Arctic.",
                "hint" => "This vast body of salt water covers most of Earth's surface",
                "position" => ["top" => "80%", "left" => "0%"]
            ],
            "plain" => [
                "correctAnswers" => ["plain", "grassland", "prairie"],
                "description" => "Plains are extensive areas of flat or gently rolling land, usually at low elevation. They are often fertile and good for agriculture.",
                "hint" => "A large area of flat land with few trees",
                "position" => ["top" => "40%", "left" => "85%"]
            ],
            "island" => [
                "correctAnswers" => ["island", "isle"],
                "description" => "Islands are landforms completely surrounded by water. They can be continental (part of continental shelf) or oceanic (volcanic in origin).",
                "hint" => "A piece of land completely surrounded by water",
                "position" => ["top" => "22%", "left" => "15%"]
            ],
            "volcano" => [
                "correctAnswers" => ["volcano"],
                "description" => "Volcanoes are openings in Earth's crust that allow molten rock, ash, and gases to escape from below the surface. They can be active, dormant, or extinct.",
                "hint" => "This geological feature can erupt with lava and ash",
                "position" => ["top" => "35%", "left" => "10%"]
            ],
            "snow-mountain" => [
                "correctAnswers" => ["snow mountain", "snowy mountain", "alp", "glacier"],
                "description" => "Snow-covered mountains are high-elevation landforms where temperatures remain below freezing for much of the year, allowing snow to accumulate.",
                "hint" => "A high mountain covered with snow year-round",
                "position" => ["top" => "10%", "left" => "75%"]
            ],
            "river" => [
                "correctAnswers" => ["river", "stream", "waterway"],
                "description" => "Rivers are natural flowing watercourses that move towards an ocean, sea, lake or another river. They are crucial for freshwater supply and ecosystems.",
                "hint" => "A natural flowing watercourse towards a larger body of water",
                "position" => ["top" => "65%", "left" => "75%"]
            ]
        ];

        // Generate input boxes dynamically from PHP array
        foreach ($features as $id => $feature) {
            $position = $feature['position'];
            echo "<input type='text' class='input-box' id='{$id}' placeholder='Type the name here' 
                  style='top: {$position['top']}; left: {$position['left']};'>";
        }
        ?>
        
        <div id="info-box" class="info-box"></div>
        <div id="hint-box" class="hint-box"></div>
    </div>
    
    <button id="show-hints">Show Hints</button>
    <a href="geography.php"><button id="back-button"><b>Back</b></button></a>
    
    <script>
        // Convert PHP array to JavaScript object
        const features = <?php echo json_encode($features); ?>;

        // Game state
        let score = 0;
        let totalQuestions = Object.keys(features).length;
        let answeredQuestions = 0;
        let showHints = false;
        
        // Initialize the game
        function initGame() {
            score = 0;
            answeredQuestions = 0;
            updateScore();
            
            // Position input boxes near their features and reset them
            Object.keys(features).forEach(id => {
                const input = document.getElementById(id);
                input.value = "";
                input.className = "input-box";
                input.placeholder = "Type the name here";
                input.disabled = false;
            });
            
            // Hide all feature markers initially
            document.querySelectorAll('.feature-marker').forEach(marker => {
                marker.style.display = 'none';
            });
        }
        
        // Update score display
        function updateScore() {
            document.getElementById('score-display').textContent = `Score: ${score} / ${totalQuestions}`;
            document.getElementById('progress').style.width = `${(answeredQuestions / totalQuestions) * 100}%`;
        }
        
        // Show information about a feature
        function showInfo(input, isCorrect) {
            const infoBox = document.getElementById("info-box");
            const feature = features[input.id];
            
            if (isCorrect) {
                infoBox.innerHTML = `<strong>${input.id.replace('-', ' ').toUpperCase()}</strong><br>${feature.description}`;
                infoBox.style.left = `${input.offsetLeft - 60}px`;
                infoBox.style.top = `${input.offsetTop + 40}px`;
                infoBox.style.display = "block";
                
                // Hide after 5 seconds
                setTimeout(() => { 
                    infoBox.style.display = "none"; 
                }, 5000);
            }
        }
        
        // Show hint when hovering over input
        function showHint(input) {
            if (!showHints) return;
            
            const hintBox = document.getElementById("hint-box");
            const feature = features[input.id];
            
            hintBox.textContent = feature.hint;
            hintBox.style.left = `${input.offsetLeft}px`;
            hintBox.style.top = `${input.offsetTop - 30}px`;
            hintBox.style.display = "block";
        }
        
        // Hide hint
        function hideHint() {
            document.getElementById("hint-box").style.display = "none";
        }
        
        // Check answer
        function checkAnswer(input) {
            const userInput = input.value.toLowerCase().trim();
            const feature = features[input.id];
            
            // Check if answer is correct
            const isCorrect = feature.correctAnswers.some(answer => userInput === answer);
            
            if (isCorrect) {
                input.classList.add("correct");
                input.placeholder = input.id.replace('-', ' ');
                score++;
                answeredQuestions++;
                showInfo(input, true);
                input.disabled = true;
            } else {
                input.classList.add("incorrect");
                setTimeout(() => {
                    input.classList.remove("incorrect");
                }, 1000);
            }
            
            updateScore();
            
            // Check if game is complete
            if (answeredQuestions === totalQuestions) {
                setTimeout(() => {
                    alert(`Game Over! Your final score is ${score}/${totalQuestions}`);
                }, 500);
            }
        }
        
        // Event listeners
        document.querySelectorAll(".input-box").forEach(input => {
            // Check answer on Enter
            input.addEventListener("keydown", (event) => {
                if (event.key === "Enter") {
                    checkAnswer(input);
                }
            });
            
            // Show hint on hover
            input.addEventListener("mouseover", () => {
                showHint(input);
            });
            
            input.addEventListener("mouseout", hideHint);
            
            // Clear any styling when typing
            input.addEventListener("input", () => {
                input.className = "input-box";
            });
        });
        
        // Toggle hints
        document.getElementById("show-hints").addEventListener("click", () => {
            showHints = !showHints;
            const btn = document.getElementById("show-hints");
            btn.textContent = showHints ? "Hide Hints" : "Show Hints";
            
            // Toggle feature markers
            document.querySelectorAll('.feature-marker').forEach(marker => {
                marker.style.display = showHints ? 'block' : 'none';
            });
        });
        
        // Initialize the game
        initGame();
    </script>
</body>
</html>