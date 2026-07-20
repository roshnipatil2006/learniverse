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

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get current user data
$current_user = $_SESSION['current_user'];

// Function to save game session and award coins
function saveGameSession($user_id, $game_type, $subject, $level, $score, $duration, $db) {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // 1. Insert game session record
        $stmt = $db->prepare("
            INSERT INTO game_sessions (user_id, game_type, subject, level, score, duration) 
            VALUES (:user_id, :game_type, :subject, :level, :score, :duration)
        ");
        
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':game_type', $game_type);
        $stmt->bindValue(':subject', $subject);
        $stmt->bindValue(':level', $level);
        $stmt->bindValue(':score', $score);
        $stmt->bindValue(':duration', $duration);
        $stmt->execute();
        
        $game_session_id = $db->lastInsertId();
        
        // 2. Award 50 coins for winning the game
        $update_coins = $db->prepare("
            UPDATE users 
            SET coins = coins + 50, 
                updated_at = NOW() 
            WHERE id = :user_id
        ");
        $update_coins->bindValue(':user_id', $user_id);
        $update_coins->execute();
        
        // 3. Update current streak
        $update_streak = $db->prepare("
            UPDATE users 
            SET current_streak = current_streak + 1,
                updated_at = NOW()
            WHERE id = :user_id
        ");
        $update_streak->bindValue(':user_id', $user_id);
        $update_streak->execute();
        
        // 4. Update leaderboard
        $update_leaderboard = $db->prepare("
            INSERT INTO leaderboard (user_id, total_score, games_played, average_score, last_played)
            VALUES (:user_id, :total_score, 1, :average_score, NOW())
            ON DUPLICATE KEY UPDATE 
                total_score = total_score + :update_score,
                games_played = games_played + 1,
                average_score = (total_score + :update_score) / (games_played + 1),
                last_played = NOW()
        ");
        $update_leaderboard->bindValue(':user_id', $user_id);
        $update_leaderboard->bindValue(':total_score', $score);
        $update_leaderboard->bindValue(':average_score', $score);
        $update_leaderboard->bindValue(':update_score', $score);
        $update_leaderboard->execute();
        
        // Commit transaction
        $db->commit();
        
        // Get updated coins count
        $coins_stmt = $db->prepare("SELECT coins FROM users WHERE id = :user_id");
        $coins_stmt->bindValue(':user_id', $user_id);
        $coins_stmt->execute();
        $user_data = $coins_stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'message' => 'Game completed successfully!',
            'coins_awarded' => 50,
            'new_coins' => $user_data['coins'],
            'game_session_id' => $game_session_id
        ];
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        error_log("Game completion error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Failed to save game: ' . $e->getMessage()
        ];
    }
}

// Handle AJAX request for saving game session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_game'])) {
    $required_fields = ['game_type', 'subject', 'level', 'score', 'duration'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit();
    }
    
    $game_type = $_POST['game_type'];
    $subject = $_POST['subject'];
    $level = intval($_POST['level']);
    $score = intval($_POST['score']);
    $duration = intval($_POST['duration']);
    $user_id = $current_user['id'];
    
    $result = saveGameSession($user_id, $game_type, $subject, $level, $score, $duration, $db);
    
    // Update session user data
    if ($result['success']) {
        $user_query = "SELECT * FROM users WHERE id = :user_id";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindValue(':user_id', $user_id);
        $user_stmt->execute();
        $updated_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($updated_user) {
            $_SESSION['current_user'] = $updated_user;
        }
    }
    
    echo json_encode($result);
    exit();
}

// Game data
$keywords = [
    ['name' => "Sovereign", 'meaning' => "India is free from external control", 'opposite' => "Dependent"],
    ['name' => "Socialist", 'meaning' => "Wealth is distributed to reduce inequality", 'opposite' => "Capitalist"],
    ['name' => "Secular", 'meaning' => "No official religion, all religions equal", 'opposite' => "Theocratic"],
    ['name' => "Democratic", 'meaning' => "People elect their government", 'opposite' => "Autocratic"],
    ['name' => "Republic", 'meaning' => "Head of state is elected, not hereditary", 'opposite' => "Monarchy"],
    ['name' => "Justice", 'meaning' => "Fairness in all aspects of society", 'opposite' => "Injustice"],
    ['name' => "Liberty", 'meaning' => "Freedom of thought and expression", 'opposite' => "Oppression"],
    ['name' => "Equality", 'meaning' => "Equal status and opportunity", 'opposite' => "Discrimination"],
    ['name' => "Fraternity", 'meaning' => "Brotherhood and unity among citizens", 'opposite' => "Hostility"]
];

