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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_standard = $_POST['selected_standard'] ?? '';
    
    if (!empty($selected_standard)) {
        // Update user's standard in database
        $query = "UPDATE users SET current_level = :standard, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':standard', $selected_standard);
        $stmt->bindParam(':user_id', $_SESSION['current_user']['id']);
        
        if ($stmt->execute()) {
            // Update session data
            $_SESSION['current_user']['current_level'] = $selected_standard;
            $_SESSION['user_standard'] = $selected_standard;
            
            // Redirect to home page
            header('Location: home.php');
            exit();
        }
    }
}

// Get user's avatar for the logo
$user_avatar = $_SESSION['current_user']['avatar_url'] ?? 'logo.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Standard - Learniverse</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .logo {
            width: 120px;
            height: 120px;
            margin-bottom: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 219, 222, 0.3);
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.1);
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
            max-width: 500px;
        }

        .standards-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            max-width: 600px;
            margin: 0 auto;
        }

        .standard-option {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .standard-option:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .standard-option.selected {
            background: rgba(0, 219, 222, 0.2);
            border: 1px solid #00dbde;
            box-shadow: 0 10px 25px rgba(0, 219, 222, 0.3);
        }

        .standard-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            background: linear-gradient(90deg, #00dbde, #fc00ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .standard-label {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .btn {
            margin-top: 40px;
            padding: 12px 30px;
            background: linear-gradient(45deg, #00dbde, #fc00ff);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 219, 222, 0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(252, 0, 255, 0.3);
        }

        .btn:disabled {
            background: rgba(255, 255, 255, 0.2);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .error-message {
            color: #ff4757;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        @media (max-width: 600px) {
            .standards-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="logo">
        <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Your Avatar">
    </div>

    <h1>Select Your Standard</h1>
    <p>Choose your class grade to begin your learning journey</p>

    <form method="POST" action="" id="standard-form">
        <input type="hidden" name="selected_standard" id="selected-standard-input" value="">
        
        <div class="standards-container">
            <div class="standard-option" data-standard="5">
                <div class="standard-number">5</div>
                <div class="standard-label">Fifth Standard</div>
            </div>
            <div class="standard-option" data-standard="6">
                <div class="standard-number">6</div>
                <div class="standard-label">Sixth Standard</div>
            </div>
            <div class="standard-option" data-standard="7">
                <div class="standard-number">7</div>
                <div class="standard-label">Seventh Standard</div>
            </div>
            <div class="standard-option" data-standard="8">
                <div class="standard-number">8</div>
                <div class="standard-label">Eighth Standard</div>
            </div>
            <div class="standard-option" data-standard="9">
                <div class="standard-number">9</div>
                <div class="standard-label">Ninth Standard</div>
            </div>
            <div class="standard-option" data-standard="10">
                <div class="standard-number">10</div>
                <div class="standard-label">Tenth Standard</div>
            </div>
        </div>

        <button type="submit" class="btn" id="continue-btn" disabled>Continue</button>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedStandard = null;
            const form = document.getElementById('standard-form');
            const hiddenInput = document.getElementById('selected-standard-input');
            const continueBtn = document.getElementById('continue-btn');
            
            // Select standard option
            document.querySelectorAll('.standard-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    document.querySelectorAll('.standard-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    selectedStandard = this.getAttribute('data-standard');
                    hiddenInput.value = selectedStandard;
                    
                    // Update continue button
                    continueBtn.textContent = `Continue to ${this.querySelector('.standard-label').textContent}`;
                    continueBtn.disabled = false;
                });
            });
            
            // Form submission handling
            form.addEventListener('submit', function(e) {
                if (!selectedStandard) {
                    e.preventDefault();
                    alert('Please select a standard first');
                    return;
                }
                
                // Show loading state
                continueBtn.disabled = true;
                continueBtn.textContent = 'Loading...';
            });
            
            // Auto-select first option (5th standard) if no previous selection
            <?php if (!isset($_SESSION['current_user']['current_level'])): ?>
                document.querySelector('.standard-option[data-standard="5"]').click();
            <?php else: ?>
                // Auto-select user's current level if exists
                const currentLevel = <?php echo $_SESSION['current_user']['current_level'] ?? 5; ?>;
                const existingOption = document.querySelector(`.standard-option[data-standard="${currentLevel}"]`);
                if (existingOption) {
                    existingOption.click();
                } else {
                    document.querySelector('.standard-option[data-standard="5"]').click();
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>