<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Indian Political Science - Read to Learn</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f5f5f5;
      color: #333;
      background-image: linear-gradient(to right, #1e3b723c, #2a52984e),url("law.jpg");
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }

    .read-to-learn-container {
      max-width: 800px;
      margin: 20px auto;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .reading-header {
      padding: 20px;
      background: #FF9933; /* Saffron */
      color: white;
      text-align: center;
    }

    .progress-bar {
      height: 5px;
      background: #e0e0e0;
      border-radius: 5px;
      margin-top: 10px;
    }

    .progress-fill {
      height: 100%;
      background: #138808; /* Green */
      border-radius: 5px;
      width: 0%;
      transition: width 0.3s ease;
    }

    .reading-content {
      padding: 20px;
      max-height: 60vh;
      overflow-y: auto;
      line-height: 1.6;
    }

    .key-term {
      color: #000080; /* Navy Blue */
      cursor: pointer;
      text-decoration: underline dotted;
      font-weight: bold;
    }

    .key-term:hover {
      color: #1E3F8B;
    }

    .term-popup {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .popup-content {
      background: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 500px;
      width: 80%;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .reading-footer {
      padding: 15px;
      background: #f5f5f5;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid #ddd;
    }

    .quiz-controls {
      display: flex;
      gap: 10px;
    }

    .quiz-score {
      font-weight: bold;
      color: #138808; /* Green */
    }

    .inline-quiz {
      background: #f0f8ff;
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
      border: 1px solid #ddd;
    }

    .content-image {
      margin: 20px 0;
      text-align: center;
    }

    .content-image img {
      max-width: 100%;
      border-radius: 8px;
      border: 1px solid #ddd;
    }

    .image-caption {
      font-style: italic;
      color: #666;
      margin-top: 5px;
    }

    button {
      background: #000080; /* Navy Blue */
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.3s;
    }

    button:hover {
      background: #1E3F8B;
    }

    button:disabled {
      background: #cccccc;
      cursor: not-allowed;
    }

    label {
      display: block;
      margin: 8px 0;
      cursor: pointer;
    }

    input[type="radio"] {
      margin-right: 8px;
    }

    .quiz-feedback {
      margin-top: 10px;
      padding: 10px;
      border-radius: 5px;
      display: none;
    }

    .correct {
      background-color: #d4edda;
      color: #155724;
    }

    .incorrect {
      background-color: #f8d7da;
      color: #721c24;
    }
   
    /* Back Button */
    .back-btn {
      position: absolute;
      top: 20px;
      left: 20px;
      background: rgb(255, 255, 255);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: rgb(0, 0, 0);
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 10;
      text-decoration: none;
      font-size: 24px; /* Increased arrow size */
      font-weight: bold; /* Makes arrow thicker */
    }
        
    .back-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateX(-3px);
    }
  </style>
</head>
<body>
  <a href="home.html" class="back-btn">
    ←
  </a>
  <div class="read-to-learn-container">
    <div class="reading-header">
      <h1 id="content-title">Indian Political System</h1>
      <div class="progress-bar">
        <div class="progress-fill" id="progress-fill"></div>
      </div>
    </div>

    <div class="reading-content" id="reading-content">
      <?php
      // Indian Political Science Content with 5 Quiz Questions
      $contentData = array(
        "indian-constitution" => array(
          "id" => "indian-constitution",
          "title" => "The Indian Constitution",
          "category" => "political-science",
          "text" => array(
            array(
              "type" => "paragraph",
              "content" => "The {Constitution of India} is the supreme law of India, adopted on {November 26, 1949} and came into effect on {January 26, 1950}."
            ),
            array(
              "type" => "paragraph",
              "content" => "It establishes India as a {sovereign, socialist, secular, democratic republic} and contains {fundamental rights}, {directive principles}, and {fundamental duties} of citizens."
            ),
            array(
              "type" => "image",
              "url" => "indianconst.jpg",
              "caption" => "Original copy of the Constitution of India"
            ),
            array(
              "type" => "quiz",
              "question" => "What does the term 'sovereign' mean in the Indian Constitution context?",
              "options" => array(
                "India is dependent on other countries",
                "India is free to govern itself without external interference",
                "India follows religious laws",
                "India has a monarchy system"
              ),
              "correct" => 1,
              "explanation" => "Sovereign means India is an independent nation and not under the control of any foreign power."
            ),
            array(
              "type" => "paragraph",
              "content" => "The constitution provides for a {parliamentary system} of government with a {President} as head of state and {Prime Minister} as head of government."
            ),
            array(
              "type" => "quiz",
              "question" => "Which date is celebrated as Republic Day in India?",
              "options" => array(
                "August 15, 1947",
                "November 26, 1949",
                "January 26, 1950",
                "January 30, 1948"
              ),
              "correct" => 2,
              "explanation" => "January 26, 1950 is when the Constitution came into effect, marking India's transition to a republic."
            ),
            array(
              "type" => "quiz",
              "question" => "What are Fundamental Rights in the Indian Constitution?",
              "options" => array(
                "Guidelines for government policy making",
                "Basic human rights guaranteed to all citizens",
                "Duties of citizens towards the nation",
                "Special privileges for government officials"
              ),
              "correct" => 1,
              "explanation" => "Fundamental Rights (Articles 12-35) are basic human rights guaranteed to all citizens."
            ),
            array(
              "type" => "quiz",
              "question" => "What is the role of the President in India's parliamentary system?",
              "options" => array(
                "Head of government who runs daily administration",
                "Constitutional head of state with ceremonial functions",
                "Leader of the ruling political party",
                "Chief justice of the Supreme Court"
              ),
              "correct" => 1,
              "explanation" => "The President is the constitutional head of state while real executive power lies with the Prime Minister."
            ),
            array(
              "type" => "quiz",
              "question" => "What do Directive Principles of State Policy represent?",
              "options" => array(
                "Laws that citizens must follow",
                "Guidelines for the government to establish social justice",
                "Rules for conducting elections",
                "Powers of the judiciary"
              ),
              "correct" => 1,
              "explanation" => "Directive Principles (Articles 36-51) are guidelines for the state to establish a just society."
            )
          ),
          "keyTerms" => array(
            "Constitution of India" => "The supreme legal document that frames the political principles, establishes the structure, procedures, powers and duties of government institutions",
            "November 26, 1949" => "Date when the Constitution was adopted by the Constituent Assembly",
            "January 26, 1950" => "Date when the Constitution came into effect, celebrated as Republic Day",
            "sovereign, socialist, secular, democratic republic" => "The four key words added to the preamble through the 42nd Amendment",
            "fundamental rights" => "Basic human rights guaranteed to all citizens (Articles 12-35)",
            "directive principles" => "Guidelines for the state to establish a just society (Articles 36-51)",
            "fundamental duties" => "Moral obligations of all citizens (Article 51A)",
            "parliamentary system" => "System where the executive derives its legitimacy from and is accountable to the legislature",
            "President" => "The constitutional head of India (Article 52)",
            "Prime Minister" => "The real executive head who leads the Council of Ministers"
          )
        )
      );

      // Load content
      function loadContent($topicId) {
        global $contentData;
        $content = $contentData[$topicId];
        if (!$content) return;
        
        foreach ($content["text"] as $index => $item) {
          if ($item["type"] === "paragraph") {
            echo '<p>' . processTextWithTerms($item["content"], $content["keyTerms"]) . '</p>';
          } else if ($item["type"] === "image") {
            echo '<div class="content-image">';
            echo '<img src="' . $item["url"] . '" alt="' . $item["caption"] . '">';
            echo '<p class="image-caption">' . $item["caption"] . '</p>';
            echo '</div>';
          } else if ($item["type"] === "quiz") {
            echo '<div class="inline-quiz" style="display: none;" id="quiz-' . $index . '">';
            echo '<h4>' . $item["question"] . '</h4>';
            
            foreach ($item["options"] as $optIndex => $option) {
              echo '<label>';
              echo '<input type="radio" name="quiz-' . $index . '" value="' . $optIndex . '">';
              echo $option;
              echo '</label>';
            }
            
            echo '<div class="quiz-feedback" id="feedback-' . $index . '"></div>';
            echo '</div>';
          }
        }
      }

      // Process text to make key terms clickable
      function processTextWithTerms($text, $keyTerms) {
        $parts = preg_split('/(\{[^}]+\})/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        
        foreach ($parts as $part) {
          if (preg_match('/^\{([^}]+)\}$/', $part, $matches)) {
            $term = $matches[1];
            if (isset($keyTerms[$term])) {
              $result .= '<span class="key-term" onclick="showTermPopup(\'' . addslashes($term) . '\')">' . $term . '</span>';
            } else {
              $result .= $part;
            }
          } else {
            $result .= $part;
          }
        }
        
        return $result;
      }

      // Display the content
      loadContent('indian-constitution');
      ?>
    </div>

    <div class="reading-footer">
      <div class="quiz-controls">
        <button id="quiz-toggle">Start Quiz</button>
        <button id="next-question" disabled>Next Question</button>
      </div>
      <div class="quiz-score" id="quiz-score"></div>
    </div>
  </div>

  <!-- Term Popup -->
  <div class="term-popup" id="term-popup">
    <div class="popup-content">
      <h3 id="popup-term"></h3>
      <p id="popup-definition"></p>
      <button onclick="closePopup()">Close</button>
    </div>
  </div>

  <script>
    // Convert PHP array to JavaScript object
    const contentData = <?php echo json_encode($contentData); ?>;

    // DOM Elements
    const readingContent = document.getElementById('reading-content');
    const contentTitle = document.getElementById('content-title');
    const progressFill = document.getElementById('progress-fill');
    const quizToggle = document.getElementById('quiz-toggle');
    const nextQuestionBtn = document.getElementById('next-question');
    const quizScore = document.getElementById('quiz-score');
    const termPopup = document.getElementById('term-popup');
    const popupTerm = document.getElementById('popup-term');
    const popupDefinition = document.getElementById('popup-definition');

    // State variables
    let quizAnswers = {};
    let showQuiz = false;
    let score = 0;
    let currentQuizIndex = -1;
    let quizElements = [];

    // Initialize the app
    function init() {
      // Collect all quiz elements
      quizElements = Array.from(document.querySelectorAll('.inline-quiz'));
      
      // Add event listeners to quiz inputs
      quizElements.forEach((quiz, index) => {
        const inputs = quiz.querySelectorAll('input[type="radio"]');
        inputs.forEach(input => {
          input.addEventListener('change', () => {
            // Find the correct answer for this quiz
            const quizIndex = parseInt(quiz.id.split('-')[1]);
            const correctAnswer = contentData['indian-constitution'].text[quizIndex].correct;
            handleQuizAnswer(quizIndex, parseInt(input.value), correctAnswer);
          });
        });
      });
      
      document.getElementById('reading-content').addEventListener('scroll', updateProgress);
      quizToggle.addEventListener('click', startQuiz);
      nextQuestionBtn.addEventListener('click', showNextQuestion);
    }

    // Start the quiz
    function startQuiz() {
      showQuiz = true;
      quizToggle.textContent = 'End Quiz';
      quizToggle.removeEventListener('click', startQuiz);
      quizToggle.addEventListener('click', endQuiz);
      currentQuizIndex = -1;
      score = 0; // Reset score when starting quiz
      quizAnswers = {}; // Reset answers
      showNextQuestion();
    }

    // End the quiz
    function endQuiz() {
      showQuiz = false;
      quizToggle.textContent = 'Restart Quiz';
      quizToggle.removeEventListener('click', endQuiz);
      quizToggle.addEventListener('click', startQuiz);
      nextQuestionBtn.disabled = true;
      
      // Hide all quizzes
      quizElements.forEach(quiz => {
        quiz.style.display = 'none';
      });
      
      // Show final score
      const totalQuizzes = quizElements.length;
      quizScore.textContent = `Final Score: ${score}/${totalQuizzes}`;
      
      // Reset for next attempt
      currentQuizIndex = -1;
    }

    // Show next question
    function showNextQuestion() {
      // Hide current quiz
      if (currentQuizIndex >= 0 && currentQuizIndex < quizElements.length) {
        quizElements[currentQuizIndex].style.display = 'none';
      }
      
      // Show next quiz
      currentQuizIndex++;
      if (currentQuizIndex < quizElements.length) {
        quizElements[currentQuizIndex].style.display = 'block';
        nextQuestionBtn.disabled = true;
        
        // Update quiz progress
        quizScore.textContent = `Question ${currentQuizIndex + 1} of ${quizElements.length}`;
        
        // Scroll to the question
        quizElements[currentQuizIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else {
        endQuiz();
      }
    }

    // Update reading progress
    function updateProgress() {
      const scrollTop = readingContent.scrollTop;
      const scrollHeight = readingContent.scrollHeight - readingContent.clientHeight;
      const newProgress = Math.round((scrollTop / scrollHeight) * 100);
      progressFill.style.width = `${newProgress}%`;
    }

    // Handle quiz answer selection
    function handleQuizAnswer(questionIndex, answerIndex, correctAnswer) {
      quizAnswers[questionIndex] = answerIndex;
      const feedback = document.getElementById(`feedback-${questionIndex}`);
      
      // Reset all feedback styles first
      feedback.className = "quiz-feedback";
      
      if (answerIndex === correctAnswer) {
        feedback.textContent = "✓ Correct! " + contentData['indian-constitution'].text[questionIndex].explanation;
        feedback.classList.add("correct");
        // Only increase score if not previously answered correctly
        if (!quizAnswers[`scored-${questionIndex}`]) {
          score++;
          quizAnswers[`scored-${questionIndex}`] = true;
        }
      } else {
        feedback.textContent = "✗ Incorrect. " + contentData['indian-constitution'].text[questionIndex].explanation;
        feedback.classList.add("incorrect");
      }
      
      feedback.style.display = 'block';
      nextQuestionBtn.disabled = false;
      
      // Update score display
      quizScore.textContent = `Score: ${score}/${quizElements.length}`;
    }

    // Show term popup
    function showTermPopup(term) {
      const content = contentData['indian-constitution'];
      popupTerm.textContent = term;
      popupDefinition.textContent = content.keyTerms[term] || 'Definition not found';
      termPopup.style.display = 'flex';
    }

    // Close popup
    function closePopup() {
      termPopup.style.display = 'none';
    }

    // Initialize when page loads
    window.onload = init;
  </script>
</body>
</html>