<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Learniverse Games</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    /* Modern Dark Theme with Glassmorphism */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Montserrat', sans-serif;
      background: #000;
      color: white;
      min-height: 100vh;
      padding: 20px;
      overflow-x: hidden;
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

    .header {
      font-size: 2.5rem;
      margin-bottom: 30px;
      text-align: center;
      background: linear-gradient(90deg, #00dbde, #fc00ff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      text-shadow: 0 0 20px rgba(252, 0, 255, 0.3);
    }

    .game-dashboard {
      display: flex;
      gap: 25px;
      flex-wrap: wrap;
      justify-content: center;
      max-width: 1200px;
      margin: 0 auto;
    }

    .game-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 20px;
      width: 250px;
      cursor: pointer;
      transition: all 0.4s ease;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
      perspective: 1000px;
      text-align: center;
    }

    .game-card:hover {
      transform: translateY(-10px) rotateX(5deg);
      box-shadow: 0 15px 40px rgba(252, 0, 255, 0.4);
      background: rgba(255, 255, 255, 0.15);
    }

    .game-card img {
      width: 100%;
      height: 120px;
      object-fit: contain;
      margin-bottom: 15px;
      filter: drop-shadow(0 0 10px rgba(0, 219, 222, 0.5));
      transition: transform 0.5s ease;
    }

    .game-card:hover img {
      transform: scale(1.1);
    }

    .game-card h3 {
      font-size: 1.3rem;
      margin-bottom: 10px;
      color: white;
    }

    .game-card p {
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
      line-height: 1.4;
    }

    /* Game Container */
    .game-container {
      display: none;
      margin: 30px auto;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      padding: 25px;
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.1);
      max-width: 500px;
      width: 90%;
      text-align: center;
    }

    .game-container h2 {
      font-size: 1.8rem;
      margin-bottom: 15px;
      background: linear-gradient(90deg, #00dbde, #fc00ff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .game-container p {
      color: rgba(255, 255, 255, 0.8);
      margin-bottom: 15px;
    }

    /* Tic-Tac-Toe Grid */
    .tic-tac-toe {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      margin: 20px auto;
      max-width: 300px;
    }

    .cell {
      aspect-ratio: 1;
      background: rgba(0, 219, 222, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      cursor: pointer;
      border-radius: 10px;
      transition: all 0.3s ease;
      border: 1px solid rgba(0, 219, 222, 0.2);
    }

    .cell:hover {
      background: rgba(0, 219, 222, 0.2);
      transform: scale(1.05);
    }

    /* Memory Match Grid */
    .memory-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin: 20px auto;
      max-width: 300px;
    }

    .memory-card {
      aspect-ratio: 1;
      background: rgba(252, 0, 255, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      cursor: pointer;
      border-radius: 10px;
      transition: all 0.3s ease;
      border: 1px solid rgba(252, 0, 255, 0.2);
    }

    .memory-card:hover {
      background: rgba(252, 0, 255, 0.2);
    }

    /* Reaction Clicker */
    .reaction-box {
      width: 200px;
      height: 200px;
      background: rgba(255, 87, 34, 0.2);
      margin: 20px auto;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.2rem;
      cursor: pointer;
      transition: all 0.2s ease;
      border: 1px solid rgba(255, 87, 34, 0.3);
    }

    .reaction-box:hover {
      background: rgba(255, 87, 34, 0.3);
    }

    /* Snake Game */
    #snake-canvas {
      background: rgba(0, 0, 0, 0.3);
      border-radius: 10px;
      margin: 20px auto;
      display: block;
      border: 1px solid rgba(0, 219, 222, 0.3);
    }

    /* Rock-Paper-Scissors */
    .rps-choices {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin: 20px 0;
    }

    .rps-btn {
      background: rgba(33, 150, 243, 0.2);
      color: white;
      border: 1px solid rgba(33, 150, 243, 0.3);
      padding: 12px 0;
      border-radius: 10px;
      cursor: pointer;
      font-size: 2rem;
      flex: 1;
      max-width: 80px;
      transition: all 0.3s ease;
    }

    .rps-btn:hover {
      background: rgba(33, 150, 243, 0.3);
      transform: translateY(-3px);
    }

    .rps-result {
      font-weight: 600;
      margin: 15px 0;
      color: rgba(255, 255, 255, 0.9);
      min-height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .score {
      font-weight: bold;
      margin: 15px 0;
      font-size: 1.2rem;
      color: #00dbde;
    }

    .back-btn {
      background: linear-gradient(45deg, #00dbde, #fc00ff);
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 50px;
      cursor: pointer;
      margin-top: 15px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 219, 222, 0.3);
    }

    .back-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(252, 0, 255, 0.4);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .header {
        font-size: 2rem;
      }
      
      .game-card {
        width: 100%;
        max-width: 300px;
      }
      
      .game-container {
        padding: 20px 15px;
      }
      
      .rps-choices {
        gap: 10px;
      }
      
      .rps-btn {
        font-size: 1.8rem;
        max-width: 70px;
      }
    }

    @media (max-width: 480px) {
      .header {
        font-size: 1.8rem;
      }
      
      .tic-tac-toe, .memory-grid {
        gap: 5px;
      }
      
      .rps-choices {
        gap: 8px;
      }
      
      .rps-btn {
        font-size: 1.5rem;
        max-width: 60px;
        padding: 8px 0;
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
  </style>
</head>
<body>

    <!-- Back Button - Now links to home.html -->
    <a href="home.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
    </a>
  <!-- 3D Animated Background -->
  <div id="canvas-container"></div>
  
  <h1 class="header">Extra Fun Games</h1>
  
  <div class="game-dashboard">
    <!-- Game Cards -->
    <div class="game-card" onclick="startGame('tic-tac-toe')">
      <img src="XO.png" alt="Tic-Tac-Toe">
      <h3>Tic-Tac-Toe</h3>
      <p>Classic X and O game. Play against a friend!</p>
    </div>

    <div class="game-card" onclick="startGame('memory-match')">
      <img src="memorycard.png" alt="Memory Match">
      <h3>Memory Match</h3>
      <p>Find matching pairs of cards!</p>
    </div>

    <div class="game-card" onclick="startGame('reaction-clicker')">
      <img src="reaction.png" alt="Reaction Clicker">
      <h3>Reaction Clicker</h3>
      <p>Test your reflexes!</p>
    </div>

    <!-- Snake Game -->
    <div class="game-card" onclick="startGame('snake')">
      <img src="snake.png" alt="Snake Game">
      <h3>Snake</h3>
      <p>Guide the snake to eat food!</p>
    </div>

    <!-- Rock-Paper-Scissors -->
    <div class="game-card" onclick="startGame('rock-paper-scissors')">
      <img src="rock.png" alt="Rock-Paper-Scissors">
      <h3>Rock-Paper-Scissors</h3>
      <p>Beat the computer in this classic game!</p>
    </div>
  </div>

  <!-- Game Containers (Hidden Initially) -->
  <div id="tic-tac-toe" class="game-container">
    <h2>Tic-Tac-Toe</h2>
    <p>Player: <span id="player">X</span>'s turn</p>
    <div class="tic-tac-toe" id="tic-tac-toe-grid"></div>
    <button class="back-btn" onclick="hideGame()">Back to Dashboard</button>
  </div>

  <div id="memory-match" class="game-container">
    <h2>Memory Match</h2>
    <p>Matches Found: <span id="matches">0</span></p>
    <div class="memory-grid" id="memory-grid"></div>
    <button class="back-btn" onclick="hideGame()">Back to Dashboard</button>
  </div>

  <div id="reaction-clicker" class="game-container">
    <h2>Reaction Clicker</h2>
    <p>Click the box when it turns <span style="color: #00ff7f;">GREEN</span>!</p>
    <p class="score">Score: <span id="score">0</span></p>
    <div class="reaction-box" id="reaction-box">Wait for GREEN...</div>
    <button class="back-btn" onclick="hideGame()">Back to Dashboard</button>
  </div>

  <!-- Snake Game -->
  <div id="snake" class="game-container">
    <h2>Snake Game</h2>
    <p class="score">Score: <span id="snake-score">0</span></p>
    <canvas id="snake-canvas" width="300" height="300"></canvas>
    <p>Use arrow keys to move</p>
    <button class="back-btn" onclick="hideGame()">Back to Dashboard</button>
  </div>

  <!-- Rock-Paper-Scissors -->
  <div id="rock-paper-scissors" class="game-container">
    <h2>Rock-Paper-Scissors</h2>
    <p>Choose your move:</p>
    <div class="rps-choices">
      <button class="rps-btn" onclick="playRPS('✊')">✊</button>
      <button class="rps-btn" onclick="playRPS('✋')">✋</button>
      <button class="rps-btn" onclick="playRPS('✌️')">✌️</button>
    </div>
    <div class="rps-result" id="rps-result">Make your choice!</div>
    <p class="score">Score: <span id="rps-score">0</span></p>
    <button class="back-btn" onclick="hideGame()">Back to Dashboard</button>
  </div>

  <script>
    // Show selected game
    function startGame(gameId) {
      document.querySelectorAll('.game-container').forEach(game => {
        game.style.display = 'none';
      });
      document.getElementById(gameId).style.display = 'block';
      
      // Initialize the selected game
      if (gameId === 'tic-tac-toe') initTicTacToe();
      else if (gameId === 'memory-match') initMemoryMatch();
      else if (gameId === 'reaction-clicker') initReactionClicker();
      else if (gameId === 'snake') initSnake();
      else if (gameId === 'rock-paper-scissors') initRPS();
    }

    // Hide game and return to dashboard
    function hideGame() {
      document.querySelectorAll('.game-container').forEach(game => {
        game.style.display = 'none';
      });
    }

    // --- Tic-Tac-Toe ---
    function initTicTacToe() {
      const grid = document.getElementById('tic-tac-toe-grid');
      grid.innerHTML = '';
      let currentPlayer = 'X';
      let board = ['', '', '', '', '', '', '', '', ''];

      for (let i = 0; i < 9; i++) {
        const cell = document.createElement('div');
        cell.className = 'cell';
        cell.addEventListener('click', () => handleCellClick(i));
        grid.appendChild(cell);
      }

      function handleCellClick(index) {
        if (board[index] !== '' || checkWinner()) return;
        board[index] = currentPlayer;
        grid.children[index].textContent = currentPlayer;
        
        if (checkWinner()) {
          setTimeout(() => {
            alert(`${currentPlayer} wins!`);
            initTicTacToe();
          }, 100);
          return;
        }
        
        currentPlayer = currentPlayer === 'X' ? 'O' : 'X';
        document.getElementById('player').textContent = currentPlayer;
      }

      function checkWinner() {
        const winPatterns = [
          [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
          [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
          [0, 4, 8], [2, 4, 6]             // Diagonals
        ];

        for (const pattern of winPatterns) {
          const [a, b, c] = pattern;
          if (board[a] && board[a] === board[b] && board[a] === board[c]) {
            return true;
          }
        }
        
        // Check for draw
        if (!board.includes('')) {
          setTimeout(() => {
            alert("It's a draw!");
            initTicTacToe();
          }, 100);
          return true;
        }
        
        return false;
      }
    }

    // --- Memory Match ---
    function initMemoryMatch() {
      const grid = document.getElementById('memory-grid');
      grid.innerHTML = '';
      const emojis = ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼'];
      const cards = [...emojis, ...emojis];
      let flippedCards = [];
      let matchedPairs = 0;

      cards.sort(() => Math.random() - 0.5);

      for (let i = 0; i < 16; i++) {
        const card = document.createElement('div');
        card.className = 'memory-card';
        card.dataset.index = i;
        card.textContent = '?';
        card.addEventListener('click', () => flipCard(card, i));
        grid.appendChild(card);
      }

      function flipCard(card, index) {
        if (flippedCards.length >= 2 || card.textContent !== '?') return;
        
        card.textContent = cards[index];
        flippedCards.push({ card, index });

        if (flippedCards.length === 2) {
          const [card1, card2] = flippedCards;
          if (cards[card1.index] === cards[card2.index]) {
            matchedPairs++;
            document.getElementById('matches').textContent = matchedPairs;
            card1.card.style.visibility = 'hidden';
            card2.card.style.visibility = 'hidden';
            flippedCards = [];
            
            if (matchedPairs === 8) {
              setTimeout(() => {
                alert('You won!');
                initMemoryMatch();
              }, 500);
            }
          } else {
            setTimeout(() => {
              card1.card.textContent = '?';
              card2.card.textContent = '?';
              flippedCards = [];
            }, 1000);
          }
        }
      }
    }

    // --- Reaction Clicker ---
    function initReactionClicker() {
      const box = document.getElementById('reaction-box');
      const scoreDisplay = document.getElementById('score');
      let score = 0;
      let canClick = false;
      let timeoutId;

      box.style.backgroundColor = '#FF5722';
      box.textContent = 'Wait for GREEN...';

      box.addEventListener('click', () => {
        if (canClick) {
          score++;
          scoreDisplay.textContent = score;
          canClick = false;
          box.style.backgroundColor = '#FF5722';
          box.textContent = 'Wait for GREEN...';
          clearTimeout(timeoutId);
          timeoutId = setTimeout(startRound, Math.random() * 2000 + 1000);
        } else {
          score = Math.max(0, score - 1);
          scoreDisplay.textContent = score;
          box.textContent = 'Too soon!';
          setTimeout(() => {
            box.textContent = 'Wait for GREEN...';
          }, 1000);
        }
      });

      function startRound() {
        canClick = true;
        box.style.backgroundColor = '#4CAF50';
        box.textContent = 'CLICK NOW!';
        timeoutId = setTimeout(() => {
          if (canClick) {
            canClick = false;
            box.style.backgroundColor = '#FF5722';
            box.textContent = 'Too slow!';
            setTimeout(() => {
              box.textContent = 'Wait for GREEN...';
              timeoutId = setTimeout(startRound, Math.random() * 2000 + 1000);
            }, 1000);
          }
        }, 2000);
      }

      timeoutId = setTimeout(startRound, Math.random() * 2000 + 1000);
    }

    // --- Snake Game ---
    function initSnake() {
      const canvas = document.getElementById('snake-canvas');
      const ctx = canvas.getContext('2d');
      const scoreDisplay = document.getElementById('snake-score');
      let score = 0;
      let snake = [{ x: 150, y: 150 }];
      let food = { x: 0, y: 0 };
      let dx = 20;
      let dy = 0;
      let gameLoop;
      let gameSpeed = 100;

      // Generate food
      function generateFood() {
        const cols = canvas.width / 20;
        const rows = canvas.height / 20;
        food.x = Math.floor(Math.random() * cols) * 20;
        food.y = Math.floor(Math.floor(Math.random() * rows) * 20);
        
        // Make sure food doesn't spawn on snake
        for (let segment of snake) {
          if (segment.x === food.x && segment.y === food.y) {
            return generateFood();
          }
        }
      }

      // Draw everything
      function draw() {
        // Clear canvas
        ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Draw snake
        ctx.fillStyle = '#00dbde';
        snake.forEach((segment, index) => {
          // Head is slightly different color
          if (index === 0) {
            ctx.fillStyle = '#fc00ff';
          } else {
            ctx.fillStyle = '#00dbde';
          }
          ctx.fillRect(segment.x, segment.y, 18, 18);
          ctx.strokeStyle = 'rgba(0, 0, 0, 0.2)';
          ctx.strokeRect(segment.x, segment.y, 18, 18);
        });

        // Draw food
        ctx.fillStyle = '#FF5722';
        ctx.beginPath();
        ctx.arc(food.x + 9, food.y + 9, 9, 0, Math.PI * 2);
        ctx.fill();
      }

      // Update game state
      function update() {
        const head = { x: snake[0].x + dx, y: snake[0].y + dy };

        // Check wall collision (wrap around)
        if (head.x < 0) head.x = canvas.width - 20;
        if (head.x >= canvas.width) head.x = 0;
        if (head.y < 0) head.y = canvas.height - 20;
        if (head.y >= canvas.height) head.y = 0;

        // Check self collision
        for (let i = 1; i < snake.length; i++) {
          if (head.x === snake[i].x && head.y === snake[i].y) {
            gameOver();
            return;
          }
        }

        snake.unshift(head);

        // Check food collision
        if (head.x === food.x && head.y === food.y) {
          score++;
          scoreDisplay.textContent = score;
          
          // Increase speed every 5 points
          if (score % 5 === 0 && gameSpeed > 50) {
            gameSpeed -= 5;
            clearInterval(gameLoop);
            gameLoop = setInterval(gameStep, gameSpeed);
          }
          
          generateFood();
        } else {
          snake.pop();
        }
      }

      // Game step
      function gameStep() {
        update();
        draw();
      }

      // Game over
      function gameOver() {
        clearInterval(gameLoop);
        alert(`Game Over! Score: ${score}`);
        initSnake(); // Restart
      }

      // Keyboard controls
      document.addEventListener('keydown', e => {
        // Prevent reverse direction
        if (e.key === 'ArrowUp' && dy !== 20) {
          dx = 0;
          dy = -20;
        } else if (e.key === 'ArrowDown' && dy !== -20) {
          dx = 0;
          dy = 20;
        } else if (e.key === 'ArrowLeft' && dx !== 20) {
          dx = -20;
          dy = 0;
        } else if (e.key === 'ArrowRight' && dx !== -20) {
          dx = 20;
          dy = 0;
        }
      });

      // Initialize game
      generateFood();
      gameLoop = setInterval(gameStep, gameSpeed);
    }

    // --- Rock-Paper-Scissors ---
    function initRPS() {
      const choices = ['✊', '✋', '✌️'];
      let score = 0;

      window.playRPS = function(playerChoice) {
        const computerChoice = choices[Math.floor(Math.random() * 3)];
        const result = getResult(playerChoice, computerChoice);
        
        document.getElementById('rps-result').innerHTML = `
          You: ${playerChoice} | Computer: ${computerChoice}<br>
          <strong>${result}</strong>
        `;

        if (result === 'You win!') {
          score++;
          document.getElementById('rps-score').textContent = score;
        } else if (result === 'You lose!' && score > 0) {
          score--;
          document.getElementById('rps-score').textContent = score;
        }
      };

      function getResult(player, computer) {
        if (player === computer) return 'Draw!';
        if (
          (player === '✊' && computer === '✌️') ||
          (player === '✋' && computer === '✊') ||
          (player === '✌️' && computer === '✋')
        ) return 'You win!';
        return 'You lose!';
      }
    }

    // 3D Background with Three.js
    const container = document.getElementById('canvas-container');
    if (container) {
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
    }
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</body>
</html>