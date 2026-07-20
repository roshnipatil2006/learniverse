<?php
session_start();

// Include your database configuration
require_once 'config/database.php';

// Your avatar URLs
$avatarUrls = [
    '1' => 'https://cdn-icons-png.flaticon.com/512/4333/4333609.png',
    '2' => 'https://cdn-icons-png.flaticon.com/512/4333/4333607.png',
    '3' => 'https://cdn-icons-png.flaticon.com/512/4333/4333617.png',
    '4' => 'https://cdn-icons-png.flaticon.com/512/4825/4825112.png',
    '5' => 'https://cdn-icons-png.flaticon.com/512/4825/4825082.png',
    '6' => 'https://cdn-icons-png.flaticon.com/512/4825/4825087.png',
    '7' => 'https://cdn-icons-png.flaticon.com/512/4825/4825044.png',
    '8' => 'https://cdn-icons-png.flaticon.com/512/4825/4825027.png',
    '9' => 'https://cdn-icons-png.flaticon.com/512/8326/8326722.png',
];

// Check if database connection is established properly
if (!isset($conn)) {
    // Replace these with your actual database credentials
    $servername = "localhost";
    $username = "root";
    $password = "roshni@2006";
    $dbname = "learniverse";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Create required tables if they don't exist
createFriendsTables($conn);

function createFriendsTables($conn) {
    // Friends table
    $sql = "CREATE TABLE IF NOT EXISTS friends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        friend_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_friendship (user_id, friend_id)
    )";
    $conn->query($sql);
    
    // Friend requests table
    $sql = "CREATE TABLE IF NOT EXISTS friend_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    
    // Messages table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_read BOOLEAN DEFAULT FALSE
    )";
    $conn->query($sql);
    
    // Study groups table
    $sql = "CREATE TABLE IF NOT EXISTS study_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        next_session DATETIME,
        created_by INT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    
    // Group members table
    $sql = "CREATE TABLE IF NOT EXISTS group_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        user_id INT NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_membership (group_id, user_id)
    )";
    $conn->query($sql);
    
    // Activities table
    $sql = "CREATE TABLE IF NOT EXISTS activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
}

// Insert sample data if tables are empty
function insertSampleData($conn, $avatarUrls) {
    // Check if we already have sample data
    $result = $conn->query("SELECT COUNT(*) as count FROM study_groups");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Insert sample study groups
        $conn->query("INSERT INTO study_groups (name, description, next_session, created_by) VALUES 
            ('Political Science Study Group', 'Discussing political theories and systems', NOW() + INTERVAL 2 HOUR, 1),
            ('History Buffs', 'Exploring world history and civilizations', NOW() + INTERVAL 1 DAY, 1),
            ('Mathematics Club', 'Solving complex problems together', NOW() + INTERVAL 3 DAY, 1)
        ");
        
        // Insert sample activities
        $conn->query("INSERT INTO activities (user_id, activity) VALUES 
            (2, 'completed \"World Geography\" quiz'),
            (3, 'earned the \"History Expert\" badge'),
            (4, 'started a new study session'),
            (5, 'reached level 5 in Mathematics'),
            (6, 'joined the Science Study Group')
        ");
        
        // Insert sample friend requests
        $conn->query("INSERT INTO friend_requests (sender_id, receiver_id) VALUES 
            (2, 1),
            (3, 1),
            (4, 1)
        ");
        
        // Insert sample friends
        $conn->query("INSERT INTO friends (user_id, friend_id) VALUES 
            (1, 2), (2, 1),
            (1, 3), (3, 1)
        ");
    }
}

// Call the function to insert sample data
insertSampleData($conn, $avatarUrls);

// Get current user ID from session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default user for demo
}
$current_user_id = $_SESSION['user_id'];

// Handle friend requests and other actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'accept_request') {
        $request_id = $_POST['request_id'];
        
        // Get the friend request details
        $sql = "SELECT * FROM friend_requests WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();
            
            // Add to friends table (both directions for mutual friendship)
            $sql = "INSERT IGNORE INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $current_user_id, $request['sender_id'], $request['sender_id'], $current_user_id);
            $stmt->execute();
            
            // Delete the friend request
            $sql = "DELETE FROM friend_requests WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            
            $_SESSION['message'] = "Friend request accepted!";
        }
    } elseif ($_POST['action'] == 'decline_request') {
        $request_id = $_POST['request_id'];
        
        // Delete the friend request
        $sql = "DELETE FROM friend_requests WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        
        $_SESSION['message'] = "Friend request declined.";
    } elseif ($_POST['action'] == 'send_message') {
        $friend_id = $_POST['friend_id'];
        $message = $_POST['message'];
        
        // Insert message into database
        $sql = "INSERT INTO messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $current_user_id, $friend_id, $message);
        $stmt->execute();
    } elseif ($_POST['action'] == 'add_friend') {
        $friend_identifier = $_POST['friend_identifier'];
        
        // Prevent adding yourself
        if ($friend_identifier == $current_user_id) {
            $_SESSION['message'] = "You cannot add yourself as a friend!";
        } else {
            // Find user by nickname or email
            $sql = "SELECT id, nickname FROM users WHERE (nickname = ? OR email = ?) AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $friend_identifier, $friend_identifier, $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $friend = $result->fetch_assoc();
                $friend_id = $friend['id'];
                
                // Check if already friends
                $sql = "SELECT id FROM friends WHERE user_id = ? AND friend_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $current_user_id, $friend_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $_SESSION['message'] = "You are already friends with " . $friend['nickname'];
                } else {
                    // Check if friend request already exists
                    $sql = "SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $current_user_id, $friend_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows == 0) {
                        // Send friend request
                        $sql = "INSERT INTO friend_requests (sender_id, receiver_id, sent_at) VALUES (?, ?, NOW())";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ii", $current_user_id, $friend_id);
                        $stmt->execute();
                        
                        $_SESSION['message'] = "Friend request sent to " . $friend['nickname'];
                    } else {
                        $_SESSION['message'] = "Friend request already sent to " . $friend['nickname'];
                    }
                }
            } else {
                $_SESSION['message'] = "User not found";
            }
        }
    } elseif ($_POST['action'] == 'join_group') {
        $group_id = $_POST['group_id'];
        
        // Add user to study group
        $sql = "INSERT IGNORE INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $group_id, $current_user_id);
        $stmt->execute();
        
        $_SESSION['message'] = "You've joined the study group!";
    }
}

