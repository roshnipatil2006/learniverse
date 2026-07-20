<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indian Political Science Word Puzzle</title>
    <style>
        /* Your existing CSS styles remain the same */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #080606;
            background-image: linear-gradient(rgba(13, 12, 12, 0.701), rgba(58, 42, 42, 0.023)), url('other img/puzzlebg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .game-container {
            display: flex;
            max-width: 1000px;
            margin: 0 auto;
            background-color: rgba(135, 81, 81, 0.416);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .puzzle-container {
            
            flex: 2;
            padding-right: 20px;
        }
        
        .clues-container {
            flex: 1;
            background-color: #f9f9f9b2;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ff9933;
        }
        
        h1 {
            text-align: center;

            color: #ff9933;
            text-shadow: 0 0 10px rgba(246, 231, 100, 0.973);
            margin-bottom: 30px;
        }
        
        h2 {
            color: #138808;
            border-bottom: 2px solid #138808;
            padding-bottom: 5px;
            font-size: 1.2em;
        }
        
        .puzzle-grid {
            border: #000;
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 2px;
            margin-bottom: 20px;
        }
        
        .cell {
            width: 100%;
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e9e9e9;
            font-weight: bold;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s;
            border-radius: 3px;
        }
        
        .cell.selected {
            background-color: #ff9933;
            color: white;
        }
        
        .cell.found {
            background-color: #138808;
            color: white;
        }
        
        .cell.hint {
            background-color: #ffcc99;
        }
        
        .clue {
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 5px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .clue.found {
            text-decoration: line-through;
            color: #888;
            background-color: #e0ffe0;
        }
        
        .controls {
            text-align: center;
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        button {
            background-color: #ff9933;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            min-width: 120px;
        }
        
        button:hover {
            background-color: #e68a00;
        }
        
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        button.secondary {
            background-color: #138808;
        }
        
        button.secondary:hover {
            background-color: #0d6006;
        }
        
        .level-indicator {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            color: #2be71a;
        }
        
        .congratulations {
            text-align: center;
            color: #138808;
            font-size: 1.5em;
            font-weight: bold;
            margin-top: 20px;
            display: none;
        }
        
        .hint-message {
            text-align: center;
            margin-top: 10px;
            color: #ff6600;
            font-weight: bold;
            min-height: 24px;
        }
        
        .back-button {
    position: absolute;
    top: 20px;
    right: 20px; /* Move button to the right */
    background-color: #007bff; /* Blue color */
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
    box-shadow: 5px 0px 15px rgba(0, 123, 255, 0.6); /* Glow on the right */
}

.back-button:hover {
    background-color: #0056b3; /* Darker blue */
    transform: scale(1.05);
    box-shadow: 10px 0px 20px rgba(0, 123, 255, 0.8); /* Stronger glow on hover */
}

.coin-reward {
    text-align: center;
    color: #ffd700;
    font-size: 1.2em;
    font-weight: bold;
    margin-top: 10px;
    display: none;
}

    </style>
</head>
<body>
    <a href="ps.php" class="back-button">
        Back
      </a>
    <h1>Indian Political Science Word Puzzle</h1>
    
    <div class="level-indicator">Level: <span id="level">1</span>/5 - <span id="levelName">Basic Terms</span></div>
    
    <div class="game-container">
        <div class="puzzle-container">
            <div class="puzzle-grid" id="puzzleGrid"></div>
            <div class="hint-message" id="hintMessage"></div>
            <div class="coin-reward" id="coinReward">+50 Coins Awarded!</div>
            <div class="controls">
                <button id="hintButton">Get Hint</button>
                <button id="checkButton">Check Selected</button>
                <button id="resetButton">Reset Puzzle</button>
                <button id="nextLevelButton" class="secondary" disabled>Next Level</button>
            </div>
            <div class="congratulations" id="congrats">
                Congratulations! You've completed this level!
            </div>
        </div>
        
        <div class="clues-container">
            <h2>Clues</h2>
            <div id="cluesList"></div>
        </div>
    </div>

    <script>
        // Game data with increasing difficulty
        const gameLevels = [
            { // Level 1 - Easy
                name: "Basic Terms",
                words: [
                    { word: "LOKSABHA", clue: "The lower house of India's Parliament consisting of elected representatives", 
                      hint: "Starts with 'L', has 8 letters, meets in New Delhi" },
                    { word: "PRESIDENT", clue: "The constitutional head of the Indian state and supreme commander of armed forces", 
                      hint: "Starts with 'P', has 9 letters, lives in Rashtrapati Bhavan" },
                    { word: "DEMOCRACY", clue: "System of government where power is vested in the people through elections", 
                      hint: "Starts with 'D', has 9 letters, India is the world's largest" },
                    { word: "CONGRESS", clue: "India's oldest political party, founded in 1885", 
                      hint: "Starts with 'C', has 8 letters, led India to independence" },
                    { word: "BJP", clue: "Political party founded in 1980, currently the ruling party at center", 
                      hint: "3-letter acronym, currently in power at center" }
                ],
                gridSize: 10
            },
            { // Level 2 - Medium
                name: "Government Structure",
                words: [
                    { word: "RAJYASABHA", clue: "The upper house of Parliament representing states and union territories", 
                      hint: "Starts with 'R', has 9 letters, members are indirectly elected" },
                    { word: "FEDERALISM", clue: "System where power is divided between central and state governments", 
                      hint: "Starts with 'F', has 9 letters, key feature of Indian polity" },
                    { word: "SECULAR", clue: "Constitutional principle ensuring equal treatment of all religions", 
                      hint: "Starts with 'S', has 7 letters, added to preamble by 42nd amendment" },
                    { word: "COALITION", clue: "Government formed by alliance of multiple political parties", 
                      hint: "Starts with 'C', has 9 letters, common when no single party gets majority" },
                    { word: "GOVERNOR", clue: "Constitutional head of a state appointed by the President", 
                      hint: "Starts with 'G', has 8 letters, represents central government in states" }
                ],
                gridSize: 10
            },
            { // Level 3 - Medium-Hard
                name: "Constitutional Concepts",
                words: [
                    { word: "CONSTITUTION", clue: "Supreme legal document of India adopted on 26th November 1949", 
                      hint: "Starts with 'C', has 12 letters, came into effect in 1950" },
                    { word: "AMENDMENT", clue: "Process to modify or add provisions to the Constitution", 
                      hint: "Starts with 'A', has 9 letters, 105 have been made so far" },
                    { word: "JUDICIARY", clue: "Independent branch of government that interprets laws", 
                      hint: "Starts with 'J', has 9 letters, includes Supreme Court and High Courts" },
                    { word: "PANCHAYAT", clue: "Local self-government system in rural areas established by 73rd Amendment", 
                      hint: "Starts with 'P', has 9 letters, grassroots level democracy" },
                    { word: "FRANCHISE", clue: "The right to vote in political elections", 
                      hint: "Starts with 'F', has 9 letters, universal adult franchise in India" }
                ],
                gridSize: 10
            },
            { // Level 4 - Hard
                name: "Rights & Policies",
                words: [
                    { word: "FUNDAMENTAL", clue: "Basic human rights guaranteed by Part III of the Constitution", 
                      hint: "Starts with 'F', has 11 letters, includes right to equality and freedom" },
                    { word: "DIRECTIVE", clue: "Principles in Part IV that guide government policy making", 
                      hint: "Starts with 'D', has 9 letters, not enforceable in courts" },
                    { word: "RESERVATION", clue: "Policy for affirmative action for backward classes in education and jobs", 
                      hint: "Starts with 'R', has 11 letters, based on caste and tribe lists" },
                    { word: "JUDICIAL", clue: "Relating to courts or judges in the government system", 
                      hint: "Starts with 'J', has 8 letters, review is an important power" },
                    { word: "RTI", clue: "2005 Act that empowers citizens to access government information", 
                      hint: "3-letter acronym, transparency law, starts with 'R'" }
                ],
                gridSize: 10
            },
            { // Level 5 - Very Hard
                name: "Advanced Concepts",
                words: [
                    { word: "PARLIAMENT", clue: "System where executive is responsible to the legislature", 
                      hint: "Starts with 'P', has 9 letters, British model adopted by India" },
                    { word: "SOCIALIST", clue: "Constitutional term added by 42nd Amendment indicating economic philosophy", 
                      hint: "Starts with 'S', has 9 letters, mixed economy approach" },
                    { word: "NONALIGNED", clue: "India's foreign policy during Cold War avoiding military alliances", 
                      hint: "Starts with 'N', has 9 letters, initiated by Nehru" },
                    { word: "SUSTAINABLE", clue: "Development approach balancing growth with environmental protection", 
                      hint: "Starts with 'S', has 11 letters, UN's SDG goals" },
                    { word: "FEDERAL", clue: "Characteristic of Indian polity with division of powers between center and states", 
                      hint: "Starts with 'F', has 7 letters, but with strong central tendencies" }
                ],
                gridSize: 10
            }
        ];

        // Game state
        let currentLevel = 0;
        let grid = [];
        let selectedCells = [];
        let foundWords = [];
        let wordPositions = [];
        let hintUsed = false;
        let levelCompleted = false;
        let startTime = Date.now();

        // DOM elements
        const puzzleGrid = document.getElementById('puzzleGrid');
        const cluesList = document.getElementById('cluesList');
        const checkButton = document.getElementById('checkButton');
        const resetButton = document.getElementById('resetButton');
        const hintButton = document.getElementById('hintButton');
        const nextLevelButton = document.getElementById('nextLevelButton');
        const congratsMessage = document.getElementById('congrats');
        const levelIndicator = document.getElementById('level');
        const levelName = document.getElementById('levelName');
        const hintMessage = document.getElementById('hintMessage');
        const coinReward = document.getElementById('coinReward');

        // Initialize the game
        function initGame() {
            // Reset game state
            selectedCells = [];
            foundWords = [];
            wordPositions = [];
            hintUsed = false;
            levelCompleted = false;
            hintMessage.textContent = '';
            coinReward.style.display = 'none';
            startTime = Date.now();
            
            // Update level indicator
            levelIndicator.textContent = currentLevel + 1;
            levelName.textContent = gameLevels[currentLevel].name;
            
            // Clear the grid and clues
            puzzleGrid.innerHTML = '';
            cluesList.innerHTML = '';
            
            // Hide congratulations message and disable next level button
            congratsMessage.style.display = 'none';
            nextLevelButton.disabled = true;
            
            // Create empty grid
            const gridSize = gameLevels[currentLevel].gridSize;
            grid = Array(gridSize).fill().map(() => Array(gridSize).fill(''));
            
            // Place words in the grid
            placeWords();
            
            // Fill remaining cells with random letters
            fillEmptyCells();
            
            // Render the grid
            renderGrid();
            
            // Render the clues
            renderClues();
        }

        // Place words in the grid
        function placeWords() {
            const words = gameLevels[currentLevel].words;
            const gridSize = gameLevels[currentLevel].gridSize;
            
            words.forEach(wordObj => {
                const word = wordObj.word.toUpperCase();
                let placed = false;
                let attempts = 0;
                const maxAttempts = 100;
                
                while (!placed && attempts < maxAttempts) {
                    attempts++;
                    
                    // Randomly choose direction (0: horizontal, 1: vertical, 2: diagonal)
                    const direction = Math.floor(Math.random() * 3);
                    
                    // Randomly choose starting position
                    let row, col;
                    
                    if (direction === 0) { // Horizontal
                        row = Math.floor(Math.random() * gridSize);
                        col = Math.floor(Math.random() * (gridSize - word.length));
                    } else if (direction === 1) { // Vertical
                        row = Math.floor(Math.random() * (gridSize - word.length));
                        col = Math.floor(Math.random() * gridSize);
                    } else { // Diagonal
                        row = Math.floor(Math.random() * (gridSize - word.length));
                        col = Math.floor(Math.random() * (gridSize - word.length));
                    }
                    
                    // Check if word can be placed
                    let canPlace = true;
                    const positions = [];
                    
                    for (let i = 0; i < word.length; i++) {
                        let r = row, c = col;
                        
                        if (direction === 0) { // Horizontal
                            c = col + i;
                        } else if (direction === 1) { // Vertical
                            r = row + i;
                        } else { // Diagonal
                            r = row + i;
                            c = col + i;
                        }
                        
                        // Check if cell is empty or has the same letter
                        if (grid[r][c] !== '' && grid[r][c] !== word[i]) {
                            canPlace = false;
                            break;
                        }
                        
                        positions.push({ row: r, col: c, letter: word[i] });
                    }
                    
                    // Place the word if possible
                    if (canPlace) {
                        for (let i = 0; i < word.length; i++) {
                            const pos = positions[i];
                            grid[pos.row][pos.col] = word[i];
                        }
                        
                        // Store word positions for checking later
                        wordPositions.push({
                            word: word,
                            clue: wordObj.clue,
                            hint: wordObj.hint,
                            positions: positions,
                            direction: direction
                        });
                        
                        placed = true;
                    }
                }
                
                if (!placed && attempts >= maxAttempts) {
                    console.warn(`Could not place word: ${word}`);
                }
            });
        }

        // Fill empty cells with random letters
        function fillEmptyCells() {
            const gridSize = gameLevels[currentLevel].gridSize;
            // Weighted letters more common in political terms
            const letters = 'AAAAAABCDEEEEEEFGHIIIIJKLMNOOOPQRSTUUUVWXYZ';
            
            for (let row = 0; row < gridSize; row++) {
                for (let col = 0; col < gridSize; col++) {
                    if (grid[row][col] === '') {
                        grid[row][col] = letters[Math.floor(Math.random() * letters.length)];
                    }
                }
            }
        }

        // Render the grid
        function renderGrid() {
            const gridSize = gameLevels[currentLevel].gridSize;
            
            for (let row = 0; row < gridSize; row++) {
                for (let col = 0; col < gridSize; col++) {
                    const cell = document.createElement('div');
                    cell.className = 'cell';
                    cell.textContent = grid[row][col];
                    cell.dataset.row = row;
                    cell.dataset.col = col;
                    
                    // Check if this cell is part of a found word
                    const isFound = wordPositions.some(wp => 
                        foundWords.includes(wp.word) && 
                        wp.positions.some(pos => pos.row === row && pos.col === col)
                    );
                    
                    if (isFound) {
                        cell.classList.add('found');
                    }
                    
                    cell.addEventListener('click', () => toggleCellSelection(cell, row, col));
                    puzzleGrid.appendChild(cell);
                }
            }
        }

        // Toggle cell selection
        function toggleCellSelection(cell, row, col) {
            // Don't allow selection if game is complete
            if (foundWords.length === wordPositions.length) return;
            
            const index = selectedCells.findIndex(sc => sc.row === row && sc.col === col);
            
            if (index === -1) {
                // If this is the first selection, just select it
                if (selectedCells.length === 0) {
                    selectedCells.push({ row, col, cell });
                    cell.classList.add('selected');
                    return;
                }
                
                // Check if the new selection is adjacent to the last selection
                const lastCell = selectedCells[selectedCells.length - 1];
                const rowDiff = Math.abs(row - lastCell.row);
                const colDiff = Math.abs(col - lastCell.col);
                
                if ((rowDiff === 0 && colDiff === 1) || // Same row, adjacent column
                    (colDiff === 0 && rowDiff === 1) || // Same column, adjacent row
                    (rowDiff === 1 && colDiff === 1)) { // Diagonal adjacency
                    
                    selectedCells.push({ row, col, cell });
                    cell.classList.add('selected');
                } else {
                    // Not adjacent - start new selection with this cell
                    selectedCells.forEach(sc => sc.cell.classList.remove('selected'));
                    selectedCells = [{ row, col, cell }];
                    cell.classList.add('selected');
                }
            } else {
                // Deselect cell and all subsequent cells
                for (let i = index; i < selectedCells.length; i++) {
                    selectedCells[i].cell.classList.remove('selected');
                }
                selectedCells.splice(index);
            }
        }

        // Render clues
        function renderClues() {
            wordPositions.forEach((wp, index) => {
                const clueElement = document.createElement('div');
                clueElement.className = 'clue';
                if (foundWords.includes(wp.word)) {
                    clueElement.classList.add('found');
                }
                clueElement.textContent = `${index + 1}. ${wp.clue}`;
                cluesList.appendChild(clueElement);
            });
        }

        // Check if selected cells form a valid word
        function checkSelection() {
            if (selectedCells.length < 2) {
                alert('Please select at least 2 letters to form a word!');
                return;
            }
            
            // Check if selection is in a straight line
            const firstCell = selectedCells[0];
            const lastCell = selectedCells[selectedCells.length - 1];
            
            let direction;
            if (firstCell.row === lastCell.row) {
                direction = 0; // Horizontal
            } else if (firstCell.col === lastCell.col) {
                direction = 1; // Vertical
            } else if (Math.abs(firstCell.row - lastCell.row) === Math.abs(firstCell.col - lastCell.col)) {
                direction = 2; // Diagonal
            } else {
                alert('Selected letters must be in a straight line (horizontal, vertical, or diagonal)!');
                return;
            }
            
            // Verify all cells are in the line
            let isValid = true;
            const rowStep = direction === 0 ? 0 : (lastCell.row > firstCell.row ? 1 : -1);
            const colStep = direction === 1 ? 0 : (lastCell.col > firstCell.col ? 1 : -1);
            
            let currentRow = firstCell.row;
            let currentCol = firstCell.col;
            
            for (let i = 0; i < selectedCells.length; i++) {
                if (currentRow !== selectedCells[i].row || currentCol !== selectedCells[i].col) {
                    isValid = false;
                    break;
                }
                currentRow += rowStep;
                currentCol += colStep;
            }
            
            if (!isValid) {
                alert('Selected letters must be consecutive in a straight line!');
                return;
            }
            
            // Get the selected word
            const selectedWord = selectedCells.map(sc => grid[sc.row][sc.col]).join('');
            
            // Check if the word matches any of the hidden words (forward or backward)
            const matchedWord = wordPositions.find(wp => 
                wp.word === selectedWord || 
                wp.word === selectedWord.split('').reverse().join('')
            );
            
            if (matchedWord && !foundWords.includes(matchedWord.word)) {
                // Correct word found!
                foundWords.push(matchedWord.word);
                
                // Mark cells as found
                selectedCells.forEach(sc => {
                    sc.cell.classList.remove('selected');
                    sc.cell.classList.add('found');
                });
                
                // Update clues
                cluesList.innerHTML = '';
                renderClues();
                
                // Check if all words are found
                if (foundWords.length === wordPositions.length) {
                    levelCompleted = true;
                    congratsMessage.style.display = 'block';
                    nextLevelButton.disabled = false;
                    
                    // Calculate duration in seconds
                    const duration = Math.floor((Date.now() - startTime) / 1000);
                    
                    // Save game session to database and award coins
                    saveGameSession(duration);
                }
            } else if (matchedWord) {
                alert(`You've already found "${matchedWord.word}"!`);
            } else {
                alert(`"${selectedWord}" is not one of the hidden words. Try again!`);
            }
            
            // Clear selection
            selectedCells.forEach(sc => sc.cell.classList.remove('selected'));
            selectedCells = [];
        }

        // Save game session to database
        function saveGameSession(duration) {
            // Calculate score based on level, duration, and hints used
            const baseScore = (currentLevel + 1) * 100;
            const timeBonus = Math.max(0, 300 - duration); // Bonus for faster completion
            const hintPenalty = hintUsed ? 50 : 0;
            const finalScore = baseScore + timeBonus - hintPenalty;
            
            // Prepare data for saving
            const gameData = {
                game_type: 'puzzle',
                subject: 'political_science',
                level: currentLevel + 1,
                score: finalScore,
                duration: duration
            };
            
            // Send data to PHP script using AJAX
            fetch('complete_level.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(gameData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show coin reward
                    coinReward.style.display = 'block';
                    coinReward.textContent = `+50 Coins Awarded! Total: ${data.new_coins || '50'} coins`;
                    
                    console.log('Game session saved successfully:', data);
                } else {
                    console.error('Failed to save game session:', data.message);
                    // Still show reward message even if save failed
                    coinReward.style.display = 'block';
                    coinReward.textContent = '+50 Coins Awarded!';
                }
            })
            .catch(error => {
                console.error('Error saving game session:', error);
                // Still show reward message even if save failed
                coinReward.style.display = 'block';
                coinReward.textContent = '+50 Coins Awarded!';
            });
        }

        // Provide a hint to the player
        function provideHint() {
            if (hintUsed) {
                hintMessage.textContent = "You've already used your hint for this level!";
                return;
            }
            
            // Find a word that hasn't been found yet
            const unfoundWords = wordPositions.filter(wp => !foundWords.includes(wp.word));
            
            if (unfoundWords.length === 0) {
                hintMessage.textContent = "No more words to find!";
                return;
            }
            
            // Select a random unfound word
            const hintWord = unfoundWords[Math.floor(Math.random() * unfoundWords.length)];
            
            // Show the hint message
            hintMessage.textContent = `Hint: ${hintWord.hint}`;
            
            // Highlight the first letter of the word
            const firstLetterPos = hintWord.positions[0];
            const cells = document.querySelectorAll('.cell');
            
            cells.forEach(cell => {
                cell.classList.remove('hint');
                const row = parseInt(cell.dataset.row);
                const col = parseInt(cell.dataset.col);
                
                if (row === firstLetterPos.row && col === firstLetterPos.col) {
                    cell.classList.add('hint');
                }
            });
            
            hintUsed = true;
        }

        // Reset the current level
        function resetPuzzle() {
            initGame();
        }

        // Move to the next level
        function nextLevel() {
            if (currentLevel < gameLevels.length - 1) {
                currentLevel++;
                initGame();
            } else {
                alert('Congratulations! You have completed all levels!');
                // Optionally save final completion
                const duration = Math.floor((Date.now() - startTime) / 1000);
                saveGameSession(duration);
            }
        }

        // Event listeners
        checkButton.addEventListener('click', checkSelection);
        resetButton.addEventListener('click', resetPuzzle);
        hintButton.addEventListener('click', provideHint);
        nextLevelButton.addEventListener('click', nextLevel);

        // Start the game
        initGame();
    </script>
</body>
</html>