$specialPositions = [
    5 => ['type' => "ladder", 'target' => 15, 'keyword' => $keywords[0], 'img' => "snake img/l1.png"],
    12 => ['type' => "ladder", 'target' => 28, 'keyword' => $keywords[1], 'img' => "snake img/l2.png"],
    22 => ['type' => "ladder", 'target' => 42, 'keyword' => $keywords[2], 'img' => "snake img/l3.png"],
    35 => ['type' => "ladder", 'target' => 55, 'keyword' => $keywords[3], 'img' => "snake img/l4.png"],
    48 => ['type' => "ladder", 'target' => 68, 'keyword' => $keywords[4], 'img' => "snake img/l5.png"],
    63 => ['type' => "ladder", 'target' => 83, 'keyword' => $keywords[5], 'img' => "snake img/l6.png"],
    72 => ['type' => "ladder", 'target' => 86, 'keyword' => $keywords[6], 'img' => "snake img/l7.png"],
    79 => ['type' => "ladder", 'target' => 93, 'keyword' => $keywords[7], 'img' => "snake img/l8.png"],
    88 => ['type' => "ladder", 'target' => 96, 'keyword' => $keywords[8], 'img' => "snake img/l1.png"],
    
    17 => ['type' => "snake", 'target' => 7, 'keyword' => $keywords[0], 'img' => "snake img/s1.png"],
    25 => ['type' => "snake", 'target' => 13, 'keyword' => $keywords[1], 'img' => "snake img/s2.png"],
    37 => ['type' => "snake", 'target' => 23, 'keyword' => $keywords[2], 'img' => "snake img/s3.png"],
    44 => ['type' => "snake", 'target' => 31, 'keyword' => $keywords[3], 'img' => "snake img/s6.png"],
    59 => ['type' => "snake", 'target' => 41, 'keyword' => $keywords[4], 'img' => "snake img/s7.png"],
    66 => ['type' => "snake", 'target' => 52, 'keyword' => $keywords[5], 'img' => "snake img/s8.png"],
    74 => ['type' => "snake", 'target' => 62, 'keyword' => $keywords[6], 'img' => "snake img/s5.png"],
    85 => ['type' => "snake", 'target' => 71, 'keyword' => $keywords[7], 'img' => "snake img/s9.png"],
    92 => ['type' => "snake", 'target' => 78, 'keyword' => $keywords[8], 'img' => "snake img/s4.png"]
];

