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


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Political Science Games</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Open+Sans:wght@400;600&display=swap');
        
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: var(--light);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        /* Navigation Buttons */
        .nav-buttons {
            position: fixed;
            top: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }
        
        .nav-button {
            background: linear-gradient(45deg, #4cc9f0, #4895ef);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            font-size: 0.9rem;
        }
        
        .nav-button:hover {
            background: linear-gradient(45deg, #4895ef, #4361ee);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
            transform: scale(1.05);
        }
        
        .back-button {
            background: linear-gradient(45deg, #7209b7, #3a0ca3);
        }
        
        .back-button:hover {
            background: linear-gradient(45deg, #3a0ca3, #4361ee);
        }
        
        /* User Info */
        .user-info {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 100;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #4cc9f0;
        }
        
        .username {
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }
        
        /* Progress Bar */
        .progress-info {
            position: fixed;
            top: 70px;
            right: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            z-index: 100;
            text-align: center;
            min-width: 150px;
        }
        
        .progress-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 5px;
        }
        
        .progress-value {
            font-size: 1rem;
            font-weight: 600;
            color: #4cc9f0;
        }
        
        /* Animated Background */
        .moving-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .bg-element {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.15;
            animation: float 15s infinite linear;
        }
        
        .element-1 {
            width: 300px;
            height: 300px;
            background: var(--accent);
            top: 10%;
            left: 5%;
            animation-duration: 25s;
            animation-delay: 0s;
        }
        
        .element-2 {
            width: 400px;
            height: 400px;
            background: var(--primary);
            bottom: 5%;
            right: 10%;
            animation-duration: 30s;
            animation-delay: 3s;
        }
        
        .element-3 {
            width: 250px;
            height: 250px;
            background: #4cc9f0;
            top: 50%;
            left: 20%;
            animation-duration: 20s;
            animation-delay: 5s;
        }
        
        .element-4 {
            width: 350px;
            height: 350px;
            background: #7209b7;
            bottom: 15%;
            left: 30%;
            animation-duration: 35s;
            animation-delay: 7s;
        }
        
        .element-5 {
            width: 200px;
            height: 200px;
            background: #f8961e;
            top: 30%;
            right: 20%;
            animation-duration: 22s;
            animation-delay: 2s;
        }
        
        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(50px, 80px) rotate(90deg);
            }
            50% {
                transform: translate(100px, 0) rotate(180deg);
            }
            75% {
                transform: translate(50px, -80px) rotate(270deg);
            }
            100% {
                transform: translate(0, 0) rotate(360deg);
            }
        }
        
        /* Enhanced Floating particles - bigger and shinier */
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: particleMove linear infinite;
            box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.5);
        }
        
        @keyframes particleMove {
            to {
                transform: translateY(-100vh);
            }
        }
        
        /* Title Section - Fixed */
        .title-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .title {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 700;
            background: linear-gradient(90deg, #f5af19, #f12711);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .title::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 4px;
            bottom: -10px;
            left: 0;
            background: linear-gradient(90deg, #f5af19, #f12711);
        }
        
        .subtitle {
            font-size: clamp(1rem, 3vw, 1.2rem);
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Games Grid */
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1s 0.5s forwards;
        }
        
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .game-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }
        
        .game-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .game-card-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            height: 100%;
        }
        
        .game-icon {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.3));
            transition: transform 0.3s ease;
        }
        
        .game-card:hover .game-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .game-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .game-desc {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
        }
        
        .play-btn {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            margin-top: auto;
        }
        
        .game-card:hover .play-btn {
            background: linear-gradient(45deg, var(--accent), #b5179e);
            box-shadow: 0 6px 20px rgba(247, 37, 133, 0.4);
            transform: translateY(-3px);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .games-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
            
            .game-card-content {
                padding: 1.5rem;
            }
            
            .nav-buttons {
                flex-direction: column;
                top: 10px;
                left: 10px;
            }
            
            .nav-button {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            
            .user-info {
                top: 10px;
                right: 10px;
                padding: 6px 12px;
            }
            
            .progress-info {
                top: 60px;
                right: 10px;
                min-width: 120px;
            }
        }
        
        @media (max-width: 480px) {
            .games-grid {
                grid-template-columns: 1fr;
                width: 85%;
            }
            
            .title {
                font-size: 2.2rem;
                margin-top: 80px;
            }
            
            .bg-element {
                filter: blur(40px);
            }
            
            .user-info .username {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Buttons -->
    <div class="nav-buttons">
        <button class="nav-button back-button" onclick="window.location.href='home.php'">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <button class="nav-button" onclick="window.location.href='read.php'">
            <i class="fas fa-book"></i> Read-o-learn
        </button>
    </div>
    
    <!-- User Info -->
    <div class="user-info">
        <img src="<?php echo htmlspecialchars($current_user['avatar_url'] ?? 'https://via.placeholder.com/150/00dbde/ffffff?text=User'); ?>" 
             alt="User Avatar" class="user-avatar"
             onerror="this.src='https://via.placeholder.com/150/00dbde/ffffff?text=User'">
        <span class="username"><?php echo htmlspecialchars($current_user['nickname'] ?? $current_user['username'] ?? 'User'); ?></span>
    </div>
    

   
    <!-- Animated moving background -->
    <div class="moving-bg">
        <div class="bg-element element-1"></div>
        <div class="bg-element element-2"></div>
        <div class="bg-element element-3"></div>
        <div class="bg-element element-4"></div>
        <div class="bg-element element-5"></div>
    </div>
    
    <!-- Title section -->
    <div class="title-container">
        <h1 class="title">Political Science</h1>
        <p class="subtitle">Interactive Learning Games</p>
    </div>
    
    <!-- Games grid -->
    <div class="games-grid">
        <!-- Memory Game -->
        <div class="game-card" onclick="startGame('memory')">
            <div class="game-card-content">
                <img src="other img/memorygame.png" alt="Memory Game" class="game-icon" onerror="this.src='https://via.placeholder.com/80/4361ee/ffffff?text=MG'">
                <h3 class="game-title">Memory Game</h3>
                <p class="game-desc">Match political concepts and terms in this challenging memory game</p>
                <button class="play-btn">Play Now</button>
            </div>
        </div>
        
        <!-- Wheel Climber -->
        <div class="game-card" onclick="startGame('wheel')">
            <div class="game-card-content">
                <img src="other img/wheelclimber.png" alt="Wheel Climber" class="game-icon" onerror="this.src='https://via.placeholder.com/80/3a0ca3/ffffff?text=WC'">
                <h3 class="game-title">Wheel Climber</h3>
                <p class="game-desc">Test your knowledge climbing the political wheel of fortune</p>
                <button class="play-btn">Play Now</button>
            </div>
        </div>
        
        <!-- Word Puzzle -->
        <div class="game-card" onclick="startGame('puzzle')">
            <div class="game-card-content">
                <img src="other img/wordpuzzle.png" alt="Word Puzzle" class="game-icon" onerror="this.src='https://via.placeholder.com/80/f72585/ffffff?text=WP'">
                <h3 class="game-title">Word Puzzle</h3>
                <p class="game-desc">Solve political science puzzles and expand your vocabulary</p>
                <button class="play-btn">Play Now</button>
            </div>
        </div>
        
        <!-- Snake & Ladder -->
        <div class="game-card" onclick="startGame('ladder')">
            <div class="game-card-content">
                <img src="other img/snakeladder.jpg" alt="Snake & Ladder" class="game-icon" onerror="this.src='https://via.placeholder.com/80/4cc9f0/ffffff?text=SL'">
                <h3 class="game-title">Snake & Ladder</h3>
                <p class="game-desc">Classic game with political twists and learning opportunities</p>
                <button class="play-btn">Play Now</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <script>
        // Create enhanced floating particles (bigger and shinier)
        function createParticles() {
            const particlesContainer = document.querySelector('.moving-bg');
            const particleCount = window.innerWidth < 768 ? 20 : 40;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random properties - larger and brighter
                const size = Math.random() * 5 + 2; // Bigger particles (2-7px)
                const posX = Math.random() * window.innerWidth;
                const duration = Math.random() * 15 + 10;
                const delay = Math.random() * 5;
                const opacity = Math.random() * 0.7 + 0.3; // Brighter
                
                // Apply styles
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}px`;
                particle.style.bottom = `-10px`;
                particle.style.opacity = opacity;
                particle.style.animationDuration = `${duration}s`;
                particle.style.animationDelay = `${delay}s`;
                
                // Add twinkle effect
                particle.style.boxShadow = `0 0 ${size*2}px ${size/2}px rgba(255, 255, 255, ${opacity*0.7})`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        function startGame(gameType) {
    // Add click animation
    const card = event.currentTarget;
    card.style.transform = 'scale(0.95)';
    
    // Show loading state
    const playBtn = card.querySelector('.play-btn');
    const originalText = playBtn.textContent;
    playBtn.textContent = 'Loading...';
    playBtn.disabled = true;
    
    // Determine game URL
    let gameUrl;
    switch(gameType) {
        case 'memory':
            gameUrl = 'memory.php';
            break;
        case 'wheel':
            gameUrl = 'wheel.php';
            break;
        case 'puzzle':
            gameUrl = 'puzzle.php';
            break;
        case 'ladder':
            gameUrl = 'ladder.php';
            break;
        default:
            gameUrl = 'memory.php';
    }
    
    // Track game start using fetch (asynchronously)
    const formData = new FormData();
    formData.append('game_type', gameType);
    formData.append('subject', 'political_science');
    
    fetch('track_game_start.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // We don't need the response, but we can check if the request was sent
        console.log('Game start tracked');
    })
    .catch(error => {
        console.error('Error tracking game start:', error);
    });
    
    // Navigate to game immediately without waiting for tracking
    window.location.href = gameUrl;
}
        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();
            
            // Add click effect to game cards
            const cards = document.querySelectorAll('.game-card');
            cards.forEach(card => {
                card.addEventListener('click', (e) => {
                    // Prevent multiple clicks
                    if (e.target.classList.contains('play-btn') && e.target.disabled) {
                        return;
                    }
                    
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.style.transform = '';
                    }, 200);
                });
            });
        });
        
        // Adjust particles on resize
        window.addEventListener('resize', () => {
            const particles = document.querySelectorAll('.particle');
            particles.forEach(p => p.remove());
            createParticles();
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.location.href = 'home.php';
            }
        });
    </script>
</body>
</html>