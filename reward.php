<?php
// Start session and include database configuration
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "roshni@2006";  
$dbname = "learniverse";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $conn = null;
    error_log("Database connection failed: " . $conn->connect_error);
}

// Get current logged-in user from session (using your actual session structure)
if (!isset($_SESSION['current_user'])) {
    // Redirect to login if not authenticated
    header('Location: index.php');
    exit();
}

$current_user = $_SESSION['current_user'];
$user_id = $current_user['id']; // Get the actual user ID from session

// Handle reward purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_reward'])) {
    $response = ['success' => false, 'message' => 'Database not connected'];
    
    if ($conn) {
        $reward_id = intval($_POST['reward_id']);
        $reward_name = $conn->real_escape_string($_POST['reward_name']);
        $reward_price = intval($_POST['reward_price']);
        
        // Check if user already owns this reward
        $check_sql = "SELECT id FROM user_rewards WHERE user_id = ? AND reward_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $reward_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $response = ['success' => false, 'message' => 'You already own this reward!'];
        } else {
            // Get user's current coins
            $coins_sql = "SELECT coins FROM users WHERE id = ?";
            $coins_stmt = $conn->prepare($coins_sql);
            $coins_stmt->bind_param("i", $user_id);
            $coins_stmt->execute();
            $coins_result = $coins_stmt->get_result();
            
            if ($coins_result->num_rows > 0) {
                $user_data = $coins_result->fetch_assoc();
                $user_coins = $user_data['coins'];
                
                if ($user_coins >= $reward_price) {
                    // Start transaction
                    $conn->begin_transaction();
                    
                    try {
                        // Deduct coins from user
                        $update_coins_sql = "UPDATE users SET coins = coins - ? WHERE id = ?";
                        $update_stmt = $conn->prepare($update_coins_sql);
                        $update_stmt->bind_param("ii", $reward_price, $user_id);
                        $update_stmt->execute();
                        
                        // Add reward to user's collection
                        $insert_sql = "INSERT INTO user_rewards (user_id, reward_id, reward_name, reward_price, purchased_at) 
                                     VALUES (?, ?, ?, ?, NOW())";
                        $insert_stmt = $conn->prepare($insert_sql);
                        $insert_stmt->bind_param("iisi", $user_id, $reward_id, $reward_name, $reward_price);
                        $insert_stmt->execute();
                        
                        // Commit transaction
                        $conn->commit();
                        
                        // Get updated coins
                        $new_coins_sql = "SELECT coins FROM users WHERE id = ?";
                        $new_coins_stmt = $conn->prepare($new_coins_sql);
                        $new_coins_stmt->bind_param("i", $user_id);
                        $new_coins_stmt->execute();
                        $new_coins_result = $new_coins_stmt->get_result();
                        $new_user_data = $new_coins_result->fetch_assoc();
                        
                        $response = [
                            'success' => true, 
                            'message' => 'Reward successfully purchased!', 
                            'new_coins' => $new_user_data['coins']
                        ];
                        
                        $new_coins_stmt->close();
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $conn->rollback();
                        $response = ['success' => false, 'message' => 'Error processing purchase: ' . $e->getMessage()];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Not enough coins! Complete more lessons to earn coins.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'User not found!'];
            }
            $coins_stmt->close();
        }
        $check_stmt->close();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get user's current coins and purchased rewards
$user_coins = 0; // Default value
$purchased_rewards = [];

if ($conn) {
    // Get user coins
    $coins_sql = "SELECT coins FROM users WHERE id = ?";
    $coins_stmt = $conn->prepare($coins_sql);
    $coins_stmt->bind_param("i", $user_id);
    $coins_stmt->execute();
    $coins_result = $coins_stmt->get_result();
    
    if ($coins_result->num_rows > 0) {
        $user_data = $coins_result->fetch_assoc();
        $user_coins = $user_data['coins'];
    } else {
        // User not found in database
        error_log("User not found in database: " . $user_id);
    }
    $coins_stmt->close();
    
    // Get purchased rewards for this specific user
    $rewards_sql = "SELECT reward_id FROM user_rewards WHERE user_id = ?";
    $rewards_stmt = $conn->prepare($rewards_sql);
    $rewards_stmt->bind_param("i", $user_id);
    $rewards_stmt->execute();
    $rewards_result = $rewards_stmt->get_result();
    
    while ($row = $rewards_result->fetch_assoc()) {
        $purchased_rewards[] = $row['reward_id'];
    }
    $rewards_stmt->close();
}

// Define rewards data
$rewards = [
    1 => [
        'name' => 'History Explorer',
        'description' => 'Access premium historical documents and 3D artifact models',
        'price' => 300
    ],
    2 => [
        'name' => 'Geography Master',
        'description' => 'Interactive world maps and terrain visualization tools',
        'price' => 250
    ],
    3 => [
        'name' => 'Math Wizard',
        'description' => 'Advanced calculator and equation solving tools',
        'price' => 350
    ],
    4 => [
        'name' => 'Science Lab',
        'description' => 'Virtual experiments and 3D molecular models',
        'price' => 450
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learniverse Rewards</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
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
        
        .reward-container {
            width: 100%;
            max-width: 800px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-top: 30px;
        }
        
        .reward-header {
            margin-bottom: 2rem;
            position: relative;
        }
        
        .reward-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .reward-header p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            font-style: italic;
        }
        
        .user-welcome {
            background: rgba(0, 219, 222, 0.1);
            padding: 0.8rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid rgba(0, 219, 222, 0.3);
        }
        
        .user-welcome h3 {
            color: #00dbde;
            margin-bottom: 0.3rem;
        }
        
        .user-welcome p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .reward-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .reward-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .reward-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(252, 0, 255, 0.3);
        }
        
        .reward-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #00dbde;
        }
        
        .reward-card p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
        }
        
        .reward-price {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: gold;
            font-weight: bold;
        }
        
        .reward-price i {
            font-size: 1.2rem;
        }
        
        .claim-btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .claim-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(252, 0, 255, 0.5);
        }
        
        .claim-btn.claimed {
            background: rgba(0, 219, 222, 0.2);
            color: #00dbde;
            border: 2px solid #00dbde;
            cursor: not-allowed;
        }
        
        .claim-btn.claimed:hover {
            transform: none;
            box-shadow: none;
        }
        
        .claim-btn:disabled {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            cursor: not-allowed;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .claim-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .reward-tip {
            background: rgba(0, 219, 222, 0.1);
            border-left: 4px solid #fc00ff;
            padding: 1rem;
            margin-top: 2rem;
            text-align: left;
            border-radius: 0 5px 5px 0;
        }
        
        .reward-tip h4 {
            color: #fc00ff;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .reward-tip p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .coins-display {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .coins-display i {
            color: gold;
        }
        
        .reward-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .reward-content {
                grid-template-columns: 1fr;
            }
            
            .reward-header h1 {
                font-size: 2rem;
            }
        }

        /* Status message */
        .status-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
            font-weight: bold;
        }
        .status-success {
            background: #4CAF50;
            color: white;
        }
        .status-error {
            background: #f44336;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Back Button -->
    <a href="home.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <!-- Status Message -->
    <div id="statusMessage" class="status-message"></div>
    
    <div class="reward-container">
        <div class="coins-display">
            <i class="fas fa-coins"></i>
            <span id="user-coins"><?php echo $user_coins; ?></span>
        </div>
        
        <div class="reward-header">
            <h1>Rewards</h1>
            <p>Unlock exclusive educational content and tools</p>
            
            <!-- User Welcome Section -->
            <div class="user-welcome">
                <h3>Welcome, <?php echo htmlspecialchars($current_user['nickname'] ?? 'Learner'); ?>!</h3>
                <p>Your current balance: <strong><?php echo $user_coins; ?> coins</strong></p>
            </div>
        </div>
        
        <div class="reward-content">
            <?php foreach ($rewards as $reward_id => $reward): ?>
                <?php 
                $is_claimed = in_array($reward_id, $purchased_rewards);
                $can_afford = $user_coins >= $reward['price'];
                ?>
                
                <div class="reward-card">
                    <?php if ($is_claimed): ?>
                        <div class="reward-badge">
                            <i class="fas fa-check"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h3><?php echo htmlspecialchars($reward['name']); ?></h3>
                    <p><?php echo htmlspecialchars($reward['description']); ?></p>
                    <div class="reward-price">
                        <i class="fas fa-coins"></i>
                        <span><?php echo $reward['price']; ?></span>
                    </div>
                    
                    <?php if ($is_claimed): ?>
                        <!-- Already Claimed - User gets the benefit but can't claim again -->
                        <button class="claim-btn claimed" disabled>
                            <i class="fas fa-check-circle"></i> Already Claimed
                        </button>
                    <?php elseif (!$can_afford): ?>
                        <!-- Can't Afford -->
                        <button class="claim-btn" disabled>
                            <i class="fas fa-lock"></i> Need <?php echo $reward['price'] - $user_coins; ?> More Coins
                        </button>
                    <?php else: ?>
                        <!-- Can Claim -->
                        <button class="claim-btn claim-reward-btn"
                                data-reward-id="<?php echo $reward_id; ?>"
                                data-reward-name="<?php echo htmlspecialchars($reward['name']); ?>"
                                data-reward-price="<?php echo $reward['price']; ?>">
                            <i class="fas fa-gift"></i> Claim Reward
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="reward-tip">
            <h4>HOW IT WORKS:</h4>
            <p>• Click "Claim Reward" to purchase with your coins<br>
               • Once claimed, you get the benefit permanently<br>
               • Each reward can only be claimed once per user<br>
               • Complete lessons and games to earn more coins</p>
        </div>
    </div>
    
    <script>
        // Show status message
        function showStatus(message, isSuccess) {
            const statusEl = document.getElementById("statusMessage");
            statusEl.textContent = message;
            statusEl.className = `status-message ${isSuccess ? 'status-success' : 'status-error'}`;
            statusEl.style.display = 'block';
            
            setTimeout(() => {
                statusEl.style.display = 'none';
            }, 3000);
        }
        
        // Claim button functionality
        document.querySelectorAll('.claim-reward-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const rewardId = this.getAttribute('data-reward-id');
                const rewardName = this.getAttribute('data-reward-name');
                const rewardPrice = parseInt(this.getAttribute('data-reward-price'));
                const userCoins = parseInt(document.getElementById('user-coins').textContent);
                
                // Double-check if user can afford (in case of multiple tabs)
                if (userCoins < rewardPrice) {
                    showStatus('Not enough coins! Complete more lessons to earn coins.', false);
                    return;
                }
                
                // Disable button immediately to prevent double-clicks
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Send purchase request to server
                const formData = new FormData();
                formData.append('purchase_reward', 'true');
                formData.append('reward_id', rewardId);
                formData.append('reward_name', rewardName);
                formData.append('reward_price', rewardPrice);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Update UI for successful purchase
                        document.getElementById('user-coins').textContent = data.new_coins;
                        
                        // Change button to "Already Claimed"
                        this.classList.remove('claim-reward-btn');
                        this.classList.add('claimed');
                        this.disabled = true;
                        this.innerHTML = '<i class="fas fa-check-circle"></i> Already Claimed';
                        
                        // Add check badge
                        const card = this.closest('.reward-card');
                        const badge = document.createElement('div');
                        badge.className = 'reward-badge';
                        badge.innerHTML = '<i class="fas fa-check"></i>';
                        card.appendChild(badge);
                        
                        showStatus('🎉 ' + data.message + ' You now have access to: ' + rewardName, true);
                    } else {
                        // Re-enable button on failure
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-gift"></i> Claim Reward';
                        showStatus(data.message, false);
                    }
                })
                .catch(error => {
                    // Re-enable button on error
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-gift"></i> Claim Reward';
                    showStatus('Network error. Please try again.', false);
                    console.error('Error:', error);
                });
            });
        });
        
        // Prevent double-clicks on all claim buttons
        document.querySelectorAll('.claim-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.disabled || this.classList.contains('claimed')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });
    </script>
</body>
</html>
<?php
// Close database connection if it exists
if ($conn) {
    $conn->close();
}
?>