// Convert PHP arrays to JSON for JavaScript
$keywordsJson = json_encode($keywords);
$specialPositionsJson = json_encode($specialPositions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indian Constitution Preamble Snakes & Ladders</title>
    <style>
        /* Your existing CSS styles remain exactly the same */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: rgba(10, 10, 10, 0.817);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #e0e0e0;
            overflow-x: hidden;
            background-image: linear-gradient(rgba(45, 38, 38, 0.7), rgba(36, 32, 32, 0.8)), url('snake img/snakebg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        
        /* Back Button Styles */
        .back-button-container {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px 10px 40px;
            background-color: rgba(20, 20, 20, 0.7);
            color: #e0e0e0;
            text-decoration: none;
            border-radius: 30px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(5px);
        }
        
        .back-button .arrow {
            margin-right: 10px;
            transition: transform 0.3s ease;
        }
        
        .back-button .text {
            position: relative;
            z-index: 2;
        }
        
        .back-button::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 10px;
            height: 100%;
            background: linear-gradient(to bottom, #2196F3, #1976D2);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .back-button:hover {
            color: #fff;
            padding-left: 45px;
            background-color: rgba(30, 30, 30, 0.9);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }
        
        .back-button:hover .arrow {
            transform: translateX(-5px);
        }
        
        .back-button:hover::before {
            transform: translateX(0);
        }
        
        .back-button:hover::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
              rgba(33, 150, 243, 0.1), 
              rgba(33, 150, 243, 0.05), 
              transparent);
            pointer-events: none;
        }
        
        h1 {
            color: #f6e849;
            text-shadow: 0 0 15px rgb(255, 213, 5);
            margin-bottom: 10px;
            font-size: 4rem;
            letter-spacing: 2px;
            font-weight: 700;
            background: linear-gradient(to right, #fda603, #d0ff00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow {
            from {
                text-shadow: 0 0 5px rgba(255, 153, 51, 0.986);
            }
            to {
                text-shadow: 0 0 15px rgba(255, 197, 51, 0.929), 0 0 30px rgba(255, 215, 0, 0.6);
            }
        }
        
        h2 {
            color: #f4ebeb;
            margin-top: 0;
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .game-area {
            display: flex;
            width: 100%;
            margin-top: 20px;
            background: rgba(20, 20, 20, 0.441);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.283);
            backdrop-filter: blur(3px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .board-container {
            flex: 3;
            display: flex;
            justify-content: center;
            padding: 15px;
            background: rgba(10, 10, 10, 0.103);
            border-radius: 10px;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.5);
        }
        
        .board {
            display: grid;
            grid-template-columns: repeat(10, 8vmin);
            grid-template-rows: repeat(10, 8vmin);
            gap: 3px;
            background-color: rgba(30, 30, 30, 0.783);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.7);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .cell {
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            background-color: rgba(40, 40, 40, 0.8);
            border-radius: 5px;
            position: relative;
            cursor: pointer;
            font-size: 2.5vmin;
            color: #26e415;
            text-shadow: 0 0 5px rgba(23, 59, 19, 0.5);
            transition: all 0.3s ease;
            border: 1px solid rgba(22, 18, 18, 0.441);
        }
        
        .cell:hover {
            background-color: rgba(60, 60, 60, 0.9);
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
        }
        
        .cell.keyword {
            background-color: rgba(255, 215, 0, 0.4);
            color: #FFD700;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.4);
            font-size: 2vmin;
        }
        
        .cell.snake-head {
            background-color: rgba(255, 50, 50, 0.4);
            color: #FF5555;
            box-shadow: 0 0 15px rgba(255, 50, 50, 0.4);
            font-size: 2vmin;
        }
        
        .player1 {
            width: 3.5vmin;
            height: 3.5vmin;
            background-color: #fb0808;
            border-radius: 50%;
            position: absolute;
            top: 5px;
            left: 5px;
            z-index: 2;
            box-shadow: 0 0 10px rgba(244, 5, 5, 0.8);
            animation: pulse 1.5s infinite;
        }
        
        .player2 {
            width: 3.5vmin;
            height: 3.5vmin;
            background-color: #12fa1a;
            border-radius: 50%;
            position: absolute;
            bottom: 5px;
            right: 5px;
            z-index: 2;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.8);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        /* Rest of your CSS remains the same */
        .controls {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 20px;
        }
        
        .player-info {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .player {
            padding: 15px;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            opacity: 0.9;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .player1-info {
            background: linear-gradient(135deg, rgba(240, 80, 31, 0.295), rgba(86, 23, 4, 0.463));
            color: #f5f1f0;
            border-left: 4px solid #fa4109;
        }
        
        .player2-info {
            background: linear-gradient(135deg, rgba(102, 247, 107, 0.2), rgba(11, 69, 2, 0.404));
            color: #f5f1f0;
            border-left: 4px solid #4CAF50;
        }
        
        .current-player {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            transform: scale(1.03);
            opacity: 1;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* 3D Dice Styles */
        .dice-container {
            perspective: 800px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(30, 30, 30, 0.7);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .dice-3d {
            width: 80px;
            height: 80px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 1.5s ease-out;
            transform: rotateX(0deg) rotateY(0deg);
            margin: 0 auto;
        }

        .face {
            position: absolute;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.2),
                       0 0 20px rgba(0,0,0,0.4);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .dot {
            width: 14px;
            height: 14px;
            background: #222;
            border-radius: 50%;
            position: absolute;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.5);
        }

        /* Proper dot positions for standard dice */
        .front .dot:nth-child(1) { top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .back .dot:nth-child(1) { top: 25%; left: 25%; }
        .back .dot:nth-child(2) { top: 25%; right: 25%; }
        .back .dot:nth-child(3) { top: 50%; left: 25%; transform: translateY(-50%); }
        .back .dot:nth-child(4) { top: 50%; right: 25%; transform: translateY(-50%); }
        .back .dot:nth-child(5) { bottom: 25%; left: 25%; }
        .back .dot:nth-child(6) { bottom: 25%; right: 25%; }
        .right .dot:nth-child(1) { top: 25%; left: 25%; }
        .right .dot:nth-child(2) { top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .right .dot:nth-child(3) { bottom: 25%; right: 25%; }
        .left .dot:nth-child(1) { top: 25%; left: 25%; }
        .left .dot:nth-child(2) { top: 25%; right: 25%; }
        .left .dot:nth-child(3) { bottom: 25%; left: 25%; }
        .left .dot:nth-child(4) { bottom: 25%; right: 25%; }
        .top .dot:nth-child(1) { top: 25%; left: 25%; }
        .top .dot:nth-child(2) { top: 25%; right: 25%; }
        .top .dot:nth-child(3) { top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .top .dot:nth-child(4) { bottom: 25%; left: 25%; }
        .top .dot:nth-child(5) { bottom: 25%; right: 25%; }
        .bottom .dot:nth-child(1) { top: 25%; left: 50%; transform: translateX(-50%); }
        .bottom .dot:nth-child(2) { bottom: 25%; left: 50%; transform: translateX(-50%); }

        /* 3D positioning */
        .front  { transform: translateZ(40px); }
        .back   { transform: rotateY(180deg) translateZ(40px); }
        .right  { transform: rotateY(90deg) translateZ(40px); }
        .left   { transform: rotateY(-90deg) translateZ(40px); }
        .top    { transform: rotateX(90deg) translateZ(40px); }
        .bottom { transform: rotateX(-90deg) translateZ(40px); }
        
        button {
            padding: 15px 30px;
            font-size: 1.1rem;
            background: linear-gradient(135deg, #138808, #0e6e0a);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            margin: 10px 0;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            font-weight: 600;
            width: 200px;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.9;
            position: relative;
            overflow: hidden;
        }
        
        button:hover {
            background: linear-gradient(135deg, #0e6e0a, #138808);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            opacity: 1;
        }
        
        button:disabled {
            background: linear-gradient(135deg, #555, #333);
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        
        button:active {
            transform: translateY(1px);
        }
        
        button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: translateX(-100%);
            transition: 0.5s;
        }
        
        button:hover::after {
            transform: translateX(100%);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 100;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: linear-gradient(145deg, #1a1a1a, #222);
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: modalFadeIn 0.5s ease-out;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal h3 {
            color: #FF9933;
            margin-top: 0;
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(255, 153, 51, 0.5);
        }
        
        .options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 25px 0;
        }
        
        .option {
            padding: 15px;
            background: rgba(40, 40, 40, 0.8);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
        }
        
        .option:hover {
            background: rgba(60, 60, 60, 0.9);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .snake-img {
            position: absolute;
            z-index: 1;
            pointer-events: none;
            transition: all 0.5s ease;
            opacity: 0;
            filter: drop-shadow(0 0 10px rgba(255, 50, 50, 0.7));
        }
        
        .ladder-img {
            position: absolute;
            z-index: 1;
            pointer-events: none;
            transition: all 0.5s ease;
            opacity: 0;
            filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.7));
        }
        
        .snake-active {
            opacity: 1;
        }
        
        .ladder-active {
            opacity: 1;
        }
        
        .setup-screen {
            text-align: center;
            max-width: 600px;
            width: 90%;
            margin: 0 auto;
            padding: 40px;
            background: rgba(20, 20, 20, 0.8);
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 1s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .setup-options {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 40px 0;
            flex-wrap: wrap;
        }
        
        .setup-option {
            padding: 25px 40px;
            background: linear-gradient(135deg, rgba(255, 153, 51, 0.2), rgba(19, 136, 8, 0.2));
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            min-width: 200px;
            color: #e0e0e0;
            border: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 1.2rem;
            position: relative;
            overflow: hidden;
        }
        
        .setup-option:hover {
            background: linear-gradient(135deg, rgba(255, 153, 51, 0.3), rgba(19, 136, 8, 0.3));
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            color: #fff;
        }
        
        .setup-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: 0.5s;
        }
        
        .setup-option:hover::before {
            left: 100%;
        }
        
        .highlight {
            animation: highlight 1s ease-in-out;
        }
        
        @keyframes highlight {
            0% { transform: scale(1); box-shadow: 0 0 10px rgba(255,255,255,0.1); }
            50% { transform: scale(1.05); box-shadow: 0 0 20px rgba(255,255,255,0.3); }
            100% { transform: scale(1); box-shadow: 0 0 10px rgba(255,255,255,0.1); }
        }
        
        /* Landscape orientation */
        @media screen and (orientation: portrait) {
            .orientation-warning {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.95);
                color: white;
                z-index: 1000;
                display: flex;
                justify-content: center;
                align-items: center;
                font-size: 1.5rem;
                text-align: center;
                padding: 20px;
                backdrop-filter: blur(5px);
            }
        }
        
        @media screen and (orientation: landscape) {
            .orientation-warning {
                display: none;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            h1 {
                font-size: 2.5rem;
            }
            
            .game-area {
                flex-direction: column;
            }
            
            .board {
                grid-template-columns: repeat(10, 6vmin);
                grid-template-rows: repeat(10, 6vmin);
            }
            
            .controls {
                margin-top: 20px;
                padding: 0;
            }
            
            .setup-option {
                padding: 20px 30px;
                min-width: 150px;
            }
            
            .back-button {
                padding: 8px 15px 8px 30px;
                font-size: 0.9rem;
            }
        }

        /* Coin reward styles */
        .coin-reward {
            text-align: center;
            color: #ffd700;
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 10px;
            display: none;
            animation: coinPulse 1s ease-in-out infinite;
        }

        @keyframes coinPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="orientation-warning">
        Please rotate your device to landscape mode for the best gaming experience!
    </div>
    
    <div class="setup-screen" id="setup-screen">
        <h1>Snakes and Ladders</h1>
       
        <p style="color: #aaa; font-size: 1.2rem;">Select game mode:</p>
        <div class="setup-options">
            <div class="setup-option" id="two-player-btn">2 Players</div>
            <div class="setup-option" id="computer-btn">Play with Computer</div>
        </div>
    </div>
    
    <div class="container" id="game-screen" style="display: none;">
        <!-- Back Button Added Here -->
        <div class="back-button-container">
            <a href="ps.php" class="back-button">
                <span class="arrow">←</span>
                <span class="text">Back</span>
            </a>
        </div>
        
        <h1>Snakes and Ladders</h1>
        <h2>Indian Constitution Preamble Edition</h2>
        
        <div class="game-area">
            <div class="board-container">
                <div class="board" id="board"></div>
            </div>
            
            <div class="controls">
                <div class="player-info">
                    <div class="player player1-info" id="player1-info">
                        Player 1 (Orange) - Position: 1
                    </div>
                    <div class="player player2-info" id="player2-info">
                        Player 2 (Green) - Position: 1
                    </div>
                </div>
                
                <div class="dice-container">
                    <div id="dice-3d" class="dice-3d">
                        <div class="face front"><span class="dot"></span></div> <!-- 1 -->
                        <div class="face back"><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span></div> <!-- 6 -->
                        <div class="face right"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div> <!-- 3 -->
                        <div class="face left"><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span></div> <!-- 4 -->
                        <div class="face top"><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span></div> <!-- 5 -->
                        <div class="face bottom"><span class="dot"></span><span class="dot"></span></div> <!-- 2 -->
                    </div>
                    <button id="roll-btn">Roll Dice</button>
                </div>
                
                <button id="reset-btn">Reset Game</button>
                <div class="coin-reward" id="coinReward">+50 Coins Awarded!</div>
            </div>
        </div>
        
        <div class="modal" id="question-modal">
            <div class="modal-content">
                <h3 id="question-text">Question</h3>
                <div class="options" id="options-container"></div>
            </div>
        </div>
        
        <div class="modal" id="result-modal">
            <div class="modal-content">
                <h3 id="result-text">Result</h3>
                <button id="continue-btn">Continue</button>
            </div>
        </div>
    </div>
    
    <script>
        // Game data from PHP
        const keywords = <?php echo $keywordsJson; ?>;
        const specialPositions = <?php echo $specialPositionsJson; ?>;
        
        // Game state
        const gameState = {
            currentPlayer: 1,
            playerPositions: [1, 1],
            gameOver: false,
            diceValue: 0,
            vsComputer: false,
            computerDelay: 1000,
            activeSnakes: {},
            activeLadders: {},
            startTime: Date.now()
        };
        
        // Initialize the board
        function initializeBoard() {
            const board = document.getElementById('board');
            board.innerHTML = '';
            
            // Clear existing snakes and ladders
            document.querySelectorAll('.snake-img, .ladder-img').forEach(el => el.remove());
            
            // Create cells in reverse order (from 100 to 1)
            for (let row = 9; row >= 0; row--) {
                for (let col = 0; col < 10; col++) {
                    let cellNum;
                    if (row % 2 === 1) {
                        cellNum = (row * 10) + (9 - col) + 1;
                    } else {
                        cellNum = (row * 10) + col + 1;
                    }
                    
                    const cell = document.createElement('div');
                    cell.className = 'cell';
                    cell.textContent = cellNum;
                    cell.id = `cell-${cellNum}`;
                    
                    if (specialPositions[cellNum]) {
                        if (specialPositions[cellNum].type === "ladder") {
                            cell.classList.add('keyword');
                        } else {
                            cell.classList.add('snake-head');
                        }
                    }
                    
                    board.appendChild(cell);
                }
            }
            
            // Create snake and ladder images (hidden by default)
            createSnakeAndLadderImages();
            updatePlayerPositions();
        }
        
        // Create snake and ladder images
        function createSnakeAndLadderImages() {
            const board = document.getElementById('board');
            const boardRect = board.getBoundingClientRect();
            
            for (const [position, special] of Object.entries(specialPositions)) {
                const startCell = document.getElementById(`cell-${position}`);
                const endCell = document.getElementById(`cell-${special.target}`);
                
                if (startCell && endCell) {
                    const startRect = startCell.getBoundingClientRect();
                    const endRect = endCell.getBoundingClientRect();
                    
                    const startX = startRect.left + startRect.width / 2 - boardRect.left;
                    const startY = startRect.top + startRect.height / 2 - boardRect.top;
                    const endX = endRect.left + endRect.width / 2 - boardRect.left;
                    const endY = endRect.top + endRect.height / 2 - boardRect.top;
                    
                    const length = Math.sqrt(Math.pow(endX - startX, 2) + Math.pow(endY - startY, 2));
                    const angle = Math.atan2(endY - startY, endX - startX) * 180 / Math.PI;
                    
                    const img = document.createElement('img');
                    img.src = special.img;
                    img.className = special.type === 'ladder' ? 'ladder-img' : 'snake-img';
                    img.id = `${special.type}-${position}`;
                    img.style.width = `${length}px`;
                    img.style.height = special.type === 'ladder' ? '60px' : '40px';
                    img.style.left = `${startX}px`;
                    img.style.top = `${startY}px`;
                    img.style.transformOrigin = '0 0';
                    img.style.transform = `rotate(${angle}deg)`;
                    
                    board.appendChild(img);
                }
            }
        }
        
        // Show snake or ladder when player lands on it
        function showSpecial(position) {
            const special = specialPositions[position];
            if (!special) return;
            
            const imgId = `${special.type}-${position}`;
            const img = document.getElementById(imgId);
            if (img) {
                img.classList.add(`${special.type}-active`);
                
                // Hide after animation
                setTimeout(() => {
                    img.classList.remove(`${special.type}-active`);
                }, 2000);
            }
        }
        
        // Update player positions
        function updatePlayerPositions() {
            document.querySelectorAll('.player1, .player2').forEach(el => el.remove());
            
            const player1Cell = document.getElementById(`cell-${gameState.playerPositions[0]}`);
            if (player1Cell) {
                const player1Marker = document.createElement('div');
                player1Marker.className = 'player1';
                player1Cell.appendChild(player1Marker);
                player1Cell.classList.add('highlight');
                setTimeout(() => player1Cell.classList.remove('highlight'), 1000);
            }
            
            const player2Cell = document.getElementById(`cell-${gameState.playerPositions[1]}`);
            if (player2Cell) {
                const player2Marker = document.createElement('div');
                player2Marker.className = 'player2';
                player2Cell.appendChild(player2Marker);
                player2Cell.classList.add('highlight');
                setTimeout(() => player2Cell.classList.remove('highlight'), 1000);
            }
            
            document.getElementById('player1-info').textContent = 
                `Player 1 (Orange) - Position: ${gameState.playerPositions[0]}`;
            document.getElementById('player2-info').textContent = 
                gameState.vsComputer ? 
                `Computer (Green) - Position: ${gameState.playerPositions[1]}` :
                `Player 2 (Green) - Position: ${gameState.playerPositions[1]}`;
            
            document.getElementById('player1-info').classList.toggle('current-player', gameState.currentPlayer === 1);
            document.getElementById('player2-info').classList.toggle('current-player', gameState.currentPlayer === 2);
        }
        
        // Roll the 3D dice
        function rollDice() {
            if (gameState.gameOver) return;
            
            document.getElementById('roll-btn').disabled = true;
            const dice = document.getElementById('dice-3d');
            
            // Random rotations for the spinning effect
            const xRot = Math.floor(Math.random() * 4) * 90 + 360 * Math.floor(Math.random() * 3);
            const yRot = Math.floor(Math.random() * 4) * 90 + 360 * Math.floor(Math.random() * 3);
            
            dice.style.transform = `rotateX(${xRot}deg) rotateY(${yRot}deg)`;
            
            // Determine the final dice value (1-6)
            gameState.diceValue = Math.floor(Math.random() * 6) + 1;
            
            // After spinning, show the correct face
            setTimeout(() => {
                const rotations = [
                    'rotateX(0deg) rotateY(0deg)',      // 1 (front)
                    'rotateX(90deg) rotateY(0deg)',     // 2 (bottom)
                    'rotateX(0deg) rotateY(-90deg)',    // 3 (right)
                    'rotateX(0deg) rotateY(90deg)',     // 4 (left)
                    'rotateX(-90deg) rotateY(0deg)',    // 5 (top)
                    'rotateX(0deg) rotateY(180deg)'     // 6 (back)
                ];
                dice.style.transform = rotations[gameState.diceValue - 1];
                
                // Move the player after dice settles
                setTimeout(movePlayer, 500);
            }, 1500);
        }
        
        // Move player
        function movePlayer() {
            const playerIndex = gameState.currentPlayer - 1;
            let newPosition = gameState.playerPositions[playerIndex] + gameState.diceValue;
            
            if (newPosition >= 100) {
                gameState.gameOver = true;
                const duration = Math.floor((Date.now() - gameState.startTime) / 1000);
                const score = 500 - duration; // Base score minus time taken
                
                // Save game session and award coins
                saveGameSession(score, duration, gameState.currentPlayer);
                return;
            }
            
            gameState.playerPositions[playerIndex] = newPosition;
            updatePlayerPositions();
            
            if (specialPositions[newPosition]) {
                showSpecial(newPosition);
                const special = specialPositions[newPosition];
                
                if (gameState.vsComputer && gameState.currentPlayer === 2) {
                    setTimeout(() => {
                        const isCorrect = Math.random() > 0.3;
                        handleAnswer(isCorrect, special.type, special.target);
                    }, gameState.computerDelay);
                } else {
                    setTimeout(() => {
                        showQuestion(special.keyword, special.type, special.target);
                    }, 1000);
                }
            } else {
                switchPlayer();
            }
        }
        
        // Save game session to database
        function saveGameSession(score, duration, winningPlayer) {
            const gameData = {
                save_game: true,
                game_type: 'ladder',
                subject: 'political_science',
                level: 1,
                score: score,
                duration: duration
            };
            
            // Send data to PHP script using AJAX
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(gameData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show coin reward
                    const coinReward = document.getElementById('coinReward');
                    coinReward.style.display = 'block';
                    coinReward.textContent = `+50 Coins Awarded! Total: ${data.new_coins || '50'} coins`;
                    
                    console.log('Game session saved successfully:', data);
                    
                    // Show win message with coin reward
                    showResult(`Player ${winningPlayer} wins! Congratulations! You earned 50 coins!`);
                } else {
                    console.error('Failed to save game session:', data.message);
                    // Still show win message
                    showResult(`Player ${winningPlayer} wins! Congratulations!`);
                }
            })
            .catch(error => {
                console.error('Error saving game session:', error);
                // Still show win message
                showResult(`Player ${winningPlayer} wins! Congratulations!`);
            });
        }
        
        // Show question modal
        function showQuestion(keyword, type, targetPosition) {
            const modal = document.getElementById('question-modal');
            const questionText = document.getElementById('question-text');
            const optionsContainer = document.getElementById('options-container');
            
            questionText.textContent = `What is the meaning of "${keyword.name}" in the Preamble?`;
            optionsContainer.innerHTML = '';
            
            const otherKeywords = keywords.filter(k => k.name !== keyword.name);
            const randomKeywords = [];
            while (randomKeywords.length < 3) {
                const randomIndex = Math.floor(Math.random() * otherKeywords.length);
                if (!randomKeywords.includes(otherKeywords[randomIndex])) {
                    randomKeywords.push(otherKeywords[randomIndex]);
                }
            }
            
            const options = [
                { text: keyword.meaning, correct: true },
                { text: keyword.opposite, correct: false },
                ...randomKeywords.map(k => ({ text: k.meaning, correct: false }))
            ];
            
            options.sort(() => Math.random() - 0.5);
            
            options.forEach((option) => {
                const optionElement = document.createElement('div');
                optionElement.className = 'option';
                optionElement.textContent = option.text;
                optionElement.onclick = () => {
                    modal.style.display = 'none';
                    handleAnswer(option.correct, type, targetPosition);
                };
                optionsContainer.appendChild(optionElement);
            });
            
            modal.style.display = 'flex';
        }
        
        // Handle answer
        function handleAnswer(isCorrect, type, targetPosition) {
            const playerIndex = gameState.currentPlayer - 1;
            let message = '';
            
            if (type === 'ladder') {
                if (isCorrect) {
                    gameState.playerPositions[playerIndex] = targetPosition;
                    message = `Correct! You climbed the ladder to position ${targetPosition}!`;
                } else {
                    message = "Incorrect! You didn't climb the ladder.";
                }
            } else if (type === 'snake') {
                if (isCorrect) {
                    message = "Correct! You avoided the snake.";
                } else {
                    gameState.playerPositions[playerIndex] = targetPosition;
                    message = `Incorrect! You slid down the snake to position ${targetPosition}.`;
                }
            }
            
            showResult(message);
        }
        
        // Show result modal
        function showResult(message) {
            const modal = document.getElementById('result-modal');
            const resultText = document.getElementById('result-text');
            
            resultText.textContent = message;
            modal.style.display = 'flex';
        }
        
        // Continue game
        function continueGame() {
            document.getElementById('result-modal').style.display = 'none';
            
            if (!gameState.gameOver) {
                updatePlayerPositions();
                switchPlayer();
            }
        }
        
        // Switch player
        function switchPlayer() {
            gameState.currentPlayer = gameState.currentPlayer === 1 ? 2 : 1;
            document.getElementById('roll-btn').disabled = false;
            updatePlayerPositions();
            
            if (gameState.vsComputer && gameState.currentPlayer === 2 && !gameState.gameOver) {
                setTimeout(() => {
                    rollDice();
                }, gameState.computerDelay);
            }
        }
        
        // Reset game
        function resetGame() {
            gameState.currentPlayer = 1;
            gameState.playerPositions = [1, 1];
            gameState.gameOver = false;
            gameState.diceValue = 0;
            gameState.startTime = Date.now();
            
            const dice = document.getElementById('dice-3d');
            dice.style.transform = 'rotateX(0deg) rotateY(0deg)';
            document.getElementById('roll-btn').disabled = false;
            document.getElementById('coinReward').style.display = 'none';
            
            updatePlayerPositions();
        }
        
        // Start game
        function startGame(vsComputer) {
            gameState.vsComputer = vsComputer;
            gameState.startTime = Date.now();
            document.getElementById('setup-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'block';
            initializeBoard();
        }
        
        // Event listeners
        document.getElementById('roll-btn').addEventListener('click', rollDice);
        document.getElementById('reset-btn').addEventListener('click', resetGame);
        document.getElementById('continue-btn').addEventListener('click', continueGame);
        document.getElementById('two-player-btn').addEventListener('click', () => startGame(false));
        document.getElementById('computer-btn').addEventListener('click', () => startGame(true));
    </script>
</body>
</html>