// Get current user info
$current_user = ['id' => 1, 'nickname' => 'Demo User', 'avatar_url' => $avatarUrls[1]]; // Default with your avatar
try {
    $sql = "SELECT id, nickname, avatar_url, avatar_id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $current_user = $result->fetch_assoc();
        // If user has no avatar, assign one from your collection
        if (empty($current_user['avatar_url'])) {
            $avatar_index = ($current_user['id'] % 9) + 1;
            $current_user['avatar_url'] = $avatarUrls[$avatar_index];
        }
    }
} catch (Exception $e) {
    // Use default user if there's an error
}

// Get friends list - EXCLUDING CURRENT USER
$friends_result = null;
try {
    $sql = "SELECT u.id, u.nickname, u.avatar_url, u.avatar_id, u.current_level, u.performance_score 
            FROM users u 
            JOIN friends f ON u.id = f.friend_id 
            WHERE f.user_id = ? AND u.id != ? AND u.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $current_user_id, $current_user_id);
    $stmt->execute();
    $friends_result = $stmt->get_result();
} catch (Exception $e) {
    // Friends table might be empty
    $friends_result = false;
}

// Get friend requests with avatar fallback - EXCLUDING CURRENT USER
$requests_result = null;
try {
    $sql = "SELECT fr.id, u.id as sender_id, u.nickname, u.avatar_url, u.avatar_id 
            FROM friend_requests fr 
            JOIN users u ON fr.sender_id = u.id 
            WHERE fr.receiver_id = ? AND u.id != ? AND u.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $current_user_id, $current_user_id);
    $stmt->execute();
    $requests_result = $stmt->get_result();
} catch (Exception $e) {
    $requests_result = false;
}

// Get study groups
$groups_result = null;
try {
    $sql = "SELECT sg.id, sg.name, sg.description, sg.next_session 
            FROM study_groups sg 
            LEFT JOIN group_members gm ON sg.id = gm.group_id AND gm.user_id = ?
            WHERE gm.user_id IS NULL AND sg.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $groups_result = $stmt->get_result();
} catch (Exception $e) {
    $groups_result = false;
}

