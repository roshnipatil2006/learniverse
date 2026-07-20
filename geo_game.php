<?php
// quiz_data.php - You can separate this into another file
$questions = [
    [ 
        'question' => "Which country is known as the Land of the Rising Sun?", 
        'options' => ["China", "Japan", "South Korea", "India"], 
        'answer' => "Japan", 
        'hint' => "It has Mount Fuji.",
        'location' => ['x' => 85, 'y' => 45],
        'feedback' => "Japan is called the Land of the Rising Sun because from China it appears that the sun rises from the direction of Japan. The name 'Japan' in Japanese is 'Nihon' or 'Nippon', which literally means 'the sun's origin'."
    ],
    [ 
        'question' => "Which is the longest river in the world?", 
        'options' => ["Amazon", "Nile", "Yangtze", "Mississippi"], 
        'answer' => "Nile", 
        'hint' => "It flows through Egypt.",
        'location' => ['x' => 60, 'y' => 50],
        'feedback' => "The Nile River is approximately 6,650 km (4,130 miles) long and flows through 11 countries in northeastern Africa. It has two main tributaries: the White Nile and the Blue Nile. The Nile has been the lifeline of civilization in Egypt for thousands of years."
    ],
    // Add the rest of your questions here following the same format
];

// Shuffle questions for variety
shuffle($questions);

