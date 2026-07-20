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
if (!isset($_SESSION['current_wheel_game'])) {
    $_SESSION['current_wheel_game'] = [
        'type' => 'wheel',
        'subject' => 'political_science',
        'started_at' => date('Y-m-d H:i:s'),
        'level' => 1,
        'score' => 0,
        'questions_answered' => 0
    ];
}

// Process game completion if posted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_progress') {
        $level = intval($_POST['level']);
        $score = intval($_POST['score']);
        $questions_answered = intval($_POST['questions_answered']);
        
        // Update session data
        $_SESSION['current_wheel_game']['level'] = $level;
        $_SESSION['current_wheel_game']['score'] = $score;
        $_SESSION['current_wheel_game']['questions_answered'] = $questions_answered;
        
        // Save to database if significant progress made
        if ($questions_answered % 5 === 0) {
            $game_session_id = $game->saveGameSession(
                $current_user['id'],
                'wheel',
                'political_science',
                $level,
                $score,
                0 // duration not tracked for wheel game
            );
            
            // Get user's new position
            $user_position = $game->getUserRank($current_user['id']);
            
            echo json_encode([
                'success' => true, 
                'session_id' => $game_session_id,
                'position' => $user_position,
                'level_up' => true
            ]);
        } else {
            echo json_encode(['success' => true]);
        }
        exit();
    }
    
    if ($_POST['action'] === 'complete_game') {
        $level = intval($_POST['level']);
        $score = intval($_POST['score']);
        $questions_answered = intval($_POST['questions_answered']);
        
        // Save final game session
        $game_session_id = $game->saveGameSession(
            $current_user['id'],
            'wheel',
            'political_science',
            $level,
            $score,
            0
        );
        
        // Get user's new position
        $user_position = $game->getUserRank($current_user['id']);
        
        // Clear game session
        unset($_SESSION['current_wheel_game']);
        
        echo json_encode([
            'success' => true, 
            'session_id' => $game_session_id,
            'position' => $user_position,
            'final_score' => $score
        ]);
        exit();
    }
}