// Get recent activity with avatar fallback - EXCLUDING CURRENT USER'S ACTIVITY IN FRIENDS' ACTIVITIES
$activities_result = null;
try {
    $sql = "SELECT u.id, u.nickname, u.avatar_url, u.avatar_id, a.activity, a.created_at 
            FROM activities a 
            JOIN users u ON a.user_id = u.id 
            WHERE (a.user_id IN (
                SELECT friend_id FROM friends WHERE user_id = ?
            ) OR a.user_id = ?) AND u.is_active = 1 AND u.id != ?
            ORDER BY a.created_at DESC 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
    $stmt->execute();
    $activities_result = $stmt->get_result();
} catch (Exception $e) {
    $activities_result = false;
}

// Get potential friends for demo (users who are not friends yet) - EXCLUDING CURRENT USER
$potential_friends = null;
try {
    $sql = "SELECT u.id, u.nickname, u.avatar_url, u.avatar_id, u.current_level 
            FROM users u 
            WHERE u.id != ? 
            AND u.id NOT IN (SELECT friend_id FROM friends WHERE user_id = ?)
            AND u.is_active = 1
            AND u.id != ?
            LIMIT 6";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
    $stmt->execute();
    $potential_friends = $stmt->get_result();
} catch (Exception $e) {
    $potential_friends = false;
}

