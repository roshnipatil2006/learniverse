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

// Get filter type from URL
$filter_type = $_GET['filter'] ?? 'total_score';

// Define valid filter types
$valid_filters = ['total_score', 'level', 'coins', 'performance', 'streak'];
if (!in_array($filter_type, $valid_filters)) {
    $filter_type = 'total_score';
}

// Function to calculate performance score based on multiple factors
function calculatePerformanceScore($user_data, $db) {
    // Get user's total game score
    $total_score = 0;
    try {
        $score_query = "SELECT COALESCE(SUM(score), 0) as total_score FROM game_sessions WHERE user_id = :user_id";
        $score_stmt = $db->prepare($score_query);
        $score_stmt->bindValue(':user_id', $user_data['id']);
        $score_stmt->execute();
        $score_data = $score_stmt->fetch(PDO::FETCH_ASSOC);
        $total_score = $score_data['total_score'] ?? 0;
    } catch (PDOException $e) {
        error_log("Score calculation error: " . $e->getMessage());
    }
    
    // Normalize factors to create a balanced performance score
    $level_score = ($user_data['current_level'] ?? 1) * 100;
    $coins_score = ($user_data['coins'] ?? 0) * 0.5;
    $achievement_score = ($user_data['achievement_count'] ?? 0) * 50;
    $streak_score = ($user_data['current_streak'] ?? 0) * 20;
    
    // Calculate composite performance score with weighted factors
    $composite_score = (
        ($total_score * 0.4) +           // 40% weight to total score
        ($level_score * 0.3) +           // 30% weight to level
        ($coins_score * 0.1) +           // 10% weight to coins
        ($achievement_score * 0.1) +     // 10% weight to achievements
        ($streak_score * 0.1)            // 10% weight to streak
    );
    
    // Normalize to 0-100 scale
    $max_possible_score = 10000;
    $performance_percentage = min(100, max(0, ($composite_score / $max_possible_score) * 100));
    
    return round($performance_percentage, 1);
}

// Build ORDER BY clause based on filter
$order_by = '';
switch ($filter_type) {
    case 'level':
        $order_by = 'u.current_level DESC, u.coins DESC, (SELECT COALESCE(SUM(score), 0) FROM game_sessions WHERE user_id = u.id) DESC';
        break;
    case 'coins':
        $order_by = 'u.coins DESC, u.current_level DESC, (SELECT COALESCE(SUM(score), 0) FROM game_sessions WHERE user_id = u.id) DESC';
        break;
    case 'performance':
        // We'll handle performance sorting in PHP after calculating scores
        $order_by = 'u.current_level DESC, u.coins DESC';
        break;
    case 'streak':
        $order_by = 'u.current_streak DESC, u.current_level DESC, u.coins DESC';
        break;
    default: // total_score
        $order_by = '(SELECT COALESCE(SUM(score), 0) FROM game_sessions WHERE user_id = u.id) DESC, u.current_level DESC, u.coins DESC';
}

// Fetch leaderboard data
$leaderboard_data = [];
$query = "
    SELECT 
        u.id,
        u.nickname,
        u.avatar_url,
        u.current_level,
        u.coins,
        u.current_streak,
        (SELECT COUNT(*) FROM user_achievements ua WHERE ua.user_id = u.id) as achievement_count,
        COALESCE((SELECT SUM(score) FROM game_sessions gs WHERE gs.user_id = u.id), 0) as total_game_score
    FROM users u 
    WHERE u.is_active = true 
    ORDER BY $order_by
    LIMIT 50
";

try {
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate performance score for each user
        foreach ($raw_data as $user) {
            $user['performance_score'] = calculatePerformanceScore($user, $db);
            $leaderboard_data[] = $user;
        }
        
        // If ordering by performance, re-sort the data
        if ($filter_type === 'performance') {
            usort($leaderboard_data, function($a, $b) {
                return $b['performance_score'] <=> $a['performance_score'];
            });
        }
    }
} catch (PDOException $e) {
    error_log("Leaderboard query error: " . $e->getMessage());
    $leaderboard_data = [];
}

// Calculate current user's performance score
$current_user_performance = calculatePerformanceScore($current_user, $db);

// Get current user's rank based on the current filter
$current_user_rank = 0;
$current_user_found = false;

// First, check if current user is in the top 50
foreach ($leaderboard_data as $index => $user) {
    if ($user['id'] == $current_user['id']) {
        $current_user_rank = $index + 1;
        $current_user_found = true;
        break;
    }
}

