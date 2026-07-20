<?php
// Start session at the very top
session_start();

// Include database configuration and models
require_once 'Config/database.php';
require_once 'Models/User.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize User model
$user = new User($db);

// Password hashing function
function simpleHash($password) {
    return hash('sha256', $password);
}

// Avatar URLs mapping
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

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $errors = [];
    
    if ($action === 'signup') {
        $email = trim($_POST['email'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Email validation
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } elseif (!str_ends_with($email, '@gmail.com')) {
            $errors['email'] = 'Email must be a @gmail.com address';
        } else {
            // Check if email already exists (real-time validation)
            try {
                if ($user->emailExists($email)) {
                    $errors['email'] = 'Email already registered. Please use a different email or login.';
                }
            } catch (Exception $e) {
                $errors['email'] = 'Error checking email availability. Please try again.';
            }
        }
        
        // Nickname validation
        if (empty($nickname)) {
            $errors['nickname'] = 'Nickname is required';
        } elseif (strlen($nickname) < 3) {
            $errors['nickname'] = 'Nickname must be at least 3 characters long';
        } elseif (strlen($nickname) > 20) {
            $errors['nickname'] = 'Nickname must be less than 20 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $nickname)) {
            $errors['nickname'] = 'Nickname can only contain letters, numbers, and underscores';
        } else {
            // Check if nickname already exists (real-time validation)
            try {
                if ($user->nicknameExists($nickname)) {
                    $errors['nickname'] = 'Nickname already taken. Please choose a different one.';
                }
            } catch (Exception $e) {
                $errors['nickname'] = 'Error checking nickname availability. Please try again.';
            }
        }
        
        // Password validation
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } else {
            if (strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            } elseif (!preg_match('/^[A-Z]/', $password)) {
                $errors['password'] = 'First letter must be capital';
            } elseif (!preg_match('/[a-z]/', $password)) {
                $errors['password'] = 'Must contain at least one lowercase letter';
            } elseif (!preg_match('/[0-9]/', $password)) {
                $errors['password'] = 'Must contain at least one number';
            } elseif (!preg_match('/[@$!%*?&]/', $password)) {
                $errors['password'] = 'Must contain at least one special character (@$!%*?&)';
            } elseif (preg_match('/\s/', $password)) {
                $errors['password'] = 'Password cannot contain spaces';
            }
        }
        
        // Confirm password validation
        if (empty($confirm_password)) {
            $errors['confirm_password'] = 'Please confirm your password';
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        // If no errors, proceed with user creation
        if (empty($errors)) {
            try {
                // Create new user with updated database structure
                $user->email = $email;
                $user->nickname = $nickname;
                $user->password_hash = simpleHash($password);
                $user->avatar_url = $avatarUrls['1'];
                $user->avatar_id = '1';
                $user->current_level = 1;
                $user->current_streak = 0;
                $user->performance_score = 0.00;
                $user->is_active = 1;
                $user->coins = 50;
                
                $user_id = $user->create();
                
                if ($user_id) {
                    // Get the created user
                    $stmt = $user->getById($user_id);
                    if ($stmt->rowCount() == 1) {
                        $_SESSION['current_user'] = $stmt->fetch(PDO::FETCH_ASSOC);
                        $_SESSION['show_avatar_selection'] = true;
                        
                        // Clear any previous form data
                        unset($_POST);
                        
                        // Redirect to avoid form resubmission
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit();
                    }
                } else {
                    $errors['general'] = 'Failed to create account. Please try again.';
                }
            } catch (Exception $e) {
                $errors['general'] = '.';
            }
        }
        
    } elseif ($action === 'login') {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($identifier)) {
            $errors['identifier'] = 'Email or nickname is required';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }
        
        if (empty($errors)) {
            // Try to find user by email first, then by nickname
            $stmt = $user->getByEmail($identifier);
            if ($stmt->rowCount() == 0) {
                $stmt = $user->getByNickname($identifier);
            }
            
            if ($stmt->rowCount() == 1) {
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data['password_hash'] === simpleHash($password)) {
                    $_SESSION['current_user'] = $user_data;
                    $_SESSION['show_avatar_selection'] = true;
                    
                    // Redirect to avoid form resubmission
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $errors['password'] = 'Incorrect password';
                }
            } else {
                $errors['identifier'] = 'Email or nickname not found';
            }
        }
        
    } elseif ($action === 'select_avatar') {
        $selectedAvatar = $_POST['selected_avatar'] ?? '1';
        
        if (isset($_SESSION['current_user'])) {
            $avatar_url = $avatarUrls[$selectedAvatar] ?? $avatarUrls['1'];
            
            // Update user avatar in database
            if ($user->updateAvatar($_SESSION['current_user']['id'], $avatar_url, $selectedAvatar)) {
                // Update session data
                $_SESSION['current_user']['avatar_url'] = $avatar_url;
                $_SESSION['current_user']['avatar_id'] = $selectedAvatar;
                $_SESSION['user_avatar'] = $avatar_url;
                unset($_SESSION['show_avatar_selection']);
                
                // Redirect to avoid form resubmission
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    }
}

// Check what to show initially
$showAvatarSelection = isset($_SESSION['show_avatar_selection']);
$showSuccessScreen = isset($_SESSION['current_user']) && isset($_SESSION['user_avatar']);
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #1a1a2e;
            color: #fff;
            overflow-x: hidden;
            min-height: 100vh;
            touch-action: pan-y;
        }

        /* Splash Screen */
        .splash {
            position: fixed;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            background: #000;
            animation: fadeOut 1s ease 3s forwards;
        }

        .title-video {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.7;
        }

        .title-text {
            position: relative;
            font-size: clamp(3rem, 8vw, 6rem);
            font-weight: 800;
            text-transform: uppercase;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 0 20px rgba(252, 0, 255, 0.5);
            animation: zoomIn 1.5s ease, pulse 2s ease 1.5s infinite;
            text-align: center;
            padding: 0 1rem;
        }

        @keyframes zoomIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes fadeOut {
            to { opacity: 0; visibility: hidden; }
        }

        /* Auth Containers */
        .auth-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(26, 26, 46, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: clamp(1rem, 4vw, 2rem);
            width: clamp(300px, 90vw, 450px);
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: none;
            z-index: 100;
        }

        .auth-header {
            text-align: center;
            margin-bottom: clamp(1rem, 3vw, 2rem);
        }

        .auth-header h2 {
            font-size: clamp(1.2rem, 4vw, 1.8rem);
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .auth-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: clamp(0.8rem, 2.5vw, 0.9rem);
        }

        .input-group {
            margin-bottom: clamp(0.8rem, 2.5vw, 1.2rem);
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: clamp(0.8rem, 2.2vw, 0.9rem);
        }

        .input-group input {
            width: 100%;
            padding: clamp(0.6rem, 2vw, 0.8rem);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #fc00ff;
            box-shadow: 0 0 0 2px rgba(252, 0, 255, 0.2);
        }

        .btn {
            width: 100%;
            padding: clamp(0.6rem, 2vw, 0.8rem);
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(252, 0, 255, 0.3);
        }

        .auth-footer {
            text-align: center;
            margin-top: clamp(1rem, 3vw, 1.5rem);
            font-size: clamp(0.8rem, 2.2vw, 0.9rem);
            color: rgba(255, 255, 255, 0.7);
        }

        .auth-footer a {
            color: #00dbde;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Avatar Selection */
        .avatar-selection {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #1a1a2e;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 4vw, 2rem);
            z-index: 200;
            overflow: hidden;
        }

        .avatar-header {
            text-align: center;
            margin-bottom: clamp(1.5rem, 4vw, 2rem);
            max-width: 800px;
            width: 100%;
        }

        .avatar-header h1 {
            font-size: clamp(1.5rem, 5vw, 2.2rem);
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .avatar-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: clamp(0.9rem, 2.5vw, 1rem);
        }

        .avatar-carousel {
            width: 100%;
            max-width: clamp(280px, 80vw, 400px);
            height: clamp(200px, 50vw, 300px);
            position: relative;
            overflow: hidden;
            margin-bottom: clamp(1rem, 4vw, 2rem);
        }

        .avatar-slider {
            display: flex;
            height: 100%;
            transition: transform 0.5s ease;
        }

        .avatar-slide {
            min-width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-slide img {
            width: clamp(120px, 35vw, 200px);
            height: clamp(120px, 35vw, 200px);
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .avatar-slide.active img {
            border-color: #00dbde;
            box-shadow: 0 0 20px rgba(0, 219, 222, 0.5);
            transform: scale(1.1);
        }

        .avatar-indicators {
            display: flex;
            justify-content: center;
            gap: clamp(6px, 2vw, 10px);
            margin-bottom: clamp(1rem, 4vw, 2rem);
            flex-wrap: wrap;
        }

        .indicator {
            width: clamp(8px, 2.5vw, 10px);
            height: clamp(8px, 2.5vw, 10px);
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background: #00dbde;
            transform: scale(1.2);
        }

        .avatar-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
        }

        #confirm-avatar {
            max-width: clamp(150px, 50vw, 200px);
        }

        /* Success Screen */
        .success-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #1a1a2e;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 300;
            text-align: center;
            padding: clamp(1rem, 4vw, 2rem);
        }

        .success-screen h1 {
            font-size: clamp(1.8rem, 6vw, 2.5rem);
            margin-bottom: 1rem;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .success-screen p {
            color: rgba(255, 255, 255, 0.8);
            font-size: clamp(1rem, 3vw, 1.2rem);
            margin-bottom: clamp(1.5rem, 4vw, 2rem);
            max-width: 600px;
            line-height: 1.5;
        }

        .success-avatar {
            width: clamp(100px, 25vw, 150px);
            height: clamp(100px, 25vw, 150px);
            border-radius: 50%;
            margin-bottom: clamp(1.5rem, 4vw, 2rem);
            border: 4px solid #00dbde;
            object-fit: cover;
        }

        .success-btn {
            max-width: clamp(150px, 50vw, 200px);
        }

        /* Responsive breakpoints */
        @media (max-width: 480px) {
            .auth-container {
                width: 95vw;
                padding: 1.5rem;
            }
            
            .avatar-carousel {
                height: 220px;
            }
            
            .avatar-slide img {
                width: 140px;
                height: 140px;
            }
            
            .avatar-indicators {
                gap: 8px;
            }
        }

        @media (max-width: 320px) {
            .auth-container {
                width: 98vw;
                padding: 1rem;
            }
            
            .title-text {
                font-size: 2.5rem;
            }
            
            .avatar-carousel {
                height: 180px;
            }
            
            .avatar-slide img {
                width: 120px;
                height: 120px;
            }
        }

        @media (min-width: 768px) {
            .auth-container {
                max-width: 400px;
                padding: 2rem;
            }
            
            .avatar-carousel {
                max-width: 350px;
                height: 280px;
            }
        }

        @media (min-width: 1024px) {
            .auth-container {
                max-width: 450px;
            }
            
            .avatar-carousel {
                max-width: 400px;
                height: 300px;
            }
            
            .avatar-slide img {
                width: 200px;
                height: 200px;
            }
        }

        /* Landscape orientation adjustments */
        @media (orientation: landscape) and (max-height: 600px) {
            .auth-container {
                max-height: 85vh;
                padding: 1rem;
            }
            
            .auth-header {
                margin-bottom: 1rem;
            }
            
            .input-group {
                margin-bottom: 0.8rem;
            }
            
            .avatar-selection {
                padding: 1rem;
            }
            
            .avatar-header {
                margin-bottom: 1rem;
            }
            
            .avatar-carousel {
                height: 200px;
            }
            
            .avatar-indicators {
                margin-bottom: 1rem;
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .title-text {
                text-shadow: 0 0 40px rgba(252, 0, 255, 0.5);
            }
        }

        /* Accessibility improvements */
        @media (prefers-reduced-motion: reduce) {
            .title-text {
                animation: none;
            }
            
            .splash {
                animation: none;
            }
            
            .avatar-slider {
                transition: none;
            }
            
            .btn:hover {
                transform: none;
            }
        }

        /* Focus styles for better accessibility */
        .btn:focus,
        .input-group input:focus,
        .indicator:focus,
        .auth-footer a:focus {
            outline: 2px solid #00dbde;
            outline-offset: 2px;
        }

        /* Error message styles */
        .error-message {
            color: #ff4757 !important;
            font-size: clamp(0.7rem, 2vw, 0.8rem) !important;
            margin-top: 5px !important;
            display: block;
        }

        /* Password visibility toggle */
        .password-toggle {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            cursor: pointer !important;
            font-size: clamp(16px, 4vw, 18px) !important;
            user-select: none !important;
            z-index: 10 !important;
        }

        /* Ensure input fields have proper spacing for toggle */
        .input-group {
            position: relative;
        }

        .input-group input[type="password"],
        .input-group input[type="text"] {
            padding-right: 40px !important;
        }
        
        /* Enhanced error styles */
        .error-message {
            color: #ff4757 !important;
            font-size: clamp(0.7rem, 2vw, 0.8rem) !important;
            margin-top: 5px !important;
            display: block;
        }
        
        .input-error {
            border-color: #ff4757 !important;
            background: rgba(255, 71, 87, 0.1) !important;
        }
        
        .input-success {
            border-color: #2ed573 !important;
        }
        
        .validation-icon {
            position: absolute;
            right: 35px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
        }
        
        .success-icon {
            color: #2ed573;
        }
        
        .error-icon {
            color: #ff4757;
        }
        
        .loading-icon {
            color: #ffa502;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Splash Screen -->
    <div class="splash" id="splash-screen">
        <video class="title-video" autoplay muted loop>
            <source src="https://assets.mixkit.co/videos/preview/mixkit-abstract-digital-grid-with-lines-17305-large.mp4" type="video/mp4">
        </video>
        <h1 class="title-text">LEARNIVERSE</h1>
    </div>

    <!-- Login Form -->
    <div class="auth-container" id="login-container">
        <div class="auth-header">
            <h2>Welcome Back!</h2>
            <p>Login to continue your learning journey</p>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="error-message" style="text-align: center; margin-bottom: 1rem;"><?php echo $errors['general']; ?></div>
        <?php endif; ?>
        
        <form id="login-form" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="input-group">
                <label for="login-identifier">Email or Nickname</label>
                <input type="text" id="login-identifier" name="identifier" required 
                       value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>">
                <?php if (isset($errors['identifier'])): ?>
                    <div class="error-message"><?php echo $errors['identifier']; ?></div>
                <?php endif; ?>
            </div>
            <div class="input-group">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="#" id="show-signup">Sign up</a></p>
        </div>
    </div>

    <!-- Signup Form -->
    <div class="auth-container" id="signup-container">
        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Join the Learniverse community</p>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="error-message" style="text-align: center; margin-bottom: 1rem;"><?php echo $errors['general']; ?></div>
        <?php endif; ?>
        
        <form id="signup-form" method="POST">
            <input type="hidden" name="action" value="signup">
            
            <!-- Email Field -->
            <div class="input-group">
                <label for="signup-email">Email</label>
                <input type="email" id="signup-email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       class="<?php echo isset($errors['email']) ? 'input-error' : ''; ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
                <div id="email-validation"></div>
            </div>
            
            <!-- Nickname Field -->
            <div class="input-group">
                <label for="signup-nickname">Nickname</label>
                <input type="text" id="signup-nickname" name="nickname" required 
                       value="<?php echo isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : ''; ?>"
                       class="<?php echo isset($errors['nickname']) ? 'input-error' : ''; ?>">
                <?php if (isset($errors['nickname'])): ?>
                    <div class="error-message"><?php echo $errors['nickname']; ?></div>
                <?php endif; ?>
                <div id="nickname-validation"></div>
            </div>
            
            <!-- Password Field -->
            <div class="input-group">
                <label for="signup-password">Password</label>
                <input type="password" id="signup-password" name="password" required
                       class="<?php echo isset($errors['password']) ? 'input-error' : ''; ?>">
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
                <div id="password-validation"></div>
            </div>
            
            <!-- Confirm Password Field -->
            <div class="input-group">
                <label for="signup-confirm">Confirm Password</label>
                <input type="password" id="signup-confirm" name="confirm_password" required
                       class="<?php echo isset($errors['confirm_password']) ? 'input-error' : ''; ?>">
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
                <?php endif; ?>
                <div id="confirm-validation"></div>
            </div>
            
            <button type="submit" class="btn" id="signup-button">Create Account</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="#" id="show-login">Login</a></p>
        </div>
    </div>

    <!-- Avatar Selection -->
    <div class="avatar-selection" id="avatar-selection">
        <div class="avatar-header">
            <h1>Select Your Avatar</h1>
            <p>Swipe left or right to browse options</p>
        </div>
        
        <div class="avatar-carousel">
            <div class="avatar-slider" id="avatar-slider">
                <?php foreach ($avatarUrls as $id => $url): ?>
                <div class="avatar-slide <?php echo $id == 1 ? 'active' : ''; ?>">
                    <img src="<?php echo $url; ?>" alt="Avatar <?php echo $id; ?>" data-avatar="<?php echo $id; ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="avatar-indicators" id="avatar-indicators">
            <?php for ($i = 1; $i <= count($avatarUrls); $i++): ?>
            <div class="indicator <?php echo $i == 1 ? 'active' : ''; ?>"></div>
            <?php endfor; ?>
        </div>
        
        <div class="avatar-preview">
            <form method="POST" style="width: 100%; display: flex; justify-content: center;">
                <input type="hidden" name="action" value="select_avatar">
                <input type="hidden" name="selected_avatar" id="selected-avatar-input" value="1">
                <button type="submit" id="confirm-avatar" class="btn">Select Avatar</button>
            </form>
        </div>
    </div>

    <!-- Success Screen -->
    <div class="success-screen" id="success-screen">
        <?php if (isset($_SESSION['user_avatar'])): ?>
            <img class="success-avatar" id="success-avatar" src="<?php echo htmlspecialchars($_SESSION['user_avatar']); ?>" alt="Your Avatar">
        <?php endif; ?>
        <h1>Welcome to Learniverse!</h1>
        <p>Your account has been created and your avatar selected. Get ready to explore a universe of learning!</p>
        <a href="standard.php" class="btn success-btn" id="continue-btn">Continue</a>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const splashScreen = document.querySelector('.splash');
        const loginContainer = document.getElementById('login-container');
        const signupContainer = document.getElementById('signup-container');
        const avatarSelection = document.getElementById('avatar-selection');
        const successScreen = document.getElementById('success-screen');
        const showSignupLink = document.getElementById('show-signup');
        const showLoginLink = document.getElementById('show-login');
        const loginForm = document.getElementById('login-form');
        const signupForm = document.getElementById('signup-form');
        const signupButton = document.getElementById('signup-button');
        const avatarSlider = document.getElementById('avatar-slider');
        const avatarIndicators = document.getElementById('avatar-indicators').children;
        const selectedAvatarInput = document.getElementById('selected-avatar-input');
        const confirmAvatarBtn = document.getElementById('confirm-avatar');
        const successAvatar = document.getElementById('success-avatar');
        const continueBtn = document.getElementById('continue-btn');

        // Form elements for validation
        const emailInput = document.getElementById('signup-email');
        const nicknameInput = document.getElementById('signup-nickname');
        const passwordInput = document.getElementById('signup-password');
        const confirmInput = document.getElementById('signup-confirm');

        // Carousel variables
        let currentSlide = 0;
        let startX = 0;
        let endX = 0;
        const slides = document.querySelectorAll('.avatar-slide');
        const totalSlides = slides.length;

        // Determine which screen to show after splash
        <?php if ($showSuccessScreen): ?>
            // User is logged in and has avatar - show success screen immediately
            setTimeout(() => {
                splashScreen.style.display = 'none';
                successScreen.style.display = 'flex';
            }, 3000);
        <?php elseif ($showAvatarSelection): ?>
            // User needs to select avatar
            setTimeout(() => {
                splashScreen.style.display = 'none';
                avatarSelection.style.display = 'flex';
                goToSlide(0);
            }, 3000);
        <?php else: ?>
            // Show login form by default
            setTimeout(() => {
                splashScreen.style.display = 'none';
                loginContainer.style.display = 'block';
            }, 3000);
        <?php endif; ?>

        // Toggle between login and signup forms
        showSignupLink.addEventListener('click', function(e) {
            e.preventDefault();
            loginContainer.style.display = 'none';
            signupContainer.style.display = 'block';
            clearErrors();
        });

        showLoginLink.addEventListener('click', function(e) {
            e.preventDefault();
            signupContainer.style.display = 'none';
            loginContainer.style.display = 'block';
            clearErrors();
        });

        // Initialize password visibility toggles
        setupPasswordVisibility();

        // Real-time validation
        setupRealTimeValidation();

        // Avatar selection functionality
        avatarSlider.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        }, { passive: true });

        avatarSlider.addEventListener('touchmove', (e) => {
            endX = e.touches[0].clientX;
        }, { passive: true });

        avatarSlider.addEventListener('touchend', () => {
            if (startX - endX > 50) {
                goToSlide(currentSlide + 1);
            } else if (endX - startX > 50) {
                goToSlide(currentSlide - 1);
            }
        });

        // Mouse drag support for desktop
        avatarSlider.addEventListener('mousedown', (e) => {
            startX = e.clientX;
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });

        function handleMouseMove(e) {
            endX = e.clientX;
        }

        function handleMouseUp() {
            if (startX - endX > 50) {
                goToSlide(currentSlide + 1);
            } else if (endX - startX > 50) {
                goToSlide(currentSlide - 1);
            }
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        }

        // Go to specific slide
        function goToSlide(slideIndex) {
            if (slideIndex < 0) slideIndex = totalSlides - 1;
            if (slideIndex >= totalSlides) slideIndex = 0;
            
            currentSlide = slideIndex;
            avatarSlider.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            // Update active classes
            document.querySelectorAll('.avatar-slide').forEach((slide, index) => {
                if (index === currentSlide) {
                    slide.classList.add('active');
                    // Update the hidden input with selected avatar
                    const avatarId = slide.querySelector('img').getAttribute('data-avatar');
                    selectedAvatarInput.value = avatarId;
                } else {
                    slide.classList.remove('active');
                }
            });
            
            // Update indicators
            Array.from(avatarIndicators).forEach((indicator, index) => {
                if (index === currentSlide) {
                    indicator.classList.add('active');
                } else {
                    indicator.classList.remove('active');
                }
            });
        }

        // Click on indicators to navigate
        Array.from(avatarIndicators).forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                goToSlide(index);
            });
        });

        // Real-time validation setup
        function setupRealTimeValidation() {
            // Email validation
            emailInput.addEventListener('blur', validateEmail);
            emailInput.addEventListener('input', debounce(validateEmail, 500));

            // Nickname validation
            nicknameInput.addEventListener('blur', validateNickname);
            nicknameInput.addEventListener('input', debounce(validateNickname, 500));

            // Password validation
            passwordInput.addEventListener('blur', validatePassword);
            passwordInput.addEventListener('input', debounce(validatePassword, 300));

            // Confirm password validation
            confirmInput.addEventListener('blur', validateConfirmPassword);
            confirmInput.addEventListener('input', debounce(validateConfirmPassword, 300));
        }

        // Validation functions
        function validateEmail() {
            const email = emailInput.value.trim();
            const validationDiv = document.getElementById('email-validation');
            
            if (!email) {
                showValidation(validationDiv, 'Email is required', 'error');
                setInputStatus(emailInput, 'error');
                return false;
            }

            if (!isValidEmail(email)) {
                showValidation(validationDiv, 'Invalid email format', 'error');
                setInputStatus(emailInput, 'error');
                return false;
            }

            if (!email.endsWith('@gmail.com')) {
                showValidation(validationDiv, 'Email must be a @gmail.com address', 'error');
                setInputStatus(emailInput, 'error');
                return false;
            }

            // Check if email exists via AJAX
            showValidation(validationDiv, 'Checking availability...', 'loading');
            
            checkEmailExists(email).then(exists => {
                if (exists) {
                    showValidation(validationDiv, 'Email already registered', 'error');
                    setInputStatus(emailInput, 'error');
                } else {
                    showValidation(validationDiv, 'Email is available', 'success');
                    setInputStatus(emailInput, 'success');
                }
            }).catch(error => {
                showValidation(validationDiv, 'Error checking email', 'error');
                setInputStatus(emailInput, 'error');
            });

            return true;
        }

        function validateNickname() {
            const nickname = nicknameInput.value.trim();
            const validationDiv = document.getElementById('nickname-validation');
            
            if (!nickname) {
                showValidation(validationDiv, 'Nickname is required', 'error');
                setInputStatus(nicknameInput, 'error');
                return false;
            }

            if (nickname.length < 3) {
                showValidation(validationDiv, 'Nickname must be at least 3 characters', 'error');
                setInputStatus(nicknameInput, 'error');
                return false;
            }

            if (nickname.length > 20) {
                showValidation(validationDiv, 'Nickname must be less than 20 characters', 'error');
                setInputStatus(nicknameInput, 'error');
                return false;
            }

            if (!/^[a-zA-Z0-9_]+$/.test(nickname)) {
                showValidation(validationDiv, 'Only letters, numbers, and underscores allowed', 'error');
                setInputStatus(nicknameInput, 'error');
                return false;
            }

            // Check if nickname exists via AJAX
            showValidation(validationDiv, 'Checking availability...', 'loading');
            
            checkNicknameExists(nickname).then(exists => {
                if (exists) {
                    showValidation(validationDiv, 'Nickname already taken', 'error');
                    setInputStatus(nicknameInput, 'error');
                } else {
                    showValidation(validationDiv, 'Nickname is available', 'success');
                    setInputStatus(nicknameInput, 'success');
                }
            }).catch(error => {
                showValidation(validationDiv, 'Error checking nickname', 'error');
                setInputStatus(nicknameInput, 'error');
            });

            return true;
        }

        function validatePassword() {
            const password = passwordInput.value;
            const validationDiv = document.getElementById('password-validation');
            
            if (!password) {
                showValidation(validationDiv, 'Password is required', 'error');
                setInputStatus(passwordInput, 'error');
                return false;
            }

            if (password.length < 8) {
                showValidation(validationDiv, 'Must be at least 8 characters', 'error');
                setInputStatus(passwordInput, 'error');
                return false;
            }

            if (!/^[A-Z]/.test(password)) {
                showValidation(validationDiv, 'First letter must be capital', 'error');
                setInputStatus(passwordInput, 'error');
                return false;
            }

            if (!/[a-z]/.test(password)) {
                showValidation(validationDiv, 'Must contain lowercase letter', 'error');
                setInputStatus(passwordInput, 'error');
                return false;
            }

            if (!/[0-9]/.test(password)) {
                showValidation(validationDiv, 'Must contain number', 'error');
                setInputStatus(passwordInput, 'error');
                return false;
            }

            if (!/[@$!%*?&]/.test(password)) {
                showValidation(validationDiv, 'Must contain special character (@$!%*?&)', 'error');
                setInputStatus(passwordInput, 'error');
                return false;
            }

            if (/\s/.test(password)) {
                showValidation(validationDiv, 'Cannot contain spaces', 'error');
                setInputStatus(passwordInput, 'error');
                return false;
            }

            showValidation(validationDiv, 'Password is strong', 'success');
            setInputStatus(passwordInput, 'success');
            return true;
        }

        function validateConfirmPassword() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            const validationDiv = document.getElementById('confirm-validation');
            
            if (!confirm) {
                showValidation(validationDiv, 'Please confirm password', 'error');
                setInputStatus(confirmInput, 'error');
                return false;
            }

            if (password !== confirm) {
                showValidation(validationDiv, 'Passwords do not match', 'error');
                setInputStatus(confirmInput, 'error');
                return false;
            }

            showValidation(validationDiv, 'Passwords match', 'success');
            setInputStatus(confirmInput, 'success');
            return true;
        }

        // AJAX functions
        async function checkEmailExists(email) {
            const formData = new FormData();
            formData.append('ajax_check', 'email');
            formData.append('email', email);

            try {
                const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.text();
                return result === 'exists';
            } catch (error) {
                console.error('Error checking email:', error);
                throw error;
            }
        }

        async function checkNicknameExists(nickname) {
            const formData = new FormData();
            formData.append('ajax_check', 'nickname');
            formData.append('nickname', nickname);

            try {
                const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.text();
                return result === 'exists';
            } catch (error) {
                console.error('Error checking nickname:', error);
                throw error;
            }
        }

        // Utility functions
        function showValidation(container, message, type) {
            container.innerHTML = '';
            container.className = 'validation-message';
            
            const icon = type === 'success' ? '✓' : 
                        type === 'error' ? '✗' : 
                        type === 'loading' ? '⟳' : '';
            
            const color = type === 'success' ? '#2ed573' : 
                         type === 'error' ? '#ff4757' : 
                         type === 'loading' ? '#ffa502' : '#ffffff';
            
            container.innerHTML = `<span style="color: ${color}; font-size: 0.8rem;">${icon} ${message}</span>`;
        }

        function setInputStatus(input, status) {
            input.classList.remove('input-error', 'input-success');
            if (status === 'error') {
                input.classList.add('input-error');
            } else if (status === 'success') {
                input.classList.add('input-success');
            }
        }

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function setupPasswordVisibility() {
            const passwordInputs = [
                { id: 'signup-password', eyeId: 'signup-password-eye' },
                { id: 'signup-confirm', eyeId: 'signup-confirm-eye' },
                { id: 'login-password', eyeId: 'login-password-eye' }
            ];
            
            passwordInputs.forEach(input => {
                const field = document.getElementById(input.id);
                if (!field) return;
                
                const container = field.parentElement;
                container.style.position = 'relative';
                
                const eye = document.createElement('span');
                eye.id = input.eyeId;
                eye.innerHTML = '👁️';
                eye.className = 'password-toggle';
                
                container.appendChild(eye);
                
                eye.addEventListener('click', () => {
                    if (field.type === 'password') {
                        field.type = 'text';
                        eye.innerHTML = '👁️';
                    } else {
                        field.type = 'password';
                        eye.innerHTML = '👁️';
                    }
                });
            });
        }

        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            document.querySelectorAll('.validation-message').forEach(el => el.remove());
            document.querySelectorAll('input').forEach(input => {
                input.classList.remove('input-error', 'input-success');
            });
        }
    });
    </script>

    <?php
    // Handle AJAX requests for real-time validation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_check'])) {
        $type = $_POST['ajax_check'] ?? '';
        $value = $_POST[$type] ?? '';
        
        if ($type === 'email' && $value) {
            echo $user->emailExists($value) ? 'exists' : 'available';
            exit;
        } elseif ($type === 'nickname' && $value) {
            echo $user->nicknameExists($value) ? 'exists' : 'available';
            exit;
        }
        
        echo 'invalid';
        exit;
    }
    ?>
</body>
</html>