// Count online friends
$online_count = 0;
if ($friends_result && $friends_result->num_rows > 0) {
    $online_count = $friends_result->num_rows;
    $friends_result->data_seek(0); // Reset pointer
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learniverse - Friends</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
            background: #000;
            color: white;
        }
        
        /* 3D Animated Background */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        /* Navigation */
        .nav {
            display: flex;
            justify-content: space-between;
            padding: 1.5rem 2rem;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .nav-brand {
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: white;
        }
        
        .nav-link.active {
            color: #fc00ff;
            font-weight: 600;
        }
        
        /* Main Content */
        .container {
            display: grid;
            grid-template-columns: 300px 1fr 350px;
            gap: 1.5rem;
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Friends Sidebar */
        .friends-sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem;
            height: calc(100vh - 120px);
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #00dbde;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .friend-search {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: white;
        }
        
        .friend-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .friend-item {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .friend-item:hover {
            background: rgba(0, 219, 222, 0.1);
        }
        
        .friend-item.active {
            background: rgba(252, 0, 255, 0.1);
            border-left: 3px solid #fc00ff;
        }
        
        .friend-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            border: 2px solid #00dbde;
        }
        
        .friend-name {
            font-weight: 500;
        }
        
        .friend-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: auto;
        }
        
        .online {
            background: #00ff7f;
            box-shadow: 0 0 10px #00ff7f;
        }
        
        .offline {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Chat Main Area */
        .chat-main {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 120px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }
        
        .chat-user {
            display: flex;
            align-items: center;
        }
        
        .chat-user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            border: 2px solid #fc00ff;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
        }
        
        .empty-chat i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.2);
        }
        
        .message {
            max-width: 70%;
            padding: 1rem;
            border-radius: 15px;
            position: relative;
        }
        
        .received {
            align-self: flex-start;
            background: rgba(0, 219, 222, 0.1);
            border-bottom-left-radius: 5px;
        }
        
        .sent {
            align-self: flex-end;
            background: rgba(252, 0, 255, 0.1);
            border-bottom-right-radius: 5px;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.5rem;
            text-align: right;
        }
        
        .chat-input {
            display: flex;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .chat-input input {
            flex: 1;
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: white;
            margin-right: 1rem;
        }
        
        .send-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(252, 0, 255, 0.5);
        }
        
        /* Activity Sidebar */
        .activity-sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem;
            height: calc(100vh - 120px);
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .activity-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-text {
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .activity-time {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .add-friend-btn {
            display: block;
            width: 100%;
            padding: 0.8rem;
            margin-top: 1rem;
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-friend-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(252, 0, 255, 0.3);
        }
        
        /* Friend Requests */
        .friend-request {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        
        .request-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }
        
        .accept-btn, .decline-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .accept-btn {
            background: rgba(0, 255, 127, 0.1);
            color: #00ff7f;
            border: 1px solid #00ff7f;
        }
        
        .decline-btn {
            background: rgba(255, 0, 0, 0.1);
            color: #ff0000;
            border: 1px solid #ff0000;
        }
        
        .accept-btn:hover, .decline-btn:hover {
            transform: scale(1.1);
        }
        
        /* Study Groups */
        .study-group {
            background: rgba(0, 219, 222, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .study-group:hover {
            background: rgba(0, 219, 222, 0.2);
        }
        
        .group-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #00dbde;
        }
        
        .group-members {
            display: flex;
            margin-bottom: 0.5rem;
        }
        
        .group-member-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fc00ff;
            margin-right: -10px;
        }
        
        .group-member-avatar:first-child {
            margin-right: 0;
        }
        
        .join-group-btn {
            display: block;
            width: 100%;
            padding: 0.5rem;
            background: transparent;
            border: 1px solid #fc00ff;
            color: #fc00ff;
            border-radius: 50px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .join-group-btn:hover {
            background: rgba(252, 0, 255, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .container {
                grid-template-columns: 250px 1fr;
            }
            .activity-sidebar {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            .friends-sidebar {
                height: auto;
                margin-bottom: 1rem;
            }
            .chat-main {
                height: 500px;
            }
        }

         /* Back Button */
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
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
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-3px);
        }        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
            background: #000;
            color: white;
        }
        
        /* All your existing CSS styles... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
            background: #000;
            color: white;
        }
        
        /* 3D Animated Background */
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        /* Navigation */
        .nav {
            display: flex;
            justify-content: space-between;
            padding: 1.5rem 2rem;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .nav-brand {
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: white;
        }
        
        .nav-link.active {
            color: #fc00ff;
            font-weight: 600;
        }
        
        /* Main Content */
        .container {
            display: grid;
            grid-template-columns: 300px 1fr 350px;
            gap: 1.5rem;
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Friends Sidebar */
        .friends-sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem;
            height: calc(100vh - 120px);
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #00dbde;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .friend-search {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: white;
        }
        
        .friend-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .friend-item {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .friend-item:hover {
            background: rgba(0, 219, 222, 0.1);
        }
        
        .friend-item.active {
            background: rgba(252, 0, 255, 0.1);
            border-left: 3px solid #fc00ff;
        }
        
        .friend-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            border: 2px solid #00dbde;
        }
        
        .friend-name {
            font-weight: 500;
        }
        
        .friend-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: auto;
        }
        
        .online {
            background: #00ff7f;
            box-shadow: 0 0 10px #00ff7f;
        }
        
        .offline {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Chat Main Area */
        .chat-main {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 120px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }
        
        .chat-user {
            display: flex;
            align-items: center;
        }
        
        .chat-user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            border: 2px solid #fc00ff;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
        }
        
        .empty-chat i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.2);
        }
        
        .message {
            max-width: 70%;
            padding: 1rem;
            border-radius: 15px;
            position: relative;
        }
        
        .received {
            align-self: flex-start;
            background: rgba(0, 219, 222, 0.1);
            border-bottom-left-radius: 5px;
        }
        
        .sent {
            align-self: flex-end;
            background: rgba(252, 0, 255, 0.1);
            border-bottom-right-radius: 5px;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.5rem;
            text-align: right;
        }
        
        .chat-input {
            display: flex;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .chat-input input {
            flex: 1;
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: white;
            margin-right: 1rem;
        }
        
        .send-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(252, 0, 255, 0.5);
        }
        
        /* Activity Sidebar */
        .activity-sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem;
            height: calc(100vh - 120px);
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .activity-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-text {
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .activity-time {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .add-friend-btn {
            display: block;
            width: 100%;
            padding: 0.8rem;
            margin-top: 1rem;
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-friend-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(252, 0, 255, 0.3);
        }
        
        /* Friend Requests */
        .friend-request {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        
        .request-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }
        
        .accept-btn, .decline-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .accept-btn {
            background: rgba(0, 255, 127, 0.1);
            color: #00ff7f;
            border: 1px solid #00ff7f;
        }
        
        .decline-btn {
            background: rgba(255, 0, 0, 0.1);
            color: #ff0000;
            border: 1px solid #ff0000;
        }
        
        .accept-btn:hover, .decline-btn:hover {
            transform: scale(1.1);
        }
        
        /* Study Groups */
        .study-group {
            background: rgba(0, 219, 222, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .study-group:hover {
            background: rgba(0, 219, 222, 0.2);
        }
        
        .group-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #00dbde;
        }
        
        .group-members {
            display: flex;
            margin-bottom: 0.5rem;
        }
        
        .group-member-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fc00ff;
            margin-right: -10px;
        }
        
        .group-member-avatar:first-child {
            margin-right: 0;
        }
        
        .join-group-btn {
            display: block;
            width: 100%;
            padding: 0.5rem;
            background: transparent;
            border: 1px solid #fc00ff;
            color: #fc00ff;
            border-radius: 50px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .join-group-btn:hover {
            background: rgba(252, 0, 255, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .container {
                grid-template-columns: 250px 1fr;
            }
            .activity-sidebar {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            .friends-sidebar {
                height: auto;
                margin-bottom: 1rem;
            }
            .chat-main {
                height: 500px;
            }
        }

         /* Back Button */
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
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
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-3px);
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
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
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-3px);
        }

        /* Message notification */
        .message-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 219, 222, 0.9);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Suggested friends section */
        .suggested-friends {
            margin-top: 2rem;
        }

        .suggested-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .suggested-friend {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .suggested-friend:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .suggested-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 0.5rem;
            border: 2px solid #00dbde;
        }

        .suggested-name {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .suggested-level {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.5rem;
        }

        .add-suggested-btn {
            background: transparent;
            border: 1px solid #fc00ff;
            color: #fc00ff;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-suggested-btn:hover {
            background: rgba(252, 0, 255, 0.1);
        }
    </style>
</head>
<body>
    <!-- 3D Animated Background -->
    <div id="canvas-container"></div>
    
    <!-- Message Notification -->
    <?php if (isset($_SESSION['message'])): ?>
    <div class="message-notification" id="message-notification">
        <?php echo $_SESSION['message']; ?>
        <button onclick="document.getElementById('message-notification').remove()" style="margin-left: 10px; background: none; border: none; color: white; cursor: pointer;">×</button>
    </div>
    <?php unset($_SESSION['message']); endif; ?>
    
    <!-- Navigation -->
    <nav class="nav">
        <div class="nav-brand">Learniverse</div>
        <div class="nav-links">
            <a href="home.php" class="nav-link">Home</a>
            <a href="friends.php" class="nav-link active">Friends</a>
        </div>
        <a href="home.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
    </nav>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Friends Sidebar -->
        <div class="friends-sidebar">
            <h2 class="section-title">Friends <span id="online-count"><?php echo $online_count; ?> online</span></h2>
            <input type="text" class="friend-search" placeholder="Search friends...">
            
            <div class="friend-list">
                <?php if ($friends_result && $friends_result->num_rows > 0): ?>
                    <?php while($friend = $friends_result->fetch_assoc()): 
                        // Use your avatar URLs as fallback
                        $friend_avatar = $friend['avatar_url'];
                        if (empty($friend_avatar)) {
                            $avatar_index = ($friend['id'] % 9) + 1;
                            $friend_avatar = $avatarUrls[$avatar_index];
                        }
                    ?>
                    <div class="friend-item" data-friend-id="<?php echo $friend['id']; ?>">
                        <img src="<?php echo $friend_avatar; ?>" class="friend-avatar" alt="<?php echo $friend['nickname']; ?>">
                        <span class="friend-name"><?php echo $friend['nickname']; ?></span>
                        <div style="margin-left: auto; display: flex; flex-direction: column; align-items: flex-end;">
                            <span class="friend-status online"></span>
                            <small style="font-size: 0.7rem; color: rgba(255,255,255,0.6);">Level <?php echo $friend['current_level']; ?></small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.5);">
                    <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>No friends yet. Add some friends to start chatting!</p>
                    <small style="font-size: 0.8rem;">Try adding friends using their nickname or email</small>
                </div>
                <?php endif; ?>
            </div>
            
            <h2 class="section-title" style="margin-top: 2rem;">Friend Requests</h2>
            <?php if ($requests_result && $requests_result->num_rows > 0): ?>
                <?php while($request = $requests_result->fetch_assoc()): 
                    // Use your avatar URLs as fallback
                    $request_avatar = $request['avatar_url'];
                    if (empty($request_avatar)) {
                        $avatar_index = ($request['sender_id'] % 9) + 1;
                        $request_avatar = $avatarUrls[$avatar_index];
                    }
                ?>
                <div class="friend-request">
                    <img src="<?php echo $request_avatar; ?>" class="friend-avatar" alt="<?php echo $request['nickname']; ?>">
                    <span class="friend-name"><?php echo $request['nickname']; ?></span>
                    <div class="request-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="accept_request">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <button type="submit" class="accept-btn" title="Accept">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="decline_request">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <button type="submit" class="decline-btn" title="Decline">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div style="text-align: center; padding: 1rem; color: rgba(255,255,255,0.5);">
                <p>No pending friend requests</p>
            </div>
            <?php endif; ?>

            <!-- Suggested Friends Section -->
            <?php if ($potential_friends && $potential_friends->num_rows > 0): ?>
            <div class="suggested-friends">
                <h2 class="section-title">Suggested Friends</h2>
                <div class="suggested-grid">
                    <?php while($suggested = $potential_friends->fetch_assoc()): 
                        // Use your avatar URLs as fallback
                        $suggested_avatar = $suggested['avatar_url'];
                        if (empty($suggested_avatar)) {
                            $avatar_index = ($suggested['id'] % 9) + 1;
                            $suggested_avatar = $avatarUrls[$avatar_index];
                        }
                    ?>
                    <div class="suggested-friend">
                        <img src="<?php echo $suggested_avatar; ?>" class="suggested-avatar" alt="<?php echo $suggested['nickname']; ?>">
                        <div class="suggested-name"><?php echo $suggested['nickname']; ?></div>
                        <div class="suggested-level">Level <?php echo $suggested['current_level']; ?></div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="add_friend">
                            <input type="hidden" name="friend_identifier" value="<?php echo $suggested['nickname']; ?>">
                            <button type="submit" class="add-suggested-btn">
                                <i class="fas fa-user-plus"></i> Add
                            </button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="add-friend-form">
                <input type="hidden" name="action" value="add_friend">
                <button type="button" class="add-friend-btn" id="add-friend-btn">
                    <i class="fas fa-user-plus"></i> Add Friend
                </button>
            </form>
        </div>
        
        <!-- Chat Main Area -->
        <div class="chat-main">
            <div class="chat-header">
                <div class="chat-user">
                    <img src="<?php echo $current_user['avatar_url']; ?>" class="chat-user-avatar" alt="<?php echo $current_user['nickname']; ?>">
                    <div>
                        <h3>Select a friend to chat</h3>
                        <small style="color: rgba(255,255,255,0.5);">Click on a friend to start chatting</small>
                    </div>
                </div>
            </div>
            
            <div class="chat-messages">
                <div class="empty-chat">
                    <i class="fas fa-comment-dots"></i>
                    <h3>No messages yet</h3>
                    <p>Select a friend from your list to start chatting</p>
                </div>
            </div>
            
            <div class="chat-input">
                <input type="text" placeholder="Type a message..." disabled>
                <button class="send-btn" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
        
        <!-- Activity Sidebar -->
        <div class="activity-sidebar">
            <h2 class="section-title">Study Groups</h2>
            
            <?php if ($groups_result && $groups_result->num_rows > 0): ?>
                <?php while($group = $groups_result->fetch_assoc()): ?>
                <div class="study-group">
                    <h3 class="group-title"><?php echo $group['name']; ?></h3>
                    <p style="font-size: 0.8rem; margin-bottom: 0.5rem; color: rgba(255,255,255,0.7);">
                        <?php echo $group['description']; ?>
                    </p>
                    <p style="font-size: 0.8rem; margin-bottom: 0.5rem; color: rgba(255,255,255,0.7);">
                        Next session: <?php echo date('D, M j g:i A', strtotime($group['next_session'])); ?>
                    </p>
                    <form method="POST">
                        <input type="hidden" name="action" value="join_group">
                        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                        <button type="submit" class="join-group-btn">Join Group</button>
                    </form>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div style="text-align: center; padding: 1rem; color: rgba(255,255,255,0.5);">
                <p>No available study groups</p>
                <small style="font-size: 0.8rem;">Study groups will appear here when available</small>
            </div>
            <?php endif; ?>
            
            <h2 class="section-title" style="margin-top: 2rem;">Recent Activity</h2>
            
            <?php if ($activities_result && $activities_result->num_rows > 0): ?>
                <?php while($activity = $activities_result->fetch_assoc()): 
                    // Use your avatar URLs as fallback
                    $activity_avatar = $activity['avatar_url'];
                    if (empty($activity_avatar)) {
                        $avatar_index = ($activity['id'] % 9) + 1;
                        $activity_avatar = $avatarUrls[$avatar_index];
                    }
                ?>
                <div class="activity-item">
                    <img src="<?php echo $activity_avatar; ?>" class="activity-avatar" alt="<?php echo $activity['nickname']; ?>">
                    <div class="activity-details">
                        <p class="activity-text"><?php echo $activity['nickname'] . ' ' . $activity['activity']; ?></p>
                        <p class="activity-time"><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></p>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div style="text-align: center; padding: 1rem; color: rgba(255,255,255,0.5);">
                <p>No recent activity</p>
                <small style="font-size: 0.8rem;">Activity from you and your friends will appear here</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Three.js for 3D Background -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        // 3D Background with Three.js
        const container = document.getElementById('canvas-container');
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        
        renderer.setSize(window.innerWidth, window.innerHeight);
        container.appendChild(renderer.domElement);
        
        // Create particles
        const particlesGeometry = new THREE.BufferGeometry();
        const particleCount = 2000;
        
        const posArray = new Float32Array(particleCount * 3);
        
        for(let i = 0; i < particleCount * 3; i++) {
            posArray[i] = (Math.random() - 0.5) * 10;
        }
        
        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        
        const particlesMaterial = new THREE.PointsMaterial({
            size: 0.02,
            color: 0x00dbde,
            transparent: true,
            opacity: 0.8,
            blending: THREE.AdditiveBlending
        });
        
        const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
        scene.add(particlesMesh);
        
        // Create lines connecting particles
        const lineGeometry = new THREE.BufferGeometry();
        lineGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        
        const lineMaterial = new THREE.LineBasicMaterial({
            color: 0xfc00ff,
            transparent: true,
            opacity: 0.1
        });
        
        const line = new THREE.Line(lineGeometry, lineMaterial);
        scene.add(line);
        
        camera.position.z = 3;
        
        // Animation loop
        function animate() {
            requestAnimationFrame(animate);
            
            particlesMesh.rotation.x += 0.0005;
            particlesMesh.rotation.y += 0.0005;
            
            line.rotation.x += 0.0005;
            line.rotation.y += 0.0005;
            
            renderer.render(scene, camera);
        }
        
        animate();
        
        // Handle window resize
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
        
        // Chat functionality
        const chatInput = document.querySelector('.chat-input input');
        const sendBtn = document.querySelector('.send-btn');
        const chatMessages = document.querySelector('.chat-messages');
        
        function sendMessage() {
            const message = chatInput.value.trim();
            if (message) {
                const messageElement = document.createElement('div');
                messageElement.className = 'message sent';
                messageElement.innerHTML = `
                    <p>${message}</p>
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                `;
                chatMessages.innerHTML = ''; // Clear empty state
                chatMessages.appendChild(messageElement);
                chatInput.value = '';
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Enable empty state if all messages are deleted
                if (chatMessages.children.length === 0) {
                    showEmptyChat();
                }
            }
        }
        
        function showEmptyChat() {
            chatMessages.innerHTML = `
                <div class="empty-chat">
                    <i class="fas fa-comment-dots"></i>
                    <h3>No messages yet</h3>
                    <p>Select a friend from your list to start chatting</p>
                </div>
            `;
        }
        
        sendBtn.addEventListener('click', sendMessage);
        
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Friend list functionality
        const friendItems = document.querySelectorAll('.friend-item');
        friendItems.forEach(item => {
            item.addEventListener('click', () => {
                friendItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                
                // Update chat header
                const friendName = item.querySelector('.friend-name').textContent;
                const friendAvatar = item.querySelector('.friend-avatar').src;
                
                document.querySelector('.chat-user h3').textContent = friendName;
                document.querySelector('.chat-user-avatar').src = friendAvatar;
                document.querySelector('.chat-user small').textContent = 'Online';
                document.querySelector('.chat-user small').style.color = '#00ff7f';
                
                // Enable chat input
                chatInput.disabled = false;
                sendBtn.disabled = false;
                
                // Show empty chat
                showEmptyChat();
            });
        });
        
        // Add friend button
        document.getElementById('add-friend-btn').addEventListener('click', function() {
            const friendIdentifier = prompt("Enter your friend's nickname or email:");
            if (friendIdentifier) {
                const form = document.getElementById('add-friend-form');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'friend_identifier';
                input.value = friendIdentifier;
                form.appendChild(input);
                form.submit();
            }
        });
        
        // Auto-hide message notification after 5 seconds
        const messageNotification = document.getElementById('message-notification');
        if (messageNotification) {
            setTimeout(() => {
                messageNotification.remove();
            }, 5000);
        }
    </script>
</body>
</html>
<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>