// If current user not in top 50, calculate their rank
if (!$current_user_found) {
    $rank_query = "
        SELECT COUNT(*) + 1 as user_rank
        FROM users u 
        WHERE u.is_active = true 
        AND (
    ";
    
    // Build ranking condition based on filter type
    switch ($filter_type) {
        case 'level':
            $rank_query .= "
                u.current_level > :user_value
                OR (u.current_level = :user_value AND u.coins > :coins_value)
                OR (u.current_level = :user_value AND u.coins = :coins_value AND (SELECT COALESCE(SUM(score), 0) FROM game_sessions WHERE user_id = u.id) > :score_value)
            ";
            break;
        case 'coins':
            $rank_query .= "
                u.coins > :user_value
                OR (u.coins = :user_value AND u.current_level > :level_value)
                OR (u.coins = :user_value AND u.current_level = :level_value AND (SELECT COALESCE(SUM(score), 0) FROM game_sessions WHERE user_id = u.id) > :score_value)
            ";
            break;
        case 'performance':
            // For performance, we need to calculate for all users - this is complex
            // We'll use a simpler approach: count users with higher composite score
            $rank_query = "
                SELECT COUNT(*) + 1 as user_rank
                FROM users u 
                WHERE u.is_active = true 
                AND (
                    (SELECT COALESCE(SUM(score), 0) FROM game_sessions WHERE user_id = u.id) > :total_score
                    OR (
                        (SELECT COALESCE(SUM(score), 0) FROM game_sessions WHERE user_id = u.id) = :total_score
                        AND u.current_level > :current_level
                    )
                    OR (
                        (SELECT COALESCE(SUM(score), 0) FROM game_sessions WHERE user_id = u.id) = :total_score
                        AND u.current_level = :current_level
                        AND u.coins > :coins
                    )
                )
            ";
            break;
        case 'streak':
            $rank_query .= "
                u.current_streak > :user_value
                OR (u.current_streak = :user_value AND u.current_level > :level_value)
                OR (u.current_streak = :user_value AND u.current_level = :level_value AND u.coins > :coins_value)
            ";
            break;
        default: // total_score
            $rank_query .= "
                COALESCE((SELECT SUM(score) FROM game_sessions gs WHERE gs.user_id = u.id), 0) > :user_value
                OR (
                    COALESCE((SELECT SUM(score) FROM game_sessions gs WHERE gs.user_id = u.id), 0) = :user_value
                    AND u.current_level > :level_value
                )
            ";
    }
    
    if ($filter_type !== 'performance') {
        $rank_query .= ")";
    }
    
    try {
        $rank_stmt = $db->prepare($rank_query);
        
        // Bind parameters based on filter type
        switch ($filter_type) {
            case 'level':
                $rank_stmt->bindValue(':user_value', $current_user['current_level'] ?? 1);
                $rank_stmt->bindValue(':coins_value', $current_user['coins'] ?? 0);
                
                // Calculate user's total score for tie-breaking
                $user_total_score = 0;
                $score_query = "SELECT COALESCE(SUM(score), 0) as total_score FROM game_sessions WHERE user_id = :user_id";
                $score_stmt = $db->prepare($score_query);
                $score_stmt->bindValue(':user_id', $current_user['id']);
                $score_stmt->execute();
                $score_data = $score_stmt->fetch(PDO::FETCH_ASSOC);
                $user_total_score = $score_data['total_score'] ?? 0;
                
                $rank_stmt->bindValue(':score_value', $user_total_score);
                break;
                
            case 'coins':
                $rank_stmt->bindValue(':user_value', $current_user['coins'] ?? 0);
                $rank_stmt->bindValue(':level_value', $current_user['current_level'] ?? 1);
                
                // Calculate user's total score for tie-breaking
                $user_total_score = 0;
                $score_query = "SELECT COALESCE(SUM(score), 0) as total_score FROM game_sessions WHERE user_id = :user_id";
                $score_stmt = $db->prepare($score_query);
                $score_stmt->bindValue(':user_id', $current_user['id']);
                $score_stmt->execute();
                $score_data = $score_stmt->fetch(PDO::FETCH_ASSOC);
                $user_total_score = $score_data['total_score'] ?? 0;
                
                $rank_stmt->bindValue(':score_value', $user_total_score);
                break;
                
            case 'performance':
                // For performance ranking, use composite criteria
                $user_total_score = 0;
                $score_query = "SELECT COALESCE(SUM(score), 0) as total_score FROM game_sessions WHERE user_id = :user_id";
                $score_stmt = $db->prepare($score_query);
                $score_stmt->bindValue(':user_id', $current_user['id']);
                $score_stmt->execute();
                $score_data = $score_stmt->fetch(PDO::FETCH_ASSOC);
                $user_total_score = $score_data['total_score'] ?? 0;
                
                $rank_stmt->bindValue(':total_score', $user_total_score);
                $rank_stmt->bindValue(':current_level', $current_user['current_level'] ?? 1);
                $rank_stmt->bindValue(':coins', $current_user['coins'] ?? 0);
                break;
                
            case 'streak':
                $rank_stmt->bindValue(':user_value', $current_user['current_streak'] ?? 0);
                $rank_stmt->bindValue(':level_value', $current_user['current_level'] ?? 1);
                $rank_stmt->bindValue(':coins_value', $current_user['coins'] ?? 0);
                break;
                
            default: // total_score
                $user_total_score = 0;
                $score_query = "SELECT COALESCE(SUM(score), 0) as total_score FROM game_sessions WHERE user_id = :user_id";
                $score_stmt = $db->prepare($score_query);
                $score_stmt->bindValue(':user_id', $current_user['id']);
                $score_stmt->execute();
                $score_data = $score_stmt->fetch(PDO::FETCH_ASSOC);
                $user_total_score = $score_data['total_score'] ?? 0;
                
                $rank_stmt->bindValue(':user_value', $user_total_score);
                $rank_stmt->bindValue(':level_value', $current_user['current_level'] ?? 1);
                break;
        }
        
        $rank_stmt->execute();
        $rank_data = $rank_stmt->fetch(PDO::FETCH_ASSOC);
        $current_user_rank = $rank_data['user_rank'] ?? 'N/A';
        
    } catch (PDOException $e) {
        error_log("Rank calculation error: " . $e->getMessage());
        $current_user_rank = 'N/A';
    }
}

// Calculate current user's total game score for display
$current_user_total_score = 0;
try {
    $score_query = "SELECT COALESCE(SUM(score), 0) as total_score FROM game_sessions WHERE user_id = :user_id";
    $score_stmt = $db->prepare($score_query);
    $score_stmt->bindValue(':user_id', $current_user['id']);
    $score_stmt->execute();
    $score_data = $score_stmt->fetch(PDO::FETCH_ASSOC);
    $current_user_total_score = $score_data['total_score'] ?? 0;
} catch (PDOException $e) {
    error_log("User score calculation error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Learniverse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .nav-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .nav-btn {
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(252, 0, 255, 0.3);
        }
        
        /* Leaderboard Container */
        .leaderboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .leaderboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .leaderboard-title {
            font-size: 3rem;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .leaderboard-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.2rem;
        }
        
        /* Current User Stats */
        .current-user-stats {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .user-rank-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #00dbde;
        }
        
        .rank-badge {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #000;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .user-details {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #00dbde;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Leaderboard Table */
        .leaderboard-table {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table-header {
            display: grid;
            grid-template-columns: 80px 1fr 100px 100px 100px 100px 100px;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: rgba(0, 219, 222, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: bold;
            color: #00dbde;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 80px 1fr 100px 100px 100px 100px 100px;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: background 0.3s ease;
        }
        
        .table-row:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        
        .table-row.current-user {
            background: rgba(0, 219, 222, 0.15);
            border-left: 4px solid #00dbde;
        }
        
        .rank {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .rank-1 { color: #ffd700; }
        .rank-2 { color: #c0c0c0; }
        .rank-3 { color: #cd7f32; }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-name {
            font-weight: 600;
        }
        
        .score-value {
            font-weight: bold;
            color: #00dbde;
        }
        
        .level-value {
            color: #fc00ff;
        }
        
        .coins-value {
            color: #ffd700;
        }
        
        .performance-value {
            color: #4cc9f0;
        }
        
        .streak-value {
            color: #f72585;
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active {
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            color: white;
        }
        
        .filter-tab:hover:not(.active) {
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .table-header,
            .table-row {
                grid-template-columns: 60px 1fr 80px 80px;
                gap: 0.5rem;
                padding: 0.8rem 1rem;
            }
            
            .table-header div:nth-child(5),
            .table-header div:nth-child(6),
            .table-header div:nth-child(7),
            .table-row div:nth-child(5),
            .table-row div:nth-child(6),
            .table-row div:nth-child(7) {
                display: none;
            }
            
            .current-user-stats {
                flex-direction: column;
                text-align: center;
            }
            
            .user-details {
                justify-content: center;
            }
            
            .leaderboard-title {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .table-header,
            .table-row {
                grid-template-columns: 50px 1fr 70px;
            }
            
            .table-header div:nth-child(4),
            .table-row div:nth-child(4) {
                display: none;
            }
            
            .user-info {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
            
            .filter-tabs {
                flex-direction: column;
                align-items: center;
            }
        }
        
        /* Loading Animation */
        .loading {
            text-align: center;
            padding: 2rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 3px solid #00dbde;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-container">
        <div class="logo">Learniverse</div>
        <div class="nav-buttons">
            <a href="home.php" class="nav-btn">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="ps.php" class="nav-btn">
                <i class="fas fa-gamepad"></i> Games
            </a>
        </div>
    </div>
    
    <!-- Leaderboard Container -->
    <div class="leaderboard-container">
        <!-- Header -->
        <div class="leaderboard-header">
            <h1 class="leaderboard-title">Global Leaderboard</h1>
            <p class="leaderboard-subtitle">See how you rank among Learniverse players worldwide</p>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <div class="filter-tab <?php echo $filter_type === 'total_score' ? 'active' : ''; ?>" data-filter="total_score">Total Score</div>
            <div class="filter-tab <?php echo $filter_type === 'level' ? 'active' : ''; ?>" data-filter="level">Level</div>
            <div class="filter-tab <?php echo $filter_type === 'coins' ? 'active' : ''; ?>" data-filter="coins">Coins</div>
            <div class="filter-tab <?php echo $filter_type === 'performance' ? 'active' : ''; ?>" data-filter="performance">Performance</div>
            <div class="filter-tab <?php echo $filter_type === 'streak' ? 'active' : ''; ?>" data-filter="streak">Streak</div>
        </div>
        
        <!-- Current User Stats -->
        <div class="current-user-stats">
            <div class="user-rank-info">
                <img src="<?php echo htmlspecialchars($current_user['avatar_url'] ?? 'https://via.placeholder.com/150/00dbde/ffffff?text=User'); ?>" 
                     alt="Your Avatar" class="user-avatar"
                     onerror="this.src='https://via.placeholder.com/150/00dbde/ffffff?text=User'">
                <div>
                    <h3><?php echo htmlspecialchars($current_user['nickname'] ?? $current_user['username'] ?? 'User'); ?></h3>
                    <div class="rank-badge">Rank #<?php echo $current_user_rank; ?></div>
                </div>
            </div>
            <div class="user-details">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $current_user_total_score; ?></div>
                    <div class="stat-label">Total Score</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $current_user['coins'] ?? 0; ?></div>
                    <div class="stat-label">Coins</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $current_user['current_level'] ?? 1; ?></div>
                    <div class="stat-label">Level</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $current_user_performance; ?>%</div>
                    <div class="stat-label">Performance</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $current_user['current_streak'] ?? 0; ?></div>
                    <div class="stat-label">Day Streak</div>
                </div>
            </div>
        </div>
        
        <!-- Leaderboard Table -->
        <div class="leaderboard-table">
            <!-- Table Header -->
            <div class="table-header">
                <div>Rank</div>
                <div>Player</div>
                <div>Total Score</div>
                <div>Level</div>
                <div>Coins</div>
                <div>Performance</div>
                <div>Streak</div>
            </div>
            
            <!-- Table Rows -->
            <?php if (!empty($leaderboard_data)): ?>
                <?php foreach ($leaderboard_data as $index => $user): ?>
                    <div class="table-row <?php echo $user['id'] == $current_user['id'] ? 'current-user' : ''; ?>">
                        <div class="rank <?php echo $index < 3 ? 'rank-' . ($index + 1) : ''; ?>">
                            <?php if ($index < 3): ?>
                                <i class="fas fa-medal"></i>
                            <?php endif; ?>
                            #<?php echo $index + 1; ?>
                        </div>
                        <div class="user-info">
                            <img src="<?php echo htmlspecialchars($user['avatar_url'] ?? 'https://via.placeholder.com/150/00dbde/ffffff?text=User'); ?>" 
                                 alt="<?php echo htmlspecialchars($user['nickname']); ?>" 
                                 class="user-avatar-small"
                                 onerror="this.src='https://via.placeholder.com/150/00dbde/ffffff?text=User'">
                            <span class="user-name"><?php echo htmlspecialchars($user['nickname']); ?></span>
                        </div>
                        <div class="score-value"><?php echo $user['total_game_score']; ?></div>
                        <div class="level-value"><?php echo $user['current_level']; ?></div>
                        <div class="coins-value"><?php echo $user['coins']; ?></div>
                        <div class="performance-value"><?php echo $user['performance_score']; ?>%</div>
                        <div class="streak-value"><?php echo $user['current_streak']; ?> days</div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-trophy" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>No players yet</h3>
                    <p>Be the first to climb the leaderboard!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterTabs = document.querySelectorAll('.filter-tab');
            
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const filterType = this.getAttribute('data-filter');
                    window.location.href = `leaderboard.php?filter=${filterType}`;
                });
            });
            
            // Auto-refresh leaderboard every 30 seconds
            setInterval(() => {
                window.location.reload();
            }, 30000);
        });
    </script>
</body>
</html>