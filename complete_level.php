<?php
// Complete level function
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

// Function to complete level and award coins
function completeLevel($user_id, $game_type, $subject, $level, $score, $duration, $db) {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // 1. Insert game session record
        $stmt = $db->prepare("
            INSERT INTO game_sessions (user_id, game_type, subject, level, score, duration, completed_at) 
            VALUES (:user_id, :game_type, :subject, :level, :score, :duration, NOW())
        ");
        
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':game_type', $game_type);
        $stmt->bindValue(':subject', $subject);
        $stmt->bindValue(':level', $level);
        $stmt->bindValue(':score', $score);
        $stmt->bindValue(':duration', $duration);
        $stmt->execute();
        
        // 2. Award 50 coins for level completion
        $update_coins = $db->prepare("
            UPDATE users 
            SET coins = coins + 50, 
                updated_at = NOW() 
            WHERE id = :user_id
        ");
        $update_coins->bindValue(':user_id', $user_id);
        $update_coins->execute();
        
        // 3. Update user's current level if completed level is higher
        $update_level = $db->prepare("
            UPDATE users 
            SET current_level = GREATEST(current_level, :completed_level + 1),
                updated_at = NOW()
            WHERE id = :user_id AND :completed_level >= current_level
        ");
        $update_level->bindValue(':user_id', $user_id);
        $update_level->bindValue(':completed_level', $level);
        $update_level->execute();
        
        // 4. Update current streak (increment by 1)
        $update_streak = $db->prepare("
            UPDATE users 
            SET current_streak = current_streak + 1,
                updated_at = NOW()
            WHERE id = :user_id
        ");
        $update_streak->bindValue(':user_id', $user_id);
        $update_streak->execute();
        
        // Commit transaction
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Level completed successfully!',
            'coins_awarded' => 50,
            'new_level' => $level + 1
        ];
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        error_log("Level completion error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Failed to complete level. Please try again.'
        ];
    }
}

// Handle POST request for level completion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['game_type', 'subject', 'level', 'score', 'duration'];
    $missing_fields = [];
    
    // Validate required fields
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
    
    // Get POST data
    $game_type = $_POST['game_type'];
    $subject = $_POST['subject'];
    $level = intval($_POST['level']);
    $score = intval($_POST['score']);
    $duration = intval($_POST['duration']);
    $user_id = $current_user['id'];
    
    // Validate game type
    $valid_game_types = ['memory', 'wheel', 'puzzle', 'ladder'];
    if (!in_array($game_type, $valid_game_types)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid game type'
        ]);
        exit();
    }
    
    // Complete the level
    $result = completeLevel($user_id, $game_type, $subject, $level, $score, $duration, $db);
    
    // Update session user data with new coins and level
    if ($result['success']) {
        // Refresh user data from database
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

// If not POST request, show error
echo json_encode([
    'success' => false,
    'message' => 'Invalid request method'
]);
exit();
?>