// Initialize or retrieve session variables
session_start();
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = 0;
}
if (!isset($_SESSION['current_question'])) {
    $_SESSION['current_question'] = 0;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['answer'])) {
        $selected_answer = $_POST['answer'];
        $current_question_index = $_SESSION['current_question'];
        
        if ($selected_answer === $questions[$current_question_index]['answer']) {
            $_SESSION['score'] += 10;
            $result = 'correct';
        } else {
            $result = 'incorrect';
        }
        
        // Move to next question or reset
        $_SESSION['current_question'] = ($_SESSION['current_question'] + 1) % count($questions);
        
        // Return JSON response for AJAX handling
        header('Content-Type: application/json');
        echo json_encode([
            'result' => $result,
            'correct_answer' => $questions[$current_question_index]['answer'],
            'feedback' => $questions[$current_question_index]['feedback'],
            'location' => $questions[$current_question_index]['location'],
            'score' => $_SESSION['score']
        ]);
        exit;
    }
    
    if (isset($_POST['reset'])) {
        $_SESSION['score'] = 0;
        $_SESSION['current_question'] = 0;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>World Geography Quiz</title>
    <style>
        /* Your existing CSS styles here */
        body {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            background-image: linear-gradient(to right, #1e3b723c, #2a52984e),url("qbg.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            margin: 0;
            padding: 0;
            position: relative;
        }
        h1 {
            font-size: 40px;
            margin-top: 20px;
            text-shadow: 0 0 5px rgb(25, 3, 82);
        }
        .map-container {
            width: 90%;
            max-width: 900px;
            height: 500px;
            margin: 20px auto;
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.5);
            background: #000;
            cursor: grab;
        }
        .map-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .question-container {
            font-size: 24px;
            background: rgba(255, 255, 255, 0.553);
            padding: 20px;
            border-radius: 15px;
            margin: 20px auto;
            width: 80%;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.4);
            animation: fadeIn 1s ease-in-out;
            color: #000;
        }
        .options {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin: 20px auto;
            max-width: 800px;
        }
        .option-button {
            background: #05b3e393;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 20px;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
            flex: 1 1 40%;
            min-width: 200px;
        }
        .option-button:hover {
            transform: translateY(-5px);
            background: #0f5261;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.4);
        }
        .option-button.correct {
            background: #4CAF50;
            animation: pulse 0.5s;
        }
        .option-button.incorrect {
            background: #f44336;
        }
        
        .score-container {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 26px;
            font-weight: bold;
            background: rgba(0, 0, 0, 0.652);
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.3);
            z-index: 10;
        }
        .country-highlight {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 0, 0.3);
            border: 2px solid yellow;
            pointer-events: none;
            display: none;
            transform: translate(-50%, -50%);
        }
        .feedback-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 100;
            justify-content: center;
            align-items: center;
        }
        .feedback-content {
            background-color: #2a5298;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 80%;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.5s;
        }
        .feedback-title {
            font-size: 28px;
            margin-bottom: 15px;
        }
        .feedback-text {
            font-size: 20px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .close-feedback {
            background: #81e3ea;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 18px;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .close-feedback:hover {
            background: #0f5261;
            transform: translateY(-2px);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .speaker-icon {
            margin-left: 10px;
            cursor: pointer;
            display: inline-block;
            width: 24px;
            height: 24px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>');
            background-size: contain;
            vertical-align: middle;
        }
        
        .back-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            box-shadow: 5px 0px 15px rgba(0, 123, 255, 0.6);
            text-decoration: none;
            display: inline-block;
        }

        .back-button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
            box-shadow: 10px 0px 20px rgba(0, 123, 255, 0.8);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <a href="geography.php" class="back-button">Back</a>
    
    <div class="score-container" id="score">Score: <?php echo $_SESSION['score']; ?></div>
    <h1>World Geography Quiz</h1>
    
    <div class="map-container" id="mapContainer">
        <img id="worldMap" src="world.jpg" alt="World Map">
        <div class="country-highlight" id="countryHighlight"></div>
    </div>
    
    <div class="question-container" id="question">
        <span id="questionText"><?php 
            echo htmlspecialchars($questions[$_SESSION['current_question']]['question']); 
        ?></span>
        <span class="speaker-icon" id="speakerIcon" onclick="speakCurrentQuestion()"></span>
    </div>
    
    <div class="options" id="options">
        <?php
        $current_question = $questions[$_SESSION['current_question']];
        $shuffled_options = $current_question['options'];
        shuffle($shuffled_options);
        
        foreach ($shuffled_options as $option) {
            echo '<button class="option-button" onclick="checkAnswer(\'' . htmlspecialchars($option) . '\')">' 
                 . htmlspecialchars($option) . '</button>';
        }
        ?>
    </div>
    
    <div class="feedback-modal" id="feedbackModal">
        <div class="feedback-content">
            <h2 class="feedback-title" id="feedbackTitle">Feedback</h2>
            <p class="feedback-text" id="feedbackText"></p>
            <button class="close-feedback" onclick="closeFeedback()">Continue</button>
        </div>
    </div>
    
    <audio id="correctSound" src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3"></audio>
    <audio id="wrongSound" src="https://assets.mixkit.co/sfx/preview/mixkit-wrong-answer-fail-notification-946.mp3"></audio>
    
    <script>
        let currentUtterance = null;
        
        function speakQuestion(text) {
            if (currentUtterance) {
                window.speechSynthesis.cancel();
            }
            
            currentUtterance = new SpeechSynthesisUtterance(text);
            currentUtterance.rate = 1.0;
            currentUtterance.pitch = 1.0;
            currentUtterance.volume = 1.0;
            
            const voices = window.speechSynthesis.getVoices();
            const preferredVoice = voices.find(v => v.lang.includes('en-US')) || 
                                 voices.find(v => v.lang.includes('en'));
            if (preferredVoice) {
                currentUtterance.voice = preferredVoice;
            }
            
            window.speechSynthesis.speak(currentUtterance);
        }
        
        function speakCurrentQuestion() {
            const currentQuestion = document.getElementById("questionText").textContent;
            speakQuestion(currentQuestion);
        }
        
        function playSound(isCorrect) {
            const sound = document.getElementById(isCorrect ? "correctSound" : "wrongSound");
            sound.currentTime = 0;
            sound.play();
        }
        
        function checkAnswer(selected) {
            const buttons = document.querySelectorAll('.option-button');
            
            if (currentUtterance) {
                window.speechSynthesis.cancel();
            }
            
            // Disable all buttons
            buttons.forEach(btn => {
                btn.disabled = true;
            });
            
            // Send answer to server
            const formData = new FormData();
            formData.append('answer', selected);
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Highlight correct/incorrect answers
                buttons.forEach(btn => {
                    if (btn.textContent === data.correct_answer) {
                        btn.classList.add('correct');
                    } else if (btn.textContent === selected && selected !== data.correct_answer) {
                        btn.classList.add('incorrect');
                    }
                });
                
                if (data.result === 'correct') {
                    playSound(true);
                    highlightCountry(data.location);
                    showFeedback(true, data.correct_answer, data.feedback);
                } else {
                    playSound(false);
                    highlightCountry(data.location);
                    showFeedback(false, data.correct_answer, data.feedback);
                }
                
                // Update score
                document.getElementById("score").textContent = "Score: " + data.score;
            })
            .catch(error => console.error('Error:', error));
        }
        
        function showFeedback(isCorrect, correctAnswer, feedback) {
            const modal = document.getElementById("feedbackModal");
            const title = document.getElementById("feedbackTitle");
            const text = document.getElementById("feedbackText");
            
            if (isCorrect) {
                title.textContent = "Correct!";
                title.style.color = "#4CAF50";
                text.innerHTML = `Well done! The correct answer is <strong>${correctAnswer}</strong>.<br><br>${feedback}`;
            } else {
                title.textContent = "Incorrect";
                title.style.color = "#f44336";
                text.innerHTML = `The correct answer is <strong>${correctAnswer}</strong>.<br><br>${feedback}`;
            }
            
            modal.style.display = "flex";
        }
        
        function closeFeedback() {
            document.getElementById("feedbackModal").style.display = "none";
            location.reload(); // Reload to get next question
        }
        
        function highlightCountry(location) {
            const highlight = document.getElementById("countryHighlight");
            highlight.style.display = "block";
            highlight.style.left = location.x + "%";
            highlight.style.top = location.y + "%";
            highlight.style.width = "60px";
            highlight.style.height = "60px";
            
            setTimeout(() => {
                highlight.style.display = "none";
            }, 2500);
        }
        
        // Map zoom functionality
        const map = document.getElementById("worldMap");
        let scale = 1;
        map.addEventListener("wheel", function(event) {
            event.preventDefault();
            scale += event.deltaY * -0.002;
            scale = Math.min(Math.max(1, scale), 3);
            map.style.transform = `scale(${scale})`;
        });
        
        // Initialize speech synthesis
        function initializeVoices() {
            // Speak the current question on load
            speakCurrentQuestion();
        }
        
        window.onload = function() {
            setTimeout(initializeVoices, 200);
        };
    </script>
</body>
</html>