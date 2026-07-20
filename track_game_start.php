<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_type = $_POST['game_type'] ?? '';
    $subject = $_POST['subject'] ?? '';
    
    if (!empty($game_type) && !empty($subject)) {
        // Store game session data
        $_SESSION['current_game'] = [
            'type' => $game_type,
            'subject' => $subject,
            'started_at' => date('Y-m-d H:i:s')
        ];
        
        // You can also log game starts to database here
        // Example: INSERT INTO game_sessions (user_id, game_type, subject, started_at) VALUES (...)
    }
    
    // Return success
    echo json_encode(['status' => 'success']);
    exit();

}
?>