// Get user's current game data from session
$current_level = $_SESSION['current_wheel_game']['level'] ?? 1;
$current_score = $_SESSION['current_wheel_game']['score'] ?? 0;
$questions_answered = $_SESSION['current_wheel_game']['questions_answered'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wheel Climber - Indian Political Science</title>
    <style>
        body {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background-color: #f0f8ff;
            text-align: center;
            margin: 0;
            padding: 20px;
            color: #333;
            overflow-x: hidden;
            background-image: linear-gradient(rgba(71, 70, 70, 0.7), rgba(100, 98, 98, 0.8)), url('other img/wc_Bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        h1 {
            color: #f0110d;
            text-shadow: 0 0 10px rgba(225, 249, 9, 0.973);
            margin-bottom: 10px;
            font-size: 2rem;
            letter-spacing: 2px;
            font-weight: 2000;
            background: linear-gradient(to right, #630825, #6e0918);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: bold 1s ease-in-out infinite alternate;
        }
        
        .game-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: rgba(149, 206, 231, 0.5);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            position: relative;
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
            backdrop-filter: blur(10px);
        }
        
        .wheel-container {
            position: relative;
            width: 400px;
            height: 400px;
            margin: 20px auto;
        }
        
        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            border: 10px solid #4e4646;
            box-shadow: 0 0 0 5px #fff, 0 0 0 10px #333;
            transition: transform 3s cubic-bezier(0.17, 0.67, 0.12, 0.99);
            transform: rotate(0deg);
        }
        
        .wheel-section {
            position: absolute;
            width: 50%;
            height: 50%;
            transform-origin: bottom right;
            box-sizing: border-box;
        }

        .section-text {
            position: absolute;
            width: 100px;
            text-align: center;
            font-weight: bold;
            color: rgb(249, 242, 242);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            bottom: 30%;
            left: 30%;
            transform: rotate(calc(36deg));
            font-size: 15px;
        }
        
        .wheel-center {
            position: absolute;
            width: 50px;
            height: 50px;
            background-color: #333;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            border: 5px solid white;
        }
        
        .spin-btn {
            background-color: #10e517;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 20px;
            cursor: pointer;
            border-radius: 50px;
            margin-top: 20px;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .spin-btn:hover {
            background-color: #148d1a;
            transform: scale(1.05);
        }
        
        .spin-btn:disabled {
            background-color: #657d93;
            cursor: not-allowed;
            transform: none;
        }
        
        .question-container {
            display: none;
            margin-top: 30px;
            padding: 20px;
            background-color: rgba(83, 139, 223, 0.63);
            border-radius: 10px;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .question {
            font-size: 30px;
            margin-bottom: 20px;
            color: #4f0b0b;
        }
        
        .options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .option-btn {
            font-size: 15px;
            padding: 10px;
            background-color: #8ad3e9;
            color: rgb(17, 11, 11);
            border: none;
            border-color: #333;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option-btn:hover {
            background-color: #bcc5ee;
            transform: scale(1.02);
        }
        
        .option-btn.correct {
            background-color: #4CAF50;
            color: white;
        }
        
        .option-btn.incorrect {
            background-color: #f44336;
            color: white;
        }
        
        .result {
            font-weight: bold;
            margin-top: 10px;
            min-height: 24px;
        }
        
        .next-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 18px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
        
        .next-btn:hover {
            background-color: #c0392b;
        }
        
        .score-container {
            margin-top: 20px;
            font-size: 22px;
            font-weight: bold;
            color: #192736;
        }
        
        .level-indicator {
            margin-top: 10px;
            font-size: 20px;
            color: #a50b51;
            font-weight: 100;
        }
        
        .progress-info {
            margin-top: 10px;
            font-size: 16px;
            color: #2c3e50;
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
        
        .level-up-message {
            background: linear-gradient(45deg, #ffd700, #ff6b00);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            font-weight: bold;
            animation: celebrate 0.5s ease-in-out 3;
        }
        
        @keyframes celebrate {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @media (max-width: 600px) {
            .wheel-container {
                width: 300px;
                height: 300px;
            }
            
            .wheel-section {
                font-size: 15px;
                padding-left: 40px;
                padding-bottom: 40px;
            }
            
            .options {
                grid-template-columns: 1fr;
            }
            
            .user-info {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 10px;
                display: inline-block;
            }
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
        <h1>Wheel Climber</h1>
        
        <div class="level-indicator">Level: <span id="level"><?php echo $current_level; ?></span></div>
        <div class="score-container">Score: <span id="score"><?php echo $current_score; ?></span></div>
        <div class="progress-info">Questions Answered: <span id="questions-answered"><?php echo $questions_answered; ?></span></div>
        
        <div class="wheel-container">
            <div class="wheel" id="wheel">
                <!-- Government & Governance -->
                <div class="wheel-section" style="background-color: #FF6384; transform: rotate(0deg);">
                    <span class="section-text">Government & Governance</span>
                </div>
                
                <!-- Citizenship & Rights -->
                <div class="wheel-section" style="background-color: #36A2EB; transform: rotate(72deg);">
                    <span class="section-text">Citizenship & Rights</span>
                </div>
                
                <!-- Local, State, and National Administration -->
                <div class="wheel-section" style="background-color: #FFCE56; transform: rotate(144deg);">
                    <span class="section-text">Local, State, and National Administration</span>
                </div>
                
                <!-- Laws & Rules -->
                <div class="wheel-section" style="background-color: #4BC0C0; transform: rotate(216deg);">
                    <span class="section-text">Laws & Rules</span>
                </div>
                
                <!-- Social & Cultural Diversity -->
                <div class="wheel-section" style="background-color: #9966FF; transform: rotate(288deg);">
                    <span class="section-text">Social & Cultural Diversity</span>
                </div>
            </div>
            <div class="wheel-center"></div>
        </div>
        
        <button class="spin-btn" id="spinBtn">Spin the Wheel!</button>
        
        <div class="question-container" id="questionContainer">
            <div class="question" id="question"></div>
            <div class="options" id="options"></div>
            <div class="result" id="result"></div>
            <button class="next-btn" id="nextBtn">Next Question</button>
        </div>
    </div>

    <script>
        // Game data - questions organized by topic and difficulty level
        const gameData = {
            "Government & Governance": {
                1: [
                    {
                        question: "Who is the head of the Indian government?",
                        options: ["President", "Prime Minister", "Chief Minister", "Governor"],
                        answer: 1
                    },
                    {
                        question: "What is the term length for a Member of Parliament (MP) in India?",
                        options: ["2 years", "5 years", "6 years", "10 years"],
                        answer: 1
                    },
                    {
                        question: "Which house of Parliament is called the 'House of the People'?",
                        options: ["Rajya Sabha", "Lok Sabha", "Vidhan Sabha", "None of these"],
                        answer: 1
                    },
                    {
                        question: "Who appoints the Prime Minister of India?",
                        options: ["President", "Chief Justice", "People directly", "Governors"],
                        answer: 0
                    },
                    {
                        question: "What is the minimum age to become the Prime Minister of India?",
                        options: ["21 years", "25 years", "30 years", "35 years"],
                        answer: 1
                    }
                ],
                2: [
                    {
                        question: "Which article of the Indian Constitution deals with the Prime Minister's position?",
                        options: ["Article 74", "Article 75", "Article 76", "Article 77"],
                        answer: 1
                    },
                    {
                        question: "What is the maximum strength of the Lok Sabha?",
                        options: ["500", "543", "552", "545"],
                        answer: 2
                    },
                    {
                        question: "Which of these is NOT a function of the Parliament?",
                        options: ["Making laws", "Controlling government finances", "Administering justice", "Amending the Constitution"],
                        answer: 2
                    },
                    {
                        question: "Who presides over the Lok Sabha when the Speaker is absent?",
                        options: ["Prime Minister", "Deputy Speaker", "President", "Vice President"],
                        answer: 1
                    },
                    {
                        question: "What is the name of the money bill that allows the government to spend money?",
                        options: ["Finance Bill", "Appropriation Bill", "Money Bill", "Budget Bill"],
                        answer: 1
                    }
                ],
                3: [
                    {
                        question: "Which constitutional amendment introduced the anti-defection law?",
                        options: ["42nd Amendment", "52nd Amendment", "61st Amendment", "73rd Amendment"],
                        answer: 1
                    },
                    {
                        question: "Who has the power to dissolve the Lok Sabha before its term ends?",
                        options: ["Prime Minister", "President", "Chief Justice", "Speaker"],
                        answer: 1
                    },
                    {
                        question: "What is the term for the collective responsibility of the Council of Ministers to the Lok Sabha?",
                        options: ["Ministerial Responsibility", "Parliamentary Responsibility", "Cabinet Responsibility", "Collective Responsibility"],
                        answer: 3
                    },
                    {
                        question: "Which schedule of the Indian Constitution deals with the allocation of seats in Rajya Sabha?",
                        options: ["Fourth Schedule", "Fifth Schedule", "Sixth Schedule", "Seventh Schedule"],
                        answer: 0
                    },
                    {
                        question: "What is the maximum gap allowed between two sessions of Parliament?",
                        options: ["3 months", "6 months", "9 months", "12 months"],
                        answer: 1
                    }
                ]
            },
            "Citizenship & Rights": {
                1: [
                    {
                        question: "How many Fundamental Rights are guaranteed by the Indian Constitution?",
                        options: ["5", "6", "7", "8"],
                        answer: 1
                    },
                    {
                        question: "Which article of the Constitution abolishes untouchability?",
                        options: ["Article 15", "Article 16", "Article 17", "Article 18"],
                        answer: 2
                    },
                    {
                        question: "What is the minimum voting age in India?",
                        options: ["16 years", "18 years", "21 years", "25 years"],
                        answer: 1
                    },
                    {
                        question: "Which Fundamental Right allows citizens to move to court if their rights are violated?",
                        options: ["Right to Equality", "Right to Freedom", "Right to Constitutional Remedies", "Right against Exploitation"],
                        answer: 2
                    },
                    {
                        question: "Who can become a citizen of India by birth?",
                        options: ["Anyone born in India", "Anyone born to Indian parents", "Both A and B", "Only if both parents are Indian"],
                        answer: 0
                    }
                ],
                2: [
                    {
                        question: "Which article provides for equality before law?",
                        options: ["Article 12", "Article 13", "Article 14", "Article 15"],
                        answer: 2
                    },
                    {
                        question: "Which Fundamental Right was removed by the 44th Amendment Act?",
                        options: ["Right to Property", "Right to Education", "Right to Privacy", "Right to Information"],
                        answer: 0
                    },
                    {
                        question: "Which article deals with protection against arrest and detention?",
                        options: ["Article 20", "Article 21", "Article 22", "Article 23"],
                        answer: 2
                    },
                    {
                        question: "What is the term for the process by which a foreigner can become an Indian citizen?",
                        options: ["Naturalization", "Registration", "Incorporation", "Assimilation"],
                        answer: 0
                    },
                    {
                        question: "Which Fundamental Duty was added by the 86th Amendment Act?",
                        options: ["To protect environment", "To develop scientific temper", "To provide education to children", "To safeguard public property"],
                        answer: 2
                    }
                ],
                3: [
                    {
                        question: "Which case established the 'Basic Structure Doctrine' of the Constitution?",
                        options: ["Golaknath Case", "Kesavananda Bharati Case", "Minerva Mills Case", "Maneka Gandhi Case"],
                        answer: 1
                    },
                    {
                        question: "Which article was described as the 'heart and soul of the Constitution' by Dr. Ambedkar?",
                        options: ["Article 14", "Article 19", "Article 21", "Article 32"],
                        answer: 3
                    },
                    {
                        question: "Which case expanded the interpretation of 'life' under Article 21?",
                        options: ["AK Gopalan Case", "Maneka Gandhi Case", "ADM Jabalpur Case", "SR Bommai Case"],
                        answer: 1
                    },
                    {
                        question: "Which Fundamental Right is available only to citizens and not to foreigners?",
                        options: ["Right to Equality", "Right to Freedom", "Right against Exploitation", "Right to Religion"],
                        answer: 1
                    },
                    {
                        question: "Which article provides for protection of interests of minorities?",
                        options: ["Article 25", "Article 28", "Article 29", "Article 30"],
                        answer: 2
                    }
                ]
            },
            "Local, State, and National Administration": {
                1: [
                    {
                        question: "Who is the constitutional head of a state in India?",
                        options: ["Chief Minister", "Governor", "High Court Judge", "Speaker"],
                        answer: 1
                    },
                    {
                        question: "What is the term length for a Member of Legislative Assembly (MLA)?",
                        options: ["2 years", "5 years", "6 years", "Depends on the state"],
                        answer: 1
                    },
                    {
                        question: "Which body conducts elections in India?",
                        options: ["Supreme Court", "Election Commission", "Parliament", "President's Office"],
                        answer: 1
                    },
                    {
                        question: "What is the third tier of government in India called?",
                        options: ["State Government", "Central Government", "Local Government", "Judiciary"],
                        answer: 2
                    },
                    {
                        question: "Who appoints the Chief Minister of a state?",
                        options: ["Governor", "President", "People directly", "Prime Minister"],
                        answer: 0
                    }
                ],
                2: [
                    {
                        question: "Which constitutional amendment introduced the Panchayati Raj system?",
                        options: ["61st Amendment", "73rd Amendment", "74th Amendment", "86th Amendment"],
                        answer: 1
                    },
                    {
                        question: "What is the tenure of a Panchayat in most states?",
                        options: ["2 years", "3 years", "5 years", "6 years"],
                        answer: 2
                    },
                    {
                        question: "Which article deals with the creation of new states?",
                        options: ["Article 1", "Article 2", "Article 3", "Article 4"],
                        answer: 2
                    },
                    {
                        question: "Who can remove a Governor from office?",
                        options: ["Chief Minister", "State Legislature", "President", "Supreme Court"],
                        answer: 2
                    },
                    {
                        question: "Which schedule of the Constitution deals with the powers of Panchayats?",
                        options: ["Eighth Schedule", "Ninth Schedule", "Eleventh Schedule", "Twelfth Schedule"],
                        answer: 2
                    }
                ],
                3: [
                    {
                        question: "Which article provides for the creation of All India Services?",
                        options: ["Article 308", "Article 309", "Article 310", "Article 311"],
                        answer: 1
                    },
                    {
                        question: "Who appoints the Chief Election Commissioner of India?",
                        options: ["Prime Minister", "President", "Parliament", "Supreme Court"],
                        answer: 1
                    },
                    {
                        question: "Which constitutional body advises the President on the distribution of financial resources?",
                        options: ["Finance Commission", "Planning Commission", "Reserve Bank", "Comptroller and Auditor General"],
                        answer: 0
                    },
                    {
                        question: "Which article provides for the creation of a Finance Commission?",
                        options: ["Article 280", "Article 281", "Article 282", "Article 283"],
                        answer: 0
                    },
                    {
                        question: "Which constitutional amendment introduced the Municipalities?",
                        options: ["73rd Amendment", "74th Amendment", "75th Amendment", "76th Amendment"],
                        answer: 1
                    }
                ]
            },
            "Laws & Rules": {
                1: [
                    {
                        question: "Who is the final interpreter of the Indian Constitution?",
                        options: ["President", "Prime Minister", "Supreme Court", "Parliament"],
                        answer: 2
                    },
                    {
                        question: "What is the minimum age to become a judge of the Supreme Court?",
                        options: ["35 years", "40 years", "45 years", "No minimum age"],
                        answer: 3
                    },
                    {
                        question: "How many judges are there in the Supreme Court of India (excluding the Chief Justice)?",
                        options: ["25", "30", "33", "34"],
                        answer: 2
                    },
                    {
                        question: "Who appoints the judges of the Supreme Court?",
                        options: ["President", "Prime Minister", "Chief Justice", "Parliament"],
                        answer: 0
                    },
                    {
                        question: "Which writ is issued to compel an authority to perform its duty?",
                        options: ["Habeas Corpus", "Mandamus", "Prohibition", "Certiorari"],
                        answer: 1
                    }
                ],
                2: [
                    {
                        question: "Which article provides for the independence of the judiciary?",
                        options: ["Article 50", "Article 121", "Article 124", "Article 147"],
                        answer: 0
                    },
                    {
                        question: "What is the term for the power of the Supreme Court to review its own judgments?",
                        options: ["Judicial Review", "Curative Jurisdiction", "Review Jurisdiction", "Appellate Jurisdiction"],
                        answer: 2
                    },
                    {
                        question: "Which article provides for Public Interest Litigation (PIL)?",
                        options: ["Article 32", "Article 226", "Both A and B", "None of these"],
                        answer: 2
                    },
                    {
                        question: "Who can remove a judge of the Supreme Court?",
                        options: ["President", "Parliament", "Chief Justice", "Prime Minister"],
                        answer: 1
                    },
                    {
                        question: "Which article provides for the establishment of High Courts?",
                        options: ["Article 214", "Article 215", "Article 216", "Article 217"],
                        answer: 0
                    }
                ],
                3: [
                    {
                        question: "Which case established the collegium system for judicial appointments?",
                        options: ["SP Gupta Case", "Supreme Court Advocates-on-Record Case", "NJAC Case", "Kesavananda Bharati Case"],
                        answer: 1
                    },
                    {
                        question: "Which article provides for the power of judicial review?",
                        options: ["Article 13", "Article 32", "Article 226", "All of these"],
                        answer: 3
                    },
                    {
                        question: "What is the retirement age for a Supreme Court judge?",
                        options: ["60 years", "62 years", "65 years", "70 years"],
                        answer: 2
                    },
                    {
                        question: "Which article provides for the advisory jurisdiction of the Supreme Court?",
                        options: ["Article 143", "Article 144", "Article 145", "Article 146"],
                        answer: 0
                    },
                    {
                        question: "Which case established that the basic structure of the Constitution cannot be amended?",
                        options: ["Golaknath Case", "Kesavananda Bharati Case", "Minerva Mills Case", "SR Bommai Case"],
                        answer: 1
                    }
                ]
            },
            "Social & Cultural Diversity": {
                1: [
                    {
                        question: "Which article provides for the official language of the Union?",
                        options: ["Article 343", "Article 344", "Article 345", "Article 346"],
                        answer: 0
                    },
                    {
                        question: "How many languages are recognized in the Eighth Schedule of the Constitution?",
                        options: ["18", "20", "22", "24"],
                        answer: 2
                    },
                    {
                        question: "Which article provides for the protection of monuments of national importance?",
                        options: ["Article 48", "Article 49", "Article 50", "Article 51"],
                        answer: 1
                    },
                    {
                        question: "Which article provides for the promotion of Hindi as the official language?",
                        options: ["Article 351", "Article 352", "Article 353", "Article 354"],
                        answer: 0
                    },
                    {
                        question: "Which article provides for the protection of interests of minorities?",
                        options: ["Article 29", "Article 30", "Both A and B", "None of these"],
                        answer: 2
                    }
                ],
                2: [
                    {
                        question: "Which article provides for the establishment of a National Commission for SCs?",
                        options: ["Article 338", "Article 339", "Article 340", "Article 341"],
                        answer: 0
                    },
                    {
                        question: "Which article provides for the appointment of a Special Officer for linguistic minorities?",
                        options: ["Article 347", "Article 348", "Article 349", "Article 350B"],
                        answer: 3
                    },
                    {
                        question: "Which article provides for the promotion of educational and economic interests of weaker sections?",
                        options: ["Article 45", "Article 46", "Article 47", "Article 48"],
                        answer: 1
                    },
                    {
                        question: "Which article provides for the protection of tribal rights in scheduled areas?",
                        options: ["Article 244", "Article 245", "Article 246", "Article 247"],
                        answer: 0
                    },
                    {
                        question: "Which schedule deals with the administration of tribal areas in northeastern states?",
                        options: ["Fifth Schedule", "Sixth Schedule", "Seventh Schedule", "Eighth Schedule"],
                        answer: 1
                    }
                ],
                3: [
                    {
                        question: "Which case upheld the constitutional validity of reservations in promotions?",
                        options: ["Indra Sawhney Case", "M Nagaraj Case", "Ashoka Kumar Thakur Case", "Jarnail Singh Case"],
                        answer: 1
                    },
                    {
                        question: "Which article was added by the 97th Amendment to promote cooperative societies?",
                        options: ["Article 43A", "Article 43B", "Article 43C", "Article 43D"],
                        answer: 1
                    },
                    {
                        question: "Which article provides for the protection of wildlife and forests?",
                        options: ["Article 48", "Article 48A", "Article 49", "Article 50"],
                        answer: 1
                    },
                    {
                        question: "Which article provides for the promotion of international peace and security?",
                        options: ["Article 49", "Article 50", "Article 51", "Article 52"],
                        answer: 2
                    },
                    {
                        question: "Which case upheld the constitutional validity of the Right to Education Act?",
                        options: ["Mohini Jain Case", "Unnikrishnan Case", "TMA Pai Case", "Society for Unaided Private Schools Case"],
                        answer: 3
                    }
                ]
            }
        };

        // Game variables
        let currentTopic = "";
        let currentLevel = <?php echo $current_level; ?>;
        let score = <?php echo $current_score; ?>;
        let questionsAnswered = <?php echo $questions_answered; ?>;
        let currentQuestion = null;
        let wheelSpinning = false;

        // DOM elements
        const wheel = document.getElementById("wheel");
        const spinBtn = document.getElementById("spinBtn");
        const questionContainer = document.getElementById("questionContainer");
        const questionElement = document.getElementById("question");
        const optionsElement = document.getElementById("options");
        const resultElement = document.getElementById("result");
        const nextBtn = document.getElementById("nextBtn");
        const scoreElement = document.getElementById("score");
        const levelElement = document.getElementById("level");
        const questionsAnsweredElement = document.getElementById("questions-answered");
        const totalScoreElement = document.getElementById("total-score");

        // Initialize the game
        function initGame() {
            spinBtn.addEventListener("click", spinWheel);
            nextBtn.addEventListener("click", nextQuestion);
            updateDisplay();
        }

        // Spin the wheel
        function spinWheel() {
            if (wheelSpinning) return;
            
            wheelSpinning = true;
            spinBtn.disabled = true;
            
            // Random rotation (5 full rotations + random angle to land on a segment)
            const segments = 5;
            const segmentAngle = 360 / segments;
            const extraRotation = 5 * 360; // 5 full rotations
            const randomAngle = Math.floor(Math.random() * segmentAngle);
            const targetAngle = extraRotation + (segmentAngle * Math.floor(Math.random() * segments)) + randomAngle;
            
            wheel.style.transform = `rotate(${-targetAngle}deg)`;
            
            // Determine the selected topic after animation completes
            setTimeout(() => {
                const normalizedAngle = targetAngle % 360;
                const selectedSegment = Math.floor(normalizedAngle / segmentAngle);
                
                const topics = [
                    "Government & Governance",
                    "Citizenship & Rights",
                    "Local, State, and National Administration",
                    "Laws & Rules",
                    "Social & Cultural Diversity"
                ];
                
                currentTopic = topics[selectedSegment];
                showQuestion();
            }, 3000);
        }

        // Show a question from the selected topic
        function showQuestion() {
            // Get questions for current topic and level
            const topicQuestions = gameData[currentTopic];
            const levelQuestions = topicQuestions[Math.min(currentLevel, 3)]; // Cap at level 3
            
            // Select a random question
            currentQuestion = levelQuestions[Math.floor(Math.random() * levelQuestions.length)];
            
            // Display the question
            questionElement.textContent = currentQuestion.question;
            
            // Display options
            optionsElement.innerHTML = "";
            currentQuestion.options.forEach((option, index) => {
                const button = document.createElement("button");
                button.className = "option-btn";
                button.textContent = option;
                button.addEventListener("click", () => checkAnswer(index));
                optionsElement.appendChild(button);
            });
            
            // Show the question container
            questionContainer.style.display = "block";
            resultElement.textContent = "";
            nextBtn.style.display = "none";
            
            // Scroll to question
            questionContainer.scrollIntoView({ behavior: "smooth" });
        }

        // Check the selected answer
        function checkAnswer(selectedIndex) {
            // Disable all option buttons
            const optionButtons = document.querySelectorAll(".option-btn");
            optionButtons.forEach(button => {
                button.disabled = true;
            });
            
            // Highlight correct and incorrect answers
            optionButtons[currentQuestion.answer].classList.add("correct");
            if (selectedIndex !== currentQuestion.answer) {
                optionButtons[selectedIndex].classList.add("incorrect");
            }
            
            // Check if answer is correct
            if (selectedIndex === currentQuestion.answer) {
                resultElement.textContent = "Correct! Well done!";
                resultElement.style.color = "green";
                score += currentLevel * 10; // More points for higher levels
            } else {
                resultElement.textContent = `Incorrect. The correct answer is: ${currentQuestion.options[currentQuestion.answer]}`;
                resultElement.style.color = "red";
            }
            
            // Show next button
            nextBtn.style.display = "inline-block";
            
            // Increase questions answered count
            questionsAnswered++;
            
            // Update display
            updateDisplay();
            
            // Save progress
            saveProgress();
            
            // Check if level should increase (every 5 questions)
            if (questionsAnswered % 5 === 0 && currentLevel < 3) {
                currentLevel++;
                updateDisplay();
                showLevelUpMessage();
            }
        }

        // Show level up message
        function showLevelUpMessage() {
            const message = document.createElement("div");
            message.className = "level-up-message";
            message.textContent = `🎉 Congratulations! You've reached Level ${currentLevel}! 🎉`;
            questionContainer.insertBefore(message, questionContainer.firstChild);
            
            // Save progress with level up
            saveProgress(true);
        }

        // Move to next question
        function nextQuestion() {
            questionContainer.style.display = "none";
            wheelSpinning = false;
            spinBtn.disabled = false;
            
            // Remove level up message if present
            const levelUpMessage = document.querySelector(".level-up-message");
            if (levelUpMessage) {
                levelUpMessage.remove();
            }
        }

        // Update display
        function updateDisplay() {
            scoreElement.textContent = score;
            levelElement.textContent = currentLevel;
            questionsAnsweredElement.textContent = questionsAnswered;
            totalScoreElement.textContent = score;
        }

        // Save progress to server
        function saveProgress(levelUp = false) {
            const formData = new FormData();
            formData.append('action', 'save_progress');
            formData.append('level', currentLevel);
            formData.append('score', score);
            formData.append('questions_answered', questionsAnswered);
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Progress saved successfully');
                    if (data.level_up && data.position) {
                        setTimeout(() => {
                            resultElement.innerHTML += `<br><small>Your Leaderboard Position: #${data.position}</small>`;
                        }, 500);
                    }
                }
            })
            .catch(error => {
                console.error('Error saving progress:', error);
            });
        }

        // Complete game (when user leaves)
        function completeGame() {
            const formData = new FormData();
            formData.append('action', 'complete_game');
            formData.append('level', currentLevel);
            formData.append('score', score);
            formData.append('questions_answered', questionsAnswered);
            
            // Send beacon for reliable completion tracking
            navigator.sendBeacon('<?php echo $_SERVER['PHP_SELF']; ?>', formData);
        }

        // Save progress when page is about to unload
        window.addEventListener('beforeunload', completeGame);

        // Start the game when page loads
        window.onload = initGame;

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.location.href = 'ps.php';
            }
            if (e.key === 'l' || e.key === 'L') {
                window.location.href = 'leaderboard.php';
            }
        });
    </script>
</body>
</html>