<?php
// Start session and check if user is logged in
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['current_user'])) {
    header('Location: index.php');
    exit();
}

// Include database configuration
require_once 'config/database.php';
require_once 'game.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
$game = new Game($db);

// Get current user data
$current_user = $_SESSION['current_user'];

// Track game start
if (!isset($_SESSION['current_game'])) {
    $_SESSION['current_game'] = [
        'type' => 'memory',
        'subject' => 'political_science',
        'started_at' => date('Y-m-d H:i:s'),
        'level' => 1,
        'score' => 0
    ];
}

// Process game completion if posted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'complete_level') {
        $level = intval($_POST['level']);
        $time_taken = intval($_POST['time_taken']);
        $score = intval($_POST['score']);
        
        // Save game session
        $game_session_id = $game->saveGameSession(
            $current_user['id'],
            'memory',
            'political_science',
            $level,
            $score,
            $time_taken
        );
        
        // Get user's new position
        $user_position = $game->getUserRank($current_user['id']);
        
        echo json_encode([
            'success' => true, 
            'score' => $score,
            'session_id' => $game_session_id,
            'position' => $user_position
        ]);
        exit();
    }
}

// Get user's current level and score from session
$current_level = $_SESSION['current_game']['level'] ?? 1;
$current_score = $_SESSION['current_game']['score'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indian Political Science Memory Game</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #080808;
            text-align: center;
            margin: 0;
            padding: 20px;
            background-image: linear-gradient(rgba(26, 22, 22, 0.338), rgba(48, 39, 39, 0.32)), url('other img/memory.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        h1 {
            color: #ff9933;
            text-shadow: 2px 2px 4px #00000040;
            text-shadow: 0 0 10px rgba(225, 249, 9, 0.973);
        }
        
        .game-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: rgba(39, 63, 37, 0.443);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .level-info {
            font-size: 1.2em;
            margin-bottom: 10px;
            color: #3af429;
            font-weight: bold;
        }
        
        .score-info {
            font-size: 1.1em;
            margin-bottom: 10px;
            color: #ffd700;
            font-weight: bold;
        }
        
        .user-info {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            color: white;
            font-size: 0.9em;
        }
        
        .cards-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin: 20px auto;
        }
        
        .card {
            height: 200px;
            perspective: 1000px;
            cursor: pointer;
        }
        
        .card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }
        
        .card.flipped .card-inner {
            transform: rotateY(180deg);
        }
        
        .card-front, .card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            box-sizing: border-box;
        }
        
        .card-front {
            background-color: #138808;
            color: white;
            font-weight: bold;
            transform: rotateY(180deg);
            font-size: 0.9em;
            text-align: center;
        }
        
        .card-back {
            background-color: #ff9933;
            background-image: url('mm img/card.png');
            background-size: cover;
            background-position: center;
            color: white;
        }
        
        .card img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 3px;
        }
        
        .card.matched {
            visibility: hidden;
        }
        
        .controls {
            margin-top: 20px;
        }
        
        button {
            background-color: #2f3491;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        button:hover {
            background-color: #897bbd;
            transform: scale(1.05);
        }
        
        button:disabled {
            background-color: #666;
            cursor: not-allowed;
            transform: none;
        }
        
        .message {
            margin-top: 20px;
            font-size: 1.2em;
            color: #000080;
            min-height: 30px;
            font-weight: bold;
        }
        
        .timer {
            font-size: 1.2em;
            color: #3434ed;
            margin-bottom: 10px;
            font-weight: bold;
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
        }

        .leaderboard-button {
            background: linear-gradient(45deg, #ffd700, #ff6b00);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            margin: 10px;
            transition: all 0.3s ease;
        }

        .leaderboard-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            
            .card {
                height: 150px;
            }
            
            .game-container {
                padding: 15px;
            }
            
            .back-button {
                position: relative;
                top: auto;
                right: auto;
                margin-bottom: 15px;
            }
            
            .user-info {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 10px;
                display: inline-block;
            }
        }

        @media (max-width: 480px) {
            .cards-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .card {
                height: 120px;
            }
            
            h1 {
                font-size: 1.5em;
            }
        }

        /* Completion animation */
        @keyframes celebrate {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .celebrate {
            animation: celebrate 0.5s ease-in-out 3;
            color: #ffd700;
        }

        .position-badge {
            background: linear-gradient(45deg, #ffd700, #ff6b00);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Player: <?php echo htmlspecialchars($current_user['nickname'] ?? $current_user['username'] ?? 'User'); ?> 
        | Total Score: <span id="total-score"><?php echo $current_score; ?></span>
    </div>

    <a href="ps.php" class="back-button">Back to Games</a>

    <div class="game-container">
        <h1>Indian Political Science Memory Game</h1>
        <div class="level-info">Level: <span id="level"><?php echo $current_level; ?></span></div>
        <div class="score-info">Current Level Score: <span id="current-score">0</span></div>
        <div class="timer">Time: <span id="time">0</span>s</div>
        <div class="message" id="message"></div>
        <div class="cards-container" id="cards-container"></div>
        <div class="controls">
            <button id="restart-btn">Restart Level</button>
            <button id="new-level-btn" disabled>Next Level</button>
            <button id="hint-btn">Hint (Show All)</button>
        </div>
    </div>

    <script>
        // Game data with different levels
        const gameData = [
            // Level 1 - Basic Concepts
            {
                pairs: [
                    {
                        image: "mm img/ashok.jpg",
                        text: "National Emblem of India - Lion Capital of Ashoka"
                    },
                    {
                        image: "mm img/flag.jpg",
                        text: "Indian National Flag - Tricolor with Ashoka Chakra"
                    },
                    {
                        image: "mm img/president.jpg",
                        text: "President of India - Head of State"
                    },
                    {
                        image: "mm img/pm.jpg",
                        text: "Prime Minister of India - Head of Government"
                    },
                    {
                        image: "mm img/supreme.jpg",
                        text: "Supreme Court of India - Highest Judicial Body"
                    }
                ]
            },
            // Level 2 - Freedom Fighters
            {
                pairs: [
                    {
                        image: "mm img/gandhi.jpg",
                        text: "Mahatma Gandhi - Father of the Nation, led Non-violence movement"
                    },
                    {
                        image: "mm img/nehru.jpg",
                        text: "Jawaharlal Nehru - First Prime Minister of India"
                    },
                    {
                        image: "mm img/bose.jpg",
                        text: "Subhas Chandra Bose - Founded Indian National Army"
                    },
                    {
                        image: "mm img/bhagat.jpg",
                        text: "Bhagat Singh - Revolutionary freedom fighter"
                    },
                    {
                        image: "mm img/lakshmibai.jpg",
                        text: "Rani Lakshmibai - Queen of Jhansi who fought British"
                    }
                ]
            },
            // Level 3 - Indian Constitution
            {
                pairs: [
                    {
                        image: "mm img/constitution.jpg",
                        text: "Indian Constitution - Longest written constitution in the world"
                    },
                    {
                        image: "mm img/ambedkar.jpg",
                        text: "Dr. B.R. Ambedkar - Chairman of Drafting Committee of Constitution"
                    },
                    {
                        image: "mm img/preamble.jpg",
                        text: "Preamble - 'We the people of India...' introduction to Constitution"
                    },
                    {
                        image: "mm img/fundamentalright.jpg",
                        text: "Fundamental Rights - Basic rights guaranteed to all citizens"
                    },
                    {
                        image: "mm img/judicialreview.jpg",
                        text: "Judicial Review - Supreme Court can review laws against Constitution"
                    }
                ]
            },
            // Level 4 - Political Parties
            {
                pairs: [
                    {
                        image: "mm img/bjp.jpg",
                        text: "BJP - Bharatiya Janata Party, current ruling party"
                    },
                    {
                        image: "mm img/inc.jpg",
                        text: "INC - Indian National Congress, oldest political party"
                    },
                    {
                        image: "mm img/aap.jpg",
                        text: "AAP - Aam Aadmi Party, founded in 2012"
                    },
                    {
                        image: "mm img/cpi.png",
                        text: "CPI(M) - Communist Party of India (Marxist)"
                    },
                    {
                        image: "mm img/bsp.png",
                        text: "BSP - Bahujan Samaj Party, represents Dalits and minorities"
                    }
                ]
            },
            // Level 5 - Current Leaders
            {
                pairs: [
                    {
                        image: "mm img/pm.jpg",
                        text: "Narendra Modi - Current Prime Minister of India"
                    },
                    {
                        image: "mm img/president.jpg",
                        text: "Droupadi Murmu - Current President of India"
                    },
                    {
                        image: "mm img/jagdeepD.jpg",
                        text: "Jagdeep Dhankhar - Current Vice President of India"
                    },
                    {
                        image: "mm img/amit.jpg",
                        text: "Amit Shah - Union Home Minister"
                    },
                    {
                        image: "mm img/arvind.jpg",
                        text: "Arvind Kejriwal - Delhi Ex Chief Minister"
                    }
                ]
            },
            // Level 6 - State Governments
            {
                pairs: [
                    {
                        image: "mm img/mumbai.jpg",
                        text: "Maharashtra - Capital: Mumbai, Current CM: Eknath Shinde"
                    },
                    {
                        image: "mm img/chennai.jpg",
                        text: "Tamil Nadu - Capital: Chennai, Current CM: M.K. Stalin"
                    },
                    {
                        image: "mm img/lacknow.jpg",
                        text: "Uttar Pradesh - Capital: Lakhnow, CM: Yogi Adityanath"
                    },
                    {
                        image: "mm img/kolkatta.jpg",
                        text: "West Bengal - Capital: Kolkata, CM: Mamata Banerjee"
                    },
                    {
                        image: "mm img/kerala.jpg",
                        text: "Kerala - First Indian state to achieve 100% literacy"
                    }
                ]
            }
        ];
        
        // Game variables
        let currentLevel = <?php echo $current_level - 1; ?>; // Convert to zero-based index
        let cards = [];
        let flippedCards = [];
        let matchedPairs = 0;
        let gameStarted = false;
        let timer = 0;
        let timerInterval;
        let firstView = true;
        let currentLevelScore = 0;
        let totalScore = <?php echo $current_score; ?>;

        // DOM elements
        const cardsContainer = document.getElementById('cards-container');
        const levelDisplay = document.getElementById('level');
        const messageDisplay = document.getElementById('message');
        const restartBtn = document.getElementById('restart-btn');
        const newLevelBtn = document.getElementById('new-level-btn');
        const timerDisplay = document.getElementById('time');
        const hintBtn = document.getElementById('hint-btn');
        const currentScoreDisplay = document.getElementById('current-score');
        const totalScoreDisplay = document.getElementById('total-score');

        // Initialize game
        function initGame() {
            // Reset game state
            cards = [];
            flippedCards = [];
            matchedPairs = 0;
            gameStarted = false;
            currentLevelScore = 0;
            currentScoreDisplay.textContent = currentLevelScore;
            clearInterval(timerInterval);
            timer = 0;
            timerDisplay.textContent = timer;
            messageDisplay.textContent = '';
            messageDisplay.className = 'message';
            
            // Update level display
            levelDisplay.textContent = currentLevel + 1;
            
            // Create cards for current level
            const levelData = gameData[currentLevel];
            const allCards = [];
            
            // Create image cards
            levelData.pairs.forEach((pair, index) => {
                allCards.push({
                    type: 'image',
                    content: pair.image,
                    pairId: index
                });
            });
            
            // Create text cards
            levelData.pairs.forEach((pair, index) => {
                allCards.push({
                    type: 'text',
                    content: pair.text,
                    pairId: index
                });
            });
            
            // Shuffle cards
            cards = shuffleArray(allCards);
            
            // Display cards
            renderCards();
            
            // Show all cards for 3 seconds at first
            if (firstView) {
                flipAllCards(true);
                setTimeout(() => {
                    flipAllCards(false);
                    firstView = false;
                    startGame();
                }, 3000);
            } else {
                startGame();
            }
        }

        // Start the game
        function startGame() {
            gameStarted = true;
            startTimer();
        }

        // Start timer
        function startTimer() {
            timerInterval = setInterval(() => {
                timer++;
                timerDisplay.textContent = timer;
            }, 1000);
        }

        // Render cards on the screen
        function renderCards() {
            cardsContainer.innerHTML = '';
            
            cards.forEach((card, index) => {
                const cardElement = document.createElement('div');
                cardElement.className = `card ${card.flipped ? 'flipped' : ''} ${card.matched ? 'matched' : ''}`;
                cardElement.dataset.index = index;
                
                const cardInner = document.createElement('div');
                cardInner.className = 'card-inner';
                
                const cardFront = document.createElement('div');
                cardFront.className = 'card-front';
                
                if (card.type === 'image') {
                    const img = document.createElement('img');
                    img.src = card.content;
                    img.alt = 'Political Science Image';
                    img.onerror = function() {
                        this.src = 'https://via.placeholder.com/100/138808/ffffff?text=Image';
                    };
                    cardFront.appendChild(img);
                } else {
                    cardFront.textContent = card.content;
                }
                
                const cardBack = document.createElement('div');
                cardBack.className = 'card-back';
                
                cardInner.appendChild(cardBack);
                cardInner.appendChild(cardFront);
                cardElement.appendChild(cardInner);
                
                cardElement.addEventListener('click', () => handleCardClick(index));
                cardsContainer.appendChild(cardElement);
            });
        }

        // Handle card click
        function handleCardClick(index) {
            if (!gameStarted || flippedCards.length >= 2 || cards[index].flipped || cards[index].matched) {
                return;
            }
            
            // Flip the card
            cards[index].flipped = true;
            flippedCards.push(index);
            renderCards();
            
            // Check for match if two cards are flipped
            if (flippedCards.length === 2) {
                const card1 = cards[flippedCards[0]];
                const card2 = cards[flippedCards[1]];
                
                if (card1.pairId === card2.pairId && card1.type !== card2.type) {
                    // Match found
                    card1.matched = true;
                    card2.matched = true;
                    matchedPairs++;
                    
                    // Add points for match
                    const matchPoints = 10 * (currentLevel + 1);
                    currentLevelScore += matchPoints;
                    currentScoreDisplay.textContent = currentLevelScore;
                    
                    flippedCards = [];
                    messageDisplay.textContent = `Correct! +${matchPoints} points`;
                    
                    // Check if level is complete
                    if (matchedPairs === gameData[currentLevel].pairs.length) {
                        completeLevel();
                    }
                } else {
                    // No match
                    messageDisplay.textContent = 'Try Again!';
                    setTimeout(() => {
                        cards[flippedCards[0]].flipped = false;
                        cards[flippedCards[1]].flipped = false;
                        flippedCards = [];
                        renderCards();
                    }, 1000);
                }
            }
        }

        // Complete level
        function completeLevel() {
            clearInterval(timerInterval);
            
            // Calculate time bonus (more points for faster completion)
            const timeBonus = Math.max(0, 100 - timer);
            currentLevelScore += timeBonus;
            currentScoreDisplay.textContent = currentLevelScore;
            totalScore += currentLevelScore;
            totalScoreDisplay.textContent = totalScore;
            
            // Show completion message
            messageDisplay.textContent = `Level Complete! Time: ${timer}s | Score: ${currentLevelScore}`;
            messageDisplay.classList.add('celebrate');
            
            // Enable next level button if there are more levels
            if (currentLevel < gameData.length - 1) {
                newLevelBtn.disabled = false;
            } else {
                messageDisplay.textContent += ' - Congratulations! You completed all levels!';
            }
            
            // Save progress to server
            saveLevelProgress();
        }

        // Save level progress to server
        function saveLevelProgress() {
            const formData = new FormData();
            formData.append('action', 'complete_level');
            formData.append('level', currentLevel + 1);
            formData.append('time_taken', timer);
            formData.append('score', currentLevelScore);
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Progress saved successfully');
                    // Update session data
                    updateSessionData(currentLevel + 1, totalScore);
                    
                    // Show position if available
                    if (data.position) {
                        setTimeout(() => {
                            const positionBadge = document.createElement('div');
                            positionBadge.className = 'position-badge';
                            positionBadge.textContent = `Your Leaderboard Position: #${data.position}`;
                            messageDisplay.appendChild(positionBadge);
                        }, 1000);
                    }
                }
            })
            .catch(error => {
                console.error('Error saving progress:', error);
            });
        }

        // Update session data
        function updateSessionData(level, score) {
            // Update local variables
            currentLevel = level - 1;
            totalScore = score;
            
            // Update display
            levelDisplay.textContent = level;
            totalScoreDisplay.textContent = score;
        }

        // Flip all cards (show or hide)
        function flipAllCards(show) {
            cards.forEach(card => {
                card.flipped = show;
            });
            renderCards();
        }

        // Shuffle array
        function shuffleArray(array) {
            const newArray = [...array];
            for (let i = newArray.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
            }
            return newArray;
        }

        // Event listeners
        restartBtn.addEventListener('click', () => {
            firstView = true;
            initGame();
        });

        newLevelBtn.addEventListener('click', () => {
            if (currentLevel < gameData.length - 1) {
                currentLevel++;
                levelDisplay.textContent = currentLevel + 1;
                newLevelBtn.disabled = true;
                firstView = true;
                initGame();
            }
        });

        hintBtn.addEventListener('click', () => {
            if (gameStarted) {
                flipAllCards(true);
                setTimeout(() => {
                    flipAllCards(false);
                }, 2000);
                
                // Deduct points for using hint
                currentLevelScore = Math.max(0, currentLevelScore - 20);
                currentScoreDisplay.textContent = currentLevelScore;
                messageDisplay.textContent = 'Hint used! -20 points';
            }
        });

        // Start the game
        initGame();

        // Handle keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.location.href = 'ps.php';
            }
            if (e.key === 'h' || e.key === 'H') {
                hintBtn.click();
            }
            if (e.key === 'r' || e.key === 'R') {
                restartBtn.click();
            }
            if (e.key === 'n' || e.key === 'N') {
                if (!newLevelBtn.disabled) {
                    newLevelBtn.click();
                }
            }
            if (e.key === 'l' || e.key === 'L') {
                window.location.href = 'leaderboard.php';
            }
        });
    </script>
</body>
</html>