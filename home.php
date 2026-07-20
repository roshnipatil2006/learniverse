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

// Update user data from database to ensure we have latest
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $current_user['id']);
$stmt->execute();

if ($stmt->rowCount() == 1) {
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['current_user'] = $current_user;
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validate inputs
        $errors = [];
        
        if (empty($nickname)) {
            $errors[] = 'Nickname is required';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Check if email already exists (excluding current user)
        if (empty($errors)) {
            $check_email_query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
            $check_stmt = $db->prepare($check_email_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->bindParam(':user_id', $current_user['id']);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $errors[] = 'Email already exists';
            }
        }
        
        if (empty($errors)) {
            // Update user in database
            $update_query = "UPDATE users SET nickname = :nickname, email = :email, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':nickname', $nickname);
            $update_stmt->bindParam(':email', $email);
            $update_stmt->bindParam(':user_id', $current_user['id']);
            
            if ($update_stmt->execute()) {
                // Update session data
                $_SESSION['current_user']['nickname'] = $nickname;
                $_SESSION['current_user']['email'] = $email;
                $current_user = $_SESSION['current_user'];
                
                $success_message = 'Profile updated successfully!';
            } else {
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
    }
    
    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['avatar']['type'];
        $file_size = $_FILES['avatar']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            // Generate unique filename
            $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $current_user['id'] . '_' . time() . '.' . $file_extension;
            $upload_path = 'uploads/avatars/' . $filename;
            
            // Create uploads directory if it doesn't exist
            if (!is_dir('uploads/avatars')) {
                mkdir('uploads/avatars', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                // Update user avatar in database
                $avatar_query = "UPDATE users SET avatar_url = :avatar_url WHERE id = :user_id";
                $avatar_stmt = $db->prepare($avatar_query);
                $avatar_stmt->bindParam(':avatar_url', $upload_path);
                $avatar_stmt->bindParam(':user_id', $current_user['id']);
                
                if ($avatar_stmt->execute()) {
                    $_SESSION['current_user']['avatar_url'] = $upload_path;
                    $current_user['avatar_url'] = $upload_path;
                    $success_message = 'Avatar updated successfully!';
                }
            }
        }
    }
}

// Get user's avatar URL
$avatar_url = $current_user['avatar_url'] ?? 'https://via.placeholder.com/150/00dbde/ffffff?text=User';

// Format join date
$join_date = isset($current_user['join_date']) ? date('F Y', strtotime($current_user['join_date'])) : date('F Y');

// Calculate progress percentage (example calculation)
$progress_percentage = min(($current_user['current_level'] ?? 1) * 10, 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learniverse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
       * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background: #000;
            color: white;
            line-height: 1.6;
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
        
        /* Home Content - Mobile First Design */
        .home {
            display: block;
            padding: min(5vh, 2rem) min(5vw, 1.5rem);
            padding-bottom: max(100px, 8vh); /* Space for bottom nav */
            opacity: 1;
            min-height: 100vh;
        }
        
        .header {
            text-align: center;
            margin-bottom: clamp(2rem, 6vh, 4rem);
            padding: 0 1rem;
        }
        
        .header h1 {
            font-size: clamp(1.8rem, 6vw, 4rem);
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: clamp(0.5rem, 2vh, 1.5rem);
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        .header p {
            font-size: clamp(0.9rem, 3.5vw, 1.3rem);
            color: rgba(255, 255, 255, 0.8);
            max-width: min(90vw, 600px);
            margin: 0 auto;
            font-weight: 300;
        }
        
        .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(280px, 90vw), 1fr));
            gap: clamp(1rem, 4vw, 2rem);
            max-width: min(95vw, 1200px);
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .box {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: clamp(15px, 3vw, 25px);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            perspective: 1000px;
            position: relative;
            min-height: 300px;
        }
        
        .box:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 20px 60px rgba(252, 0, 255, 0.25),
                0 8px 32px rgba(0, 219, 222, 0.15);
        }
        
        .box img {
            width: 100%;
            height: clamp(150px, 25vw, 200px);
            object-fit: cover;
            transition: transform 0.6s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .box:hover img {
            transform: scale(1.08);
        }
        
        .box-content {
            padding: clamp(1rem, 4vw, 2rem);
            display: flex;
            flex-direction: column;
            height: calc(100% - clamp(150px, 25vw, 200px));
            justify-content: space-between;
        }
        
        .box h3 {
            font-size: clamp(1.1rem, 4vw, 1.6rem);
            margin-bottom: clamp(0.5rem, 2vw, 1rem);
            color: white;
            font-weight: 600;
            letter-spacing: -0.01em;
        }
        
        .box p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: clamp(1rem, 3vw, 1.5rem);
            font-size: clamp(0.85rem, 2.5vw, 1rem);
            line-height: 1.5;
            flex-grow: 1;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: clamp(0.7rem, 3vw, 1rem) clamp(1.2rem, 4vw, 2rem);
            background: linear-gradient(135deg, #00dbde, #fc00ff);
            color: white;
            border: none;
            border-radius: clamp(25px, 5vw, 50px);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            font-size: clamp(0.85rem, 2.5vw, 1rem);
            text-align: center;
            min-height: 44px; /* Touch target */
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 25px rgba(252, 0, 255, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #fc00ff;
            color: #fc00ff;
        }
        
        .btn-outline:hover {
            background: #fc00ff;
            color: white;
        }
        
        /* Top Buttons - Mobile Optimized */
        .user-btn, .settings-btn {
            position: fixed;
            background: linear-gradient(135deg, #00dbde, #fc00ff);
            border: none;
            color: white;
            border-radius: 50%;
            width: clamp(45px, 12vw, 55px);
            height: clamp(45px, 12vw, 55px);
            font-size: clamp(1rem, 3vw, 1.3rem);
            cursor: pointer;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 4px 20px rgba(0, 219, 222, 0.3),
                0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            padding: 0;
        }
        
        .user-btn {
            top: max(20px, 4vw);
            left: max(20px, 4vw);
        }
        
        .settings-btn {
            top: max(20px, 4vw);
            right: max(20px, 4vw);
        }
        
        .user-btn:hover, .settings-btn:hover {
            transform: scale(1.1);
            box-shadow: 
                0 6px 30px rgba(0, 219, 222, 0.4),
                0 3px 12px rgba(0, 0, 0, 0.3);
        }

        .user-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        /* Auth Forms - Mobile First */
        .auth-form {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: clamp(1.5rem, 6vw, 2.5rem);
            border-radius: clamp(15px, 4vw, 25px);
            width: min(90vw, 400px);
            max-height: 90vh;
            overflow-y: auto;
            z-index: 2000;
            display: none;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .auth-form h2 {
            text-align: center;
            margin-bottom: clamp(1rem, 4vw, 2rem);
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: clamp(1.4rem, 5vw, 2.2rem);
            font-weight: 700;
        }
        
        .auth-form input {
            width: 100%;
            padding: clamp(0.8rem, 3vw, 1rem);
            margin-bottom: clamp(0.8rem, 3vw, 1.2rem);
            background: rgba(255, 255, 255, 0.08);
            border: 1.5px solid rgba(255, 255, 255, 0.15);
            border-radius: clamp(8px, 2vw, 12px);
            color: white;
            font-size: clamp(0.9rem, 3vw, 1rem);
            transition: all 0.3s ease;
            min-height: 44px;
        }
        
        .auth-form input:focus {
            outline: none;
            border-color: #00dbde;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(0, 219, 222, 0.1);
        }
        
        .auth-form input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .close-btn {
            position: absolute;
            top: clamp(10px, 3vw, 15px);
            right: clamp(10px, 3vw, 15px);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            font-size: clamp(1.2rem, 4vw, 1.5rem);
            cursor: pointer;
            transition: color 0.3s ease;
            min-width: 44px;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-btn:hover {
            color: #fc00ff;
        }
        
        /* Profile Section - Fully Responsive */
        .profile-section {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 1500;
            display: none;
            overflow-y: auto;
            padding: clamp(1rem, 4vw, 2rem);
        }
        
        .profile-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: clamp(1rem, 4vw, 2rem);
            margin-bottom: clamp(1.5rem, 5vw, 3rem);
            width: 100%;
            max-width: min(95vw, 900px);
            margin-left: auto;
            margin-right: auto;
            padding: 0 1rem;
        }
        
        .profile-header h2 {
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: clamp(1.6rem, 6vw, 2.8rem);
            text-align: center;
            margin: 0;
            font-weight: 700;
            flex: 1 1 auto;
        }
        
        .profile-edit-btn {
            white-space: nowrap;
            font-size: clamp(0.8rem, 2.5vw, 1rem);
            padding: clamp(0.6rem, 2vw, 1rem) clamp(1rem, 3vw, 1.5rem);
            flex-shrink: 0;
        }
        
        .profile-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: min(95vw, 900px);
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: clamp(15px, 4vw, 25px);
            padding: clamp(1.5rem, 5vw, 3rem);
            box-shadow: 
                0 15px 50px rgba(0, 0, 0, 0.3),
                0 5px 20px rgba(0, 0, 0, 0.2);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .profile-avatar {
            width: clamp(100px, 25vw, 160px);
            height: clamp(100px, 25vw, 160px);
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #00dbde;
            margin-bottom: clamp(1rem, 4vw, 2rem);
            transition: all 0.4s ease;
            box-shadow: 0 8px 30px rgba(0, 219, 222, 0.3);
        }
        
        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 40px rgba(0, 219, 222, 0.5);
        }
        
        .profile-info {
            width: 100%;
            margin-bottom: clamp(1.5rem, 4vw, 2.5rem);
        }
        
        .profile-info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: clamp(0.8rem, 3vw, 1.2rem) 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            gap: 1rem;
            min-height: 60px;
            align-items: center;
        }
        
        .profile-info-item:last-child {
            border-bottom: none;
        }
        
        .profile-info-label {
            color: #00dbde;
            font-weight: 600;
            font-size: clamp(0.9rem, 3vw, 1.1rem);
            flex-shrink: 0;
        }
        
        .profile-info-value {
            color: white;
            font-size: clamp(0.9rem, 3vw, 1.1rem);
            text-align: right;
            word-break: break-word;
            flex: 1;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(140px, 40vw), 1fr));
            gap: clamp(0.8rem, 3vw, 1.5rem);
            width: 100%;
            margin-bottom: clamp(1.5rem, 4vw, 2.5rem);
        }
        
        .stat-card {
            background: rgba(0, 219, 222, 0.08);
            border-radius: clamp(10px, 3vw, 15px);
            padding: clamp(1rem, 4vw, 1.5rem);
            text-align: center;
            border: 1px solid rgba(0, 219, 222, 0.2);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            background: rgba(0, 219, 222, 0.15);
            box-shadow: 0 8px 25px rgba(0, 219, 222, 0.2);
        }
        
        .stat-card h3 {
            color: #fc00ff;
            margin-bottom: clamp(0.3rem, 1vw, 0.6rem);
            font-size: clamp(1.1rem, 4vw, 1.6rem);
            font-weight: 700;
        }
        
        .stat-card p {
            color: rgba(255, 255, 255, 0.7);
            font-size: clamp(0.75rem, 2.5vw, 0.95rem);
            font-weight: 500;
        }
    
        .edit-profile-form {
            width: 100%;
            display: none;
        }

        .edit-profile-form input {
            width: 100%;
            padding: clamp(0.8rem, 3vw, 1rem);
            margin-bottom: clamp(0.8rem, 3vw, 1.2rem);
            background: rgba(255, 255, 255, 0.08);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: clamp(8px, 2vw, 12px);
            color: white;
            font-size: clamp(0.9rem, 3vw, 1rem);
            min-height: 44px;
        }
        
        .edit-profile-form input:focus {
            outline: none;
            border-color: #00dbde;
            background: rgba(255, 255, 255, 0.12);
        }
        
        .edit-profile-form label {
            display: block;
            margin-bottom: clamp(0.4rem, 1.5vw, 0.8rem);
            color: rgba(255, 255, 255, 0.8);
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            font-weight: 500;
        }
        
        .avatar-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: clamp(1rem, 4vw, 2rem);
        }
        
        .avatar-preview {
            width: clamp(80px, 20vw, 120px);
            height: clamp(80px, 20vw, 120px);
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: clamp(0.8rem, 3vw, 1.2rem);
            border: 3px solid #fc00ff;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .avatar-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(252, 0, 255, 0.5);
        }
        
        .progress-container {
            width: 100%;
            margin: clamp(1rem, 3vw, 2rem) 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: clamp(0.5rem, 2vw, 1rem);
            color: rgba(255, 255, 255, 0.8);
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            font-weight: 500;
        }
        
        .progress-bar {
            height: clamp(8px, 2vw, 12px);
            background: rgba(255, 255, 255, 0.1);
            border-radius: clamp(4px, 1vw, 6px);
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            border-radius: clamp(4px, 1vw, 6px);
            width: 0%;
            transition: width 0.5s ease;
        }
        
        .badges-container {
            width: 100%;
            margin: clamp(1.5rem, 4vw, 2.5rem) 0;
        }
        
        .badges-title {
            color: #fc00ff;
            margin-bottom: clamp(1rem, 3vw, 1.5rem);
            text-align: center;
            font-size: clamp(1.2rem, 4vw, 1.6rem);
            font-weight: 600;
        }
        
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(60px, 15vw), 1fr));
            gap: clamp(0.8rem, 3vw, 1.2rem);
            max-width: 400px;
            margin: 0 auto;
        }
        
        .badge {
            width: clamp(50px, 15vw, 70px);
            height: clamp(50px, 15vw, 70px);
            border-radius: 50%;
            background: rgba(0, 219, 222, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(1.2rem, 4vw, 1.8rem);
            color: #fc00ff;
            border: 2px solid rgba(252, 0, 255, 0.3);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin: 0 auto;
            cursor: pointer;
        }
        
        .badge:hover {
            transform: scale(1.1);
            background: rgba(0, 219, 222, 0.15);
            box-shadow: 0 0 20px rgba(252, 0, 255, 0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: clamp(0.8rem, 3vw, 1.2rem);
            width: 100%;
            margin-top: clamp(1rem, 3vw, 1.5rem);
            flex-wrap: wrap;
        }
        
        .action-buttons .btn {
            flex: 1;
            min-width: min(120px, 40vw);
        }
        
        .empty-value {
            color: rgba(255, 255, 255, 0.3) !important;
            font-style: italic;
        }

        /* Settings Panel - Mobile Optimized */
        .settings-panel {
            position: fixed;
            top: 0;
            right: -100%;
            width: min(90vw, 450px);
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 2000;
            padding: clamp(1.5rem, 5vw, 2.5rem);
            overflow-y: auto;
            transition: right 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-left: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.3);
        }

        .settings-panel.active {
            right: 0;
        }

        .settings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: clamp(1.5rem, 5vw, 2.5rem);
        }

        .settings-header h2 {
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: clamp(1.4rem, 5vw, 2rem);
            font-weight: 700;
        }

        .settings-content {
            display: flex;
            flex-direction: column;
            gap: clamp(1.5rem, 4vw, 2rem);
        }

        .settings-section {
            background: rgba(255, 255, 255, 0.05);
            padding: clamp(1.2rem, 4vw, 2rem);
            border-radius: clamp(12px, 3vw, 18px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .settings-section h3 {
            color: #00dbde;
            margin-bottom: clamp(1rem, 3vw, 1.5rem);
            font-size: clamp(1.1rem, 4vw, 1.4rem);
            font-weight: 600;
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: clamp(0.8rem, 3vw, 1.2rem) 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: clamp(0.9rem, 3vw, 1rem);
            min-height: 60px;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        /* Toggle Switch - Touch Friendly */
        .switch {
            position: relative;
            display: inline-block;
            width: clamp(45px, 12vw, 55px);
            height: clamp(24px, 6vw, 30px);
            flex-shrink: 0;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.2);
            transition: .4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-radius: clamp(12px, 3vw, 15px);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: clamp(18px, 4vw, 22px);
            width: clamp(18px, 4vw, 22px);
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        input:checked + .slider {
            background: linear-gradient(135deg, #00dbde, #fc00ff);
        }

        input:checked + .slider:before {
            transform: translateX(clamp(20px, 5vw, 25px));
        }

        /* Select Dropdown - Mobile Friendly */
        select {
            background: rgba(255, 255, 255, 0.1);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: clamp(6px, 2vw, 10px);
            padding: clamp(0.6rem, 2vw, 0.8rem);
            color: white;
            min-width: clamp(100px, 25vw, 140px);
            font-size: clamp(0.85rem, 2.5vw, 1rem);
            cursor: pointer;
            min-height: 44px;
        }

        select:focus {
            outline: none;
            border-color: #00dbde;
        }

        /* Social Links */
        .social-links {
            display: flex;
            flex-direction: column;
            gap: clamp(0.8rem, 3vw, 1.2rem);
        }

        .social-link {
            display: flex;
            align-items: center;
            gap: clamp(0.8rem, 3vw, 1.2rem);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: clamp(0.9rem, 3vw, 1rem);
            padding: clamp(0.5rem, 2vw, 0.8rem);
            border-radius: clamp(8px, 2vw, 12px);
            min-height: 50px;
        }

        .social-link:hover {
            color: #fc00ff;
            background: rgba(252, 0, 255, 0.1);
            transform: translateX(5px);
        }

        .social-link i {
            font-size: clamp(1.1rem, 4vw, 1.4rem);
            width: 24px;
            text-align: center;
        }

        /* Setting Buttons */
        .setting-btn {
            width: 100%;
            margin-bottom: clamp(0.5rem, 2vw, 0.8rem);
            min-height: 50px;
        }

        /* Info Modals - Mobile Optimized */
        .info-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: clamp(1rem, 4vw, 2rem);
        }

        .info-modal.active {
            opacity: 1;
            pointer-events: all;
        }

        .modal-content {
            background: rgba(0, 0, 0, 0.92);
            backdrop-filter: blur(20px);
            border-radius: clamp(15px, 4vw, 25px);
            padding: clamp(1.5rem, 5vw, 2.5rem);
            max-width: min(90vw, 700px);
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .modal-content h2 {
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: clamp(1.4rem, 5vw, 2rem);
            font-weight: 700;
            margin-bottom: clamp(1rem, 3vw, 1.5rem);
        }

        .modal-body {
            margin-top: clamp(1rem, 3vw, 1.5rem);
            color: rgba(255, 255, 255, 0.8);
            font-size: clamp(0.9rem, 3vw, 1rem);
            line-height: 1.6;
        }

        /* Bottom Navigation - Enhanced Mobile Design */
        .bottom-nav {
            position: fixed;
            bottom: max(15px, 3vw);
            left: 50%;
            transform: translateX(-50%);
            width: min(95vw, 600px);
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: clamp(18px, 5vw, 25px);
            display: flex;
            justify-content: space-around;
            padding: clamp(0.6rem, 2vw, 1rem) clamp(0.4rem, 2vw, 0.8rem);
            z-index: 1000;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 15px 40px rgba(0, 0, 0, 0.3),
                0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: rgba(255, 255, 255, 0.6);
            padding: clamp(0.4rem, 2vw, 0.8rem) clamp(0.3rem, 1.5vw, 0.6rem);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            text-align: center;
            border-radius: clamp(12px, 3vw, 18px);
            background: transparent;
            min-width: clamp(50px, 12vw, 70px);
            min-height: clamp(50px, 12vw, 70px);
            justify-content: center;
            gap: clamp(0.2rem, 1vw, 0.4rem);
        }

        .nav-item i {
            font-size: clamp(1.1rem, 4vw, 1.6rem);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .nav-item span {
            font-size: clamp(0.65rem, 2.5vw, 0.85rem);
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .nav-item.active {
            background: rgba(0, 219, 222, 0.15);
            color: white;
            box-shadow: 
                0 8px 25px rgba(0, 219, 222, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(0, 219, 222, 0.3);
            transform: translateY(-2px);
        }

        .nav-item.active i {
            background: linear-gradient(135deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            transform: scale(1.1);
            filter: drop-shadow(0 0 10px rgba(252, 0, 255, 0.3));
        }

        .nav-item:not(.active):hover {
            background: rgba(255, 255, 255, 0.08);
            color: #00dbde;
            transform: translateY(-1px);
        }

        .nav-item:not(.active):hover i {
            transform: scale(1.05);
            color: #00dbde;
        }

        /* Active item indicator */
        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: clamp(4px, 1vw, 6px);
            height: clamp(4px, 1vw, 6px);
            background: linear-gradient(135deg, #00dbde, #fc00ff);
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(252, 0, 255, 0.6);
        }

        /* Click animation */
        @keyframes navClick {
            0% { transform: scale(1); }
            50% { transform: scale(0.92); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .nav-item.clicked {
            animation: navClick 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* Hide elements when profile is active */
        .profile-section.active ~ .bottom-nav {
            display: none;
        }

        /* Loading Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        /* Responsive Media Queries - Enhanced */
        
        /* Large Mobile / Small Tablet */
        @media (max-width: 768px) {
            .home {
                padding: clamp(1rem, 4vw, 2rem);
                padding-bottom: 120px;
            }
            
            .box-container {
                grid-template-columns: 1fr;
                gap: clamp(1.2rem, 4vw, 2rem);
                padding: 0;
            }
            
            .box {
                max-width: 100%;
                margin: 0 auto;
            }
            
            .profile-content {
                padding: clamp(1.2rem, 4vw, 2rem);
                margin: 0 0.5rem;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .profile-edit-btn {
                align-self: center;
            }
            
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: clamp(1rem, 3vw, 1.5rem);
            }
            
            .badges-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: clamp(0.8rem, 3vw, 1.2rem);
            }
            
            .action-buttons {
                flex-direction: column;
                gap: clamp(0.8rem, 3vw, 1.2rem);
            }
            
            .action-buttons .btn {
                min-width: 100%;
            }
            
            .settings-panel {
                width: 100%;
                padding: clamp(1.2rem, 4vw, 2rem);
            }
            
            .bottom-nav {
                width: 96vw;
                padding: clamp(0.5rem, 2vw, 0.8rem) clamp(0.2rem, 1vw, 0.4rem);
            }
            
            .nav-item {
                min-width: clamp(45px, 10vw, 60px);
                min-height: clamp(45px, 10vw, 60px);
                padding: clamp(0.3rem, 1.5vw, 0.6rem);
            }
        }

        /* Small Mobile */
        @media (max-width: 480px) {
            .user-btn, .settings-btn {
                width: clamp(40px, 10vw, 50px);
                height: clamp(40px, 10vw, 50px);
                font-size: clamp(0.9rem, 2.5vw, 1.1rem);
                top: max(15px, 3vw);
            }
            
            .user-btn {
                left: max(15px, 3vw);
            }
            
            .settings-btn {
                right: max(15px, 3vw);
            }
            
            .header {
                margin-bottom: clamp(1.5rem, 5vw, 3rem);
            }
            
            .profile-info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                min-height: auto;
                padding: clamp(0.8rem, 3vw, 1.2rem) 0;
            }
            
            .profile-info-value {
                text-align: left;
            }
            
            .profile-stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .badges-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .badge {
                width: clamp(45px, 12vw, 60px);
                height: clamp(45px, 12vw, 60px);
                font-size: clamp(1.1rem, 3.5vw, 1.5rem);
            }
            
            .bottom-nav {
                bottom: max(10px, 2vw);
                width: 98vw;
            }
            
            .nav-item span {
                font-size: clamp(0.6rem, 2vw, 0.75rem);
            }
        }

        /* Extra Small Mobile */
        @media (max-width: 360px) {
            .home {
                padding: clamp(0.8rem, 3vw, 1.5rem);
                padding-bottom: 100px;
            }
            
            .auth-form {
                padding: clamp(1.2rem, 5vw, 2rem);
                width: 95vw;
            }
            
            .profile-content {
                padding: clamp(1rem, 4vw, 1.5rem);
                margin: 0 0.25rem;
            }
            
            .settings-panel {
                padding: clamp(1rem, 4vw, 1.5rem);
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
                gap: clamp(0.8rem, 3vw, 1.2rem);
            }
            
            .badges-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: clamp(0.6rem, 2vw, 1rem);
            }
            
            .modal-content {
                padding: clamp(1.2rem, 4vw, 2rem);
                width: 96vw;
            }
        }

        /* Landscape Mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .profile-section {
                padding: clamp(0.5rem, 2vw, 1rem);
            }
            
            .profile-content {
                padding: clamp(0.8rem, 3vw, 1.5rem);
            }
            
            .profile-header {
                margin-bottom: clamp(1rem, 3vw, 2rem);
            }
            
            .profile-avatar {
                width: clamp(70px, 15vw, 100px);
                height: clamp(70px, 15vw, 100px);
                margin-bottom: clamp(0.8rem, 2vw, 1.2rem);
            }
            
            .profile-stats {
                grid-template-columns: repeat(4, 1fr);
                gap: clamp(0.6rem, 2vw, 1rem);
            }
            
            .badges-container {
                margin: clamp(1rem, 3vw, 1.5rem) 0;
            }
            
            .bottom-nav {
                padding: clamp(0.4rem, 1.5vw, 0.6rem) clamp(0.2rem, 1vw, 0.4rem);
            }
        }

        /* Large Tablets */
        @media (min-width: 769px) and (max-width: 1024px) {
            .box-container {
                grid-template-columns: repeat(2, 1fr);
                gap: clamp(1.5rem, 3vw, 2.5rem);
            }
            
            .profile-stats {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .badges-grid {
                grid-template-columns: repeat(6, 1fr);
            }
            
            .settings-panel {
                width: 400px;
            }
        }

        /* Desktop */
        @media (min-width: 1025px) {
            .box-container {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .bottom-nav {
                width: 500px;
            }
            
            .nav-item {
                padding: 0.8rem 1rem;
            }
            
            .profile-stats {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .badges-grid {
                grid-template-columns: repeat(6, 1fr);
                max-width: 500px;
            }
        }

        /* High DPI Displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .box img, .profile-avatar, .avatar-preview {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            /* Already optimized for dark mode */
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus Styles for Accessibility */
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        .nav-item:focus-visible {
            outline: 3px solid #00dbde;
            outline-offset: 2px;
        }

        /* High Contrast Mode Support */
        @media (prefers-contrast: high) {
            .box {
                border: 2px solid rgba(255, 255, 255, 0.5);
            }
            
            .btn {
                border: 2px solid rgba(255, 255, 255, 0.3);
            }
            
            .nav-item {
                border: 1px solid rgba(255, 255, 255, 0.3);
            }
        }

        /* Print Styles */
        @media print {
            .user-btn, .settings-btn, .bottom-nav, .settings-panel, .auth-form {
                display: none !important;
            }
            
            .home {
                padding: 1rem;
            }
            
            .box {
                break-inside: avoid;
                margin-bottom: 1rem;
            }
        }
</style>
</head>
<body>
    <!-- 3D Animated Background -->
    <div id="canvas-container"></div>
    
    <!-- User Button -->
    <button class="user-btn" id="user-profile-btn" aria-label="User Profile">
        <?php if (!empty($avatar_url)): ?>
            <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="User Profile" onerror="this.src='https://via.placeholder.com/150/00dbde/ffffff?text=User'">
        <?php else: ?>
            <i class="fas fa-user"></i>
        <?php endif; ?>
    </button>
    
    <!-- Settings Button -->
    <button class="settings-btn" id="settings-btn" aria-label="Settings">
        <i class="fas fa-cog"></i>
    </button>
    
    <!-- Home Content -->
    <div class="home" id="home-section">
        <div class="header">
            <h1>Welcome to Learniverse</h1>
            <p>Explore interactive learning experiences across multiple disciplines</p>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="max-width: 600px; margin: 0 auto 2rem auto;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="form-errors" style="max-width: 600px; margin: 0 auto 2rem auto;">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="box-container">
            <div class="box fade-in">
                <img src="polity.jpg" alt="Political Science" loading="lazy">
                <div class="box-content">
                    <h3>Political Science</h3>
                    <p>Explore government systems and political theories</p>
                    <a href="ps.php" class="btn" aria-label="Explore Political Science">Explore</a>
                </div>
            </div>
            
            <div class="box fade-in">
                <img src="geography.jpg" alt="Geography" loading="lazy">
                <div class="box-content">
                    <h3>Geography</h3>
                    <p>Discover the world's landscapes and cultures</p>
                    <a href="geography.php" class="btn" aria-label="Explore Geography">Explore</a>
                </div>
            </div>

            <div class="box fade-in">
                <img src="history.jpg" alt="History" loading="lazy">
                <div class="box-content">
                    <h3>History</h3>
                    <p>Journey through time and civilizations</p>
                    <a href="home.php" class="btn" aria-label="Coming Soon..">Coming Soon..</a>
                </div>
            </div>
            
            <div class="box fade-in">
                <img src="math.jpeg" alt="Mathematics" loading="lazy">
                <div class="box-content">
                    <h3>Mathematics</h3>
                    <p>Master numbers, equations, and problem-solving</p>
                    <a href="home.php" class="btn" aria-label="Coming Soon..">Coming Soon..</a>
                </div>
            </div>

            <div class="box fade-in">
                <img src="science.jpeg" alt="Science" loading="lazy">
                <div class="box-content">
                    <h3>Science</h3>
                    <p>Discover the wonders of the natural world</p>
                    <a href="home.php" class="btn" aria-label="Coming Soon..">Coming Soon..</a>
                </div>
            </div>

            <div class="box fade-in">
                <img src="language.jpeg" alt="Language" loading="lazy">
                <div class="box-content">
                    <h3>Language</h3>
                    <p>Master communication and linguistic skills</p>
                    <a href="home.php" class="btn" aria-label="Coming Soon..">Coming Soon..</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profile Section -->
    <div class="profile-section" id="profile-section" role="main" aria-labelledby="profile-title">
        <div class="profile-header">
            <h2 id="profile-title">Your Profile</h2>
            <button class="btn profile-edit-btn" id="edit-profile-btn">Edit Profile</button>
        </div>
        
        <div class="profile-content">
            <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Profile Avatar" class="profile-avatar" id="profile-avatar" onerror="this.src='https://via.placeholder.com/150/00dbde/ffffff?text=User'">
            
            <div class="profile-info" id="profile-info">
                <div class="profile-info-item">
                    <span class="profile-info-label">Nickname:</span>
                    <span class="profile-info-value" id="nickname-value"><?php echo htmlspecialchars($current_user['nickname'] ?? 'Not set'); ?></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-label">Email:</span>
                    <span class="profile-info-value" id="email-value"><?php echo htmlspecialchars($current_user['email'] ?? 'Not set'); ?></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-label">Member Since:</span>
                    <span class="profile-info-value" id="join-date-value"><?php echo htmlspecialchars($join_date); ?></span>
                </div>
            </div>
            
            <div class="progress-container">
                <div class="progress-label">
                    <span>Learning Progress</span>
                    <span id="progress-percent"><?php echo $progress_percentage; ?>%</span>
                </div>
                <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $progress_percentage; ?>">
                    <div class="progress-fill" id="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-card">
                    <h3 id="level-value"><?php echo $current_user['current_level'] ?? '1'; ?></h3>
                    <p>Levels Completed</p>
                </div>
                <div class="stat-card">
                    <h3 id="performance-value"><?php echo $current_user['performance_score'] ?? '0'; ?>%</h3>
                    <p>Performance Rate</p>
                </div>
                <div class="stat-card">
                    <h3 id="coins-value"><?php echo $current_user['total_coins'] ?? '0'; ?></h3>
                    <p>Coins Earned</p>
                </div>
                <div class="stat-card">
                    <h3 id="streak-value"><?php echo $current_user['current_streak'] ?? '0'; ?></h3>
                    <p>Day Streak</p>
                </div>
            </div>
            
            <div class="badges-container">
                <h3 class="badges-title">Your Badges</h3>
                <div class="badges-grid">
                    <div class="badge" aria-label="Medal Badge"><i class="fas fa-medal"></i></div>
                    <div class="badge" aria-label="Star Badge"><i class="fas fa-star"></i></div>
                    <div class="badge" aria-label="Trophy Badge"><i class="fas fa-trophy"></i></div>
                    <div class="badge" aria-label="Award Badge"><i class="fas fa-award"></i></div>
                    <div class="badge" aria-label="Book Badge"><i class="fas fa-book"></i></div>
                    <div class="badge" aria-label="Brain Badge"><i class="fas fa-brain"></i></div>
                </div>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data" class="edit-profile-form" id="edit-profile-form">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="avatar-upload">
                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar Preview" class="avatar-preview" id="avatar-preview" onerror="this.src='https://via.placeholder.com/150/00dbde/ffffff?text=User'">
                    <input type="file" id="avatar-upload" name="avatar" accept="image/*" aria-label="Upload Avatar">
                </div>
                
                <label for="nickname-input">Nickname</label>
                <input type="text" id="nickname-input" name="nickname" placeholder="Enter your nickname" value="<?php echo htmlspecialchars($current_user['nickname'] ?? ''); ?>" required>
                
                <label for="email-input">Email</label>
                <input type="email" id="email-input" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" required>
                
                <div class="action-buttons">
                    <button type="submit" class="btn" id="save-profile-btn">Save Changes</button>
                    <button type="button" class="btn btn-outline" id="cancel-edit-btn">Cancel</button>
                </div>
            </form>
            
            <button class="btn" id="back-to-home-btn" style="width: 100%; margin-top: 1rem;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </button>
            
            <!-- Logout Button -->
            <form method="POST" action="logout.php" style="width: 100%; margin-top: 1rem;">
                <button type="submit" class="btn btn-outline" style="width: 100%;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Settings Panel -->
    <div class="settings-panel" id="settings-panel" role="dialog" aria-labelledby="settings-title">
        <div class="settings-header">
            <h2 id="settings-title">Settings</h2>
            <button class="close-btn" onclick="hideSettings()" aria-label="Close settings">&times;</button>
        </div>
        
        <div class="settings-content">
            <div class="settings-section">
                <h3>Preferences</h3>
                <div class="setting-item">
                    <span>Sound Effects</span>
                    <label class="switch">
                        <input type="checkbox" id="sound-toggle" checked aria-label="Toggle sound effects">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="setting-item">
                    <span>Vibration</span>
                    <label class="switch">
                        <input type="checkbox" id="vibration-toggle" checked aria-label="Toggle vibration">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="settings-section">
                <h3>Appearance</h3>
                <div class="setting-item">
                    <span>Language</span>
                    <select id="language-select" aria-label="Select language">
                        <option value="en">English</option>
                        <option value="hi">Hindi</option>
                        <option value="es">Spanish</option>
                        <option value="fr">French</option>
                        <option value="de">German</option>
                    </select>
                </div>
                <div class="setting-item">
                    <span>Theme</span>
                    <select id="theme-select" aria-label="Select theme">
                        <option value="dark">Dark</option>
                        <option value="light">Light</option>
                        <option value="purple">Purple</option>
                        <option value="blue">Blue</option>
                    </select>
                </div>
            </div>
            
            <div class="settings-section">
                <h3>Information</h3>
                <button class="btn btn-outline setting-btn" onclick="showGameRules()">Game Rules</button>
                <button class="btn btn-outline setting-btn" onclick="showPrivacyPolicy()">Privacy Policy</button>
            </div>
            
            <div class="settings-section">
                <h3>Find Us On</h3>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Instagram"><i class="fab fa-instagram"></i> Instagram</a>
                    <a href="#" class="social-link" aria-label="Twitter"><i class="fab fa-twitter"></i> Twitter</a>
                    <a href="#" class="social-link" aria-label="Website"><i class="fas fa-globe"></i> Website</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Game Rules Modal -->
    <div class="info-modal" id="game-rules-modal" role="dialog" aria-labelledby="game-rules-title">
        <div class="modal-content">
            <button class="close-btn" onclick="hideModal('game-rules-modal')" aria-label="Close game rules">&times;</button>
            <h2 id="game-rules-title">Game Rules</h2>
            <div class="modal-body">
                <p>Welcome to Learniverse! Here are the essential game rules to help you navigate your learning journey:</p>
                <br>
                <p><strong>Learning Progress:</strong> Complete lessons and quizzes to advance through different subjects. Each completion earns you experience points and coins.</p>
                <br>
                <p><strong>Badges & Achievements:</strong> Unlock special badges by reaching milestones, maintaining streaks, and excelling in different subjects.</p>
                <br>
                <p><strong>Daily Streaks:</strong> Log in and complete at least one activity daily to maintain your learning streak and earn bonus rewards.</p>
                <br>
                <p><strong>Performance Rating:</strong> Your overall performance is calculated based on quiz scores, completion rates, and consistency.</p>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="info-modal" id="privacy-policy-modal" role="dialog" aria-labelledby="privacy-policy-title">
        <div class="modal-content">
            <button class="close-btn" onclick="hideModal('privacy-policy-modal')" aria-label="Close privacy policy">&times;</button>
            <h2 id="privacy-policy-title">Privacy Policy</h2>
            <div class="modal-body">
                <p><strong>Data Collection:</strong> We collect only essential information needed to provide our educational services, including your progress, preferences, and profile information.</p>
                <br>
                <p><strong>Data Usage:</strong> Your information is used solely to enhance your learning experience, track progress, and provide personalized recommendations.</p>
                <br>
                <p><strong>Data Security:</strong> All user data is encrypted and stored securely. We never share personal information with third parties without your consent.</p>
                <br>
                <p><strong>Your Rights:</strong> You can access, modify, or delete your personal data at any time through your profile settings.</p>
                <br>
                <p>For detailed information, please visit our full privacy policy on our website.</p>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <nav class="bottom-nav" role="navigation" aria-label="Main navigation">
        <div class="nav-item" onclick="navigateTo('reward-store')" role="button" tabindex="0" aria-label="Reward Store">
            <i class="fas fa-gift"></i>
            <span>Rewards</span>
        </div>
        <div class="nav-item" onclick="navigateTo('inventory')" role="button" tabindex="0" aria-label="Inventory">
            <i class="fas fa-box-open"></i>
            <span>Inventory</span>
        </div>
        <div class="nav-item active" onclick="navigateTo('home')" role="button" tabindex="0" aria-label="Home">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </div>
        <div class="nav-item" onclick="navigateTo('game')" role="button" tabindex="0" aria-label="Fun Games">
            <i class="fas fa-gamepad"></i>
            <span>Games</span>
        </div>
        <div class="nav-item" >
            <i class="fas fa-user-friends"></i>
            <span>Friends<br> commimg soon..</span>
        </div>
    </nav>
    

    <!-- Three.js for 3D Background -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        // ==================== Performance Optimization ====================
        let isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        // ==================== Three.js Background ====================
        let scene, camera, renderer, particlesMesh, line;
        
        function initThreeJS() {
            const container = document.getElementById('canvas-container');
            
            // Skip heavy animations if reduced motion is preferred
            if (isReducedMotion) {
                container.style.background = 'linear-gradient(135deg, #000 0%, #1a1a2e 50%, #000 100%)';
                return;
            }
            
            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            renderer = new THREE.WebGLRenderer({ 
                alpha: true, 
                antialias: window.devicePixelRatio <= 1,
                powerPreference: "high-performance"
            });
            
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);
            
            // Create particles with reduced count for mobile
            const particlesGeometry = new THREE.BufferGeometry();
            const particleCount = window.innerWidth < 768 ? 1000 : 2000;
            
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
            
            particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
            scene.add(particlesMesh);
            
            // Create lines connecting particles
            const lineGeometry = new THREE.BufferGeometry();
            lineGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
            
            const lineMaterial = new THREE.LineBasicMaterial({
                color: 0xfc00ff,
                transparent: true,
                opacity: 0.1
            });
            
            line = new THREE.Line(lineGeometry, lineMaterial);
            scene.add(line);
            
            camera.position.z = 3;
            
            animate();
        }
        
        // Animation loop with performance optimization
        function animate() {
            if (isReducedMotion) return;
            
            requestAnimationFrame(animate);
            
            if (particlesMesh && line) {
                particlesMesh.rotation.x += 0.0005;
                particlesMesh.rotation.y += 0.0005;
                
                line.rotation.x += 0.0005;
                line.rotation.y += 0.0005;
            }
            
            if (renderer && scene && camera) {
                renderer.render(scene, camera);
            }
        }
        
        // Handle window resize with debouncing
        let resizeTimeout;
        function handleResize() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (camera && renderer) {
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(window.innerWidth, window.innerHeight);
                }
            }, 250);
        }
        
        window.addEventListener('resize', handleResize);

        // ==================== User Interface Functions ====================
        
        // Enhanced DOM ready with fade-in animation
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Three.js background
            initThreeJS();
            
            // Add fade-in animation to boxes
            const boxes = document.querySelectorAll('.box');
            boxes.forEach((box, index) => {
                setTimeout(() => {
                    box.classList.add('fade-in');
                }, index * 100);
            });
            
            // Load saved settings
            loadSettings();
            
            // Add keyboard navigation support
            addKeyboardNavigation();
        });

        // Load settings function
        function loadSettings() {
            try {
                const savedSettings = localStorage.getItem('learniverse_settings');
                if (savedSettings) {
                    const settings = JSON.parse(savedSettings);
                    document.getElementById('sound-toggle').checked = settings.soundEnabled !== false;
                    document.getElementById('vibration-toggle').checked = settings.vibrationEnabled !== false;
                    document.getElementById('language-select').value = settings.language || 'en';
                    document.getElementById('theme-select').value = settings.theme || 'dark';
                    
                    applyTheme(settings.theme || 'dark');
                }
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        }

        // Apply theme function
        function applyTheme(theme) {
            const body = document.body;
            body.className = body.className.replace(/theme-\w+/g, '');
            body.classList.add(`theme-${theme}`);
            
            if (theme === 'light') {
                body.style.background = 'linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 50%, #f5f5f5 100%)';
                body.style.color = '#333';
            } else {
                body.style.background = '#000';
                body.style.color = 'white';
            }
        }

        // Keyboard navigation support
        function addKeyboardNavigation() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    hideSettings();
                    hideAllModals();
                    hideProfileSection();
                }
                
                if (e.key === 'Enter' || e.key === ' ') {
                    const focused = document.activeElement;
                    if (focused.classList.contains('nav-item')) {
                        focused.click();
                    }
                }
            });
        }

        // Profile section functions with error handling
        function showProfileSection() {
            try {
                document.getElementById('profile-section').style.display = 'block';
                document.getElementById('home-section').style.display = 'none';
                document.querySelector('.bottom-nav').style.display = 'none';
                
                // Scroll to top
                document.getElementById('profile-section').scrollTop = 0;
            } catch (error) {
                console.error('Error showing profile section:', error);
            }
        }

        function hideProfileSection() {
            try {
                document.getElementById('profile-section').style.display = 'none';
                document.getElementById('home-section').style.display = 'block';
                document.querySelector('.bottom-nav').style.display = 'flex';
                updateActiveNav('home');
                
                // Reset edit form
                document.getElementById('profile-info').style.display = 'block';
                document.getElementById('edit-profile-form').style.display = 'none';
            } catch (error) {
                console.error('Error hiding profile section:', error);
            }
        }

        // ==================== Event Listeners ====================
        
        // Profile button click with error handling
        document.getElementById('user-profile-btn')?.addEventListener('click', function() {
            try {
                showProfileSection();
            } catch (error) {
                console.error('Error handling profile button click:', error);
            }
        });

        // Back to home button
        document.getElementById('back-to-home-btn')?.addEventListener('click', function() {
            hideProfileSection();
        });

        // Edit profile functionality
        document.getElementById('edit-profile-btn')?.addEventListener('click', function() {
            try {
                document.getElementById('profile-info').style.display = 'none';
                document.getElementById('edit-profile-form').style.display = 'block';
                
                // Focus first input
                const nicknameInput = document.getElementById('nickname-input');
                if (nicknameInput) nicknameInput.focus();
                
            } catch (error) {
                console.error('Error entering edit mode:', error);
            }
        });

        // Cancel edit
        document.getElementById('cancel-edit-btn')?.addEventListener('click', function() {
            document.getElementById('profile-info').style.display = 'block';
            document.getElementById('edit-profile-form').style.display = 'none';
        });

        // Settings functions
        function showSettings() {
            hideAllModals();
            document.getElementById('settings-panel')?.classList.add('active');
        }

        function hideSettings() {
            document.getElementById('settings-panel')?.classList.remove('active');
        }

        function showModal(modalId) {
            hideAllModals();
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                
                // Focus close button for accessibility
                const closeBtn = modal.querySelector('.close-btn');
                if (closeBtn) {
                    setTimeout(() => closeBtn.focus(), 100);
                }
            }
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
            }
        }

        function hideAllModals() {
            document.querySelectorAll('.info-modal').forEach(modal => {
                modal.classList.remove('active');
            });
        }

        function showGameRules() {
            hideSettings();
            showModal('game-rules-modal');
        }

        function showPrivacyPolicy() {
            hideSettings();
            showModal('privacy-policy-modal');
        }

        // Settings button click
        document.getElementById('settings-btn')?.addEventListener('click', showSettings);

        // Save settings when changed with debouncing
        let settingsTimeout;
        function saveSettingsDebounced() {
            clearTimeout(settingsTimeout);
            settingsTimeout = setTimeout(saveSettings, 300);
        }

        function saveSettings() {
            try {
                const settings = {
                    soundEnabled: document.getElementById('sound-toggle')?.checked ?? true,
                    vibrationEnabled: document.getElementById('vibration-toggle')?.checked ?? true,
                    language: document.getElementById('language-select')?.value ?? 'en',
                    theme: document.getElementById('theme-select')?.value ?? 'dark'
                };
                
                localStorage.setItem('learniverse_settings', JSON.stringify(settings));
                applyTheme(settings.theme);
                
            } catch (error) {
                console.error('Error saving settings:', error);
            }
        }

        // Add event listeners to settings controls
        document.querySelectorAll('.settings-content input, .settings-content select').forEach(element => {
            element.addEventListener('change', saveSettingsDebounced);
        });

        // Navigation functions with error handling
        function navigateTo(page) {
            try {
                // Add click animation
                const clickedItem = event.currentTarget;
                if (clickedItem) {
                    clickedItem.classList.add('clicked');
                    setTimeout(() => {
                        clickedItem.classList.remove('clicked');
                    }, 400);
                }
                
                updateActiveNav(page);
                
                // Navigation logic
                switch(page) {
                    case 'home':
                        hideProfileSection();
                        hideSettings();
                        hideAllModals();
                        break;
                    case 'reward-store':
                        window.location.href = 'reward.php';
                        break;
                    case 'inventory':
                        window.location.href = 'inventory.php';
                        break;
                    case 'game':
                        window.location.href = 'fungame.php';
                        break;
                    case 'friends':
                        window.location.href = 'friends.php';
                        break;
                    default:
                        console.warn('Unknown navigation page:', page);
                }
                
            } catch (error) {
                console.error('Navigation error:', error);
            }
        }

        // Update active navigation state
        function updateActiveNav(activePage) {
            try {
                document.querySelectorAll('.nav-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                const activeItem = document.querySelector(`[onclick="navigateTo('${activePage}')"]`);
                if (activeItem) {
                    activeItem.classList.add('active');
                }
            } catch (error) {
                console.error('Error updating navigation:', error);
            }
        }

        // Add click handlers for keyboard accessibility
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });

        // Avatar upload handling with preview
        document.getElementById('avatar-upload')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file.');
                    return;
                }
                
                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size must be less than 5MB.');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('avatar-preview');
                    if (preview) {
                        preview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });

        // Avatar preview click to trigger file input
        document.getElementById('avatar-preview')?.addEventListener('click', function() {
            document.getElementById('avatar-upload')?.click();
        });

        // Form input validation on blur
        document.getElementById('email-input')?.addEventListener('blur', function() {
            const email = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#ff4444';
                this.setAttribute('aria-invalid', 'true');
            } else {
                this.style.borderColor = '';
                this.setAttribute('aria-invalid', 'false');
            }
        });

        // Enhanced error handling for missing elements
        function safeGetElement(id) {
            const element = document.getElementById(id);
            if (!element) {
                console.warn(`Element with id '${id}' not found`);
            }
            return element;
        }

        // Smooth scroll to top when opening modals
        function smoothScrollToTop(element) {
            if (element && element.scrollTo) {
                element.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            // Close info modals when clicking outside
            if (e.target.classList.contains('info-modal')) {
                hideAllModals();
            }
        });

        // Prevent modal content clicks from closing modals
        document.querySelectorAll('.modal-content').forEach(element => {
            element.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const successMessages = document.querySelectorAll('.alert-success');
            successMessages.forEach(function(message) {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);

        // Final initialization
        console.log('Learniverse PHP initialized successfully!');
    </script>
    <button onclick="window.location.href='leaderboard.php'" 
        class="floating-btn"
        style="position: fixed; bottom: 100px; right: 30px; width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(45deg, #ffd700, #ff6b00); color: white; border: none; font-size: 24px; cursor: pointer; box-shadow: 0 4px 20px rgba(255, 215, 0, 0.3); z-index: 1000;">
    🏆
</button>
</body>
</html>