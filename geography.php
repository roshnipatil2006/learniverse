<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geography-Learniverse</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Open+Sans:wght@400;600&display=swap');
        
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --geo-primary: #4b0082;
            --geo-secondary: #7209b7;
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
            padding: 2rem;
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
            background: linear-gradient(45deg, var(--geo-primary), var(--geo-secondary));
        }
        
        .back-button:hover {
            background: linear-gradient(45deg, var(--geo-secondary), #4361ee);
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
            background: var(--geo-primary);
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
            background: var(--geo-secondary);
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
        
        /* Title Section */
        .title-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 700;
            background: linear-gradient(90deg, #b5179e, #7209b7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        h1::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 4px;
            bottom: -10px;
            left: 0;
            background: linear-gradient(90deg, #b5179e, #7209b7);
        }
        
        /* Games Grid */
        .section-container {
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
        
        .section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-decoration: none;
            color: inherit;
        }
        
        .section:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .section img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
            transition: transform 0.3s ease;
        }
        
        .section:hover img {
            transform: scale(1.05);
        }
        
        .section h2 {
            font-size: 1.3rem;
            font-weight: 600;
            padding: 1.5rem;
            color: white;
            transition: color 0.3s ease;
        }
        
        .section:hover h2 {
            color: #b5179e;
        }
        
        /* Back Button */
        .home-btn {
            background: linear-gradient(45deg, var(--geo-primary), var(--geo-secondary));
            color: white;
            border: none;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(75, 0, 130, 0.3);
            margin-top: 2rem;
            font-size: 1rem;
        }
        
        .home-btn:hover {
            background: linear-gradient(45deg, var(--geo-secondary), #b5179e);
            box-shadow: 0 6px 20px rgba(114, 9, 183, 0.4);
            transform: translateY(-3px);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .section-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
            
            .section h2 {
                padding: 1rem;
                font-size: 1.1rem;
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
        }
        
        @media (max-width: 480px) {
            .section-container {
                grid-template-columns: 1fr;
                width: 85%;
            }
            
            h1 {
                font-size: 2.2rem;
                margin-top: 80px;
            }
            
            .bg-element {
                filter: blur(40px);
            }
            
            .section img {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Buttons -->
    <div class="nav-buttons">
        <button class="nav-button back-button" onclick="window.location.href='home.php'">Back</button>
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
        <h1>Geography</h1>
    </div>
    
    <!-- Games grid -->
    <div class="section-container">
        <?php
        // Define geography games data
        $geographyGames = [
            [
                'link' => 'geo.php',
                'image' => 'geo.jpg',
                'alt' => 'Geography stimulation',
                'title' => 'Geography stimulation'
            ],
            [
                'link' => 'popgame.php',
                'image' => 'popgame.jpg',
                'alt' => 'popgame',
                'title' => 'Geo-objects'
            ],
            [
            
                'link' => 'geo_game.php',
                'image' => 'geo-Quiz.jpg',
                'alt' => 'Quiz',
                'title' => 'Geo-Quiz'
            ]
        ];
        
        // Display geography games
        foreach ($geographyGames as $game) {
            echo "
            <a href='{$game['link']}' class='section'>
                <img src='{$game['image']}' alt='{$game['alt']}'>
                <h2>{$game['title']}</h2>
            </a>";
        }
        ?>
    </div>

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
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();
            
            // Add click effect to game cards
            const cards = document.querySelectorAll('.section');
            cards.forEach(card => {
                card.addEventListener('click', () => {
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

        function goBackToHome() {
            window.location.href ="index.php";
        }
    </script>
</body>
</html>