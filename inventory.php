<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Learniverse Games Shop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    /* Base Learniverse Styles */
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

    /* Navigation Tabs */
    .tabs {
      display: flex;
      justify-content: center;
      margin-bottom: 30px;
      gap: 15px;
    }

    .tab-btn {
      background: rgba(255, 255, 255, 0.1);
      border: none;
      color: white;
      padding: 12px 25px;
      border-radius: 50px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      backdrop-filter: blur(5px);
    }

    .tab-btn.active {
      background: linear-gradient(45deg, #00dbde, #fc00ff);
      box-shadow: 0 4px 15px rgba(0, 219, 222, 0.3);
    }

    .tab-btn:hover:not(.active) {
      background: rgba(255, 255, 255, 0.2);
    }

    /* Shop Container */
    .shop-container {
      display: none;
      max-width: 1200px;
      margin: 0 auto;
    }

    .shop-container.active {
      display: block;
    }

    /* Inventory Display */
    .inventory-display {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 30px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .coins-display {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }

    .coins-display i {
      color: gold;
      font-size: 1.5rem;
    }

    .coins-amount {
      font-size: 1.3rem;
      font-weight: bold;
      background: linear-gradient(90deg, gold, #ffd700);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .inventory-items {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
    }

    .inventory-item {
      background: rgba(0, 0, 0, 0.3);
      border-radius: 15px;
      padding: 15px;
      display: flex;
      align-items: center;
      gap: 15px;
      width: calc(50% - 8px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .inventory-item-icon {
      font-size: 2rem;
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(0, 219, 222, 0.1);
      border-radius: 10px;
      color: #00dbde;
    }

    .inventory-item-info {
      flex: 1;
    }

    .inventory-item-name {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .inventory-item-count {
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
    }

    /* Shop Items Grid */
    .shop-items {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }

    .shop-item {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 20px;
      transition: all 0.4s ease;
      border: 1px solid rgba(255, 255, 255, 0.1);
      perspective: 1000px;
    }

    .shop-item:hover {
      transform: translateY(-10px) rotateX(5deg);
      box-shadow: 0 15px 40px rgba(252, 0, 255, 0.4);
      background: rgba(255, 255, 255, 0.15);
    }

    .shop-item-image {
      width: 100%;
      height: 150px;
      object-fit: contain;
      margin-bottom: 15px;
      filter: drop-shadow(0 0 10px rgba(0, 219, 222, 0.5));
      transition: transform 0.5s ease;
    }

    .shop-item:hover .shop-item-image {
      transform: scale(1.1);
    }

    .shop-item-name {
      font-size: 1.2rem;
      margin-bottom: 10px;
      color: white;
    }

    .shop-item-description {
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
      margin-bottom: 15px;
      line-height: 1.4;
    }

    .shop-item-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .shop-item-price {
      display: flex;
      align-items: center;
      gap: 5px;
      font-weight: bold;
      color: gold;
    }

    .shop-item-price i {
      color: gold;
    }

    .buy-btn {
      background: linear-gradient(45deg, #00dbde, #fc00ff);
      color: white;
      border: none;
      padding: 8px 20px;
      border-radius: 50px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 219, 222, 0.3);
    }

    .buy-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(252, 0, 255, 0.4);
    }

    .buy-btn:disabled {
      background: rgba(255, 255, 255, 0.1);
      color: rgba(255, 255, 255, 0.5);
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .header {
        font-size: 2rem;
      }
      
      .tabs {
        flex-wrap: wrap;
      }
      
      .inventory-item {
        width: 100%;
      }
      
      .shop-items {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 480px) {
      .header {
        font-size: 1.8rem;
      }
      
      .tab-btn {
        padding: 10px 20px;
        font-size: 0.9rem;
      }
      
      .inventory-item {
        flex-direction: column;
        text-align: center;
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

<?php
// PHP variables for dynamic content
$coins = 1250;
$inventory = [
    'memory-cards' => 5,
    'special-dice' => 3,
    'spin-wheels' => 2,
    'theme-packs' => 1,
    'power-ups' => 0,
    'avatars' => 0
];

$shop_items = [
    [
        'id' => 'memory-cards',
        'name' => 'Memory Card Pack',
        'description' => 'Unlock 10 new themed memory card sets with unique designs for your matching games.',
        'price' => 200,
        'image' => 'cards.png'
    ],
    [
        'id' => 'special-dice',
        'name' => 'Special Dice Set',
        'description' => 'Premium animated dice for board games with special effects when rolled.',
        'price' => 350,
        'image' => 'dice.png'
    ],
    [
        'id' => 'spin-wheel',
        'name' => 'Spin Wheel',
        'description' => 'Interactive spin wheel with customizable options for game decisions.',
        'price' => 450,
        'image' => 'wheel.png'
    ],
    [
        'id' => 'theme-pack',
        'name' => 'Premium Theme Pack',
        'description' => 'Unlock exclusive visual themes for all your games with special animations.',
        'price' => 600,
        'image' => 'theme.png'
    ],
    [
        'id' => 'power-ups',
        'name' => 'Game Power-ups',
        'description' => 'Special abilities like extra time, hints, and double points for your games.',
        'price' => 150,
        'image' => 'https://cdn-icons-png.flaticon.com/512/3281/3281289.png'
    ],
    [
        'id' => 'avatar-bundle',
        'name' => 'Avatar Bundle',
        'description' => 'Collection of 15 unique avatars to personalize your gaming profile.',
        'price' => 300,
        'image' => 'https://cdn-icons-png.flaticon.com/512/4333/4333609.png'
    ]
];
?>

  <!-- Back Button - Now links to home.html -->
  <a href="home.php" class="back-btn">
      <i class="fas fa-arrow-left"></i>
  </a>

  <!-- 3D Animated Background -->
  <div id="canvas-container"></div>
  
  <h1 class="header">Game Shop</h1>
  
  <!-- Navigation Tabs -->
  <div class="tabs">
    <button class="tab-btn active" onclick="openTab('inventory')">
      <i class="fas fa-box-open"></i> My Inventory
    </button>
    <button class="tab-btn" onclick="openTab('shop')">
      <i class="fas fa-shopping-cart"></i> Shop
    </button>
  </div>
  
  <!-- Inventory Tab -->
  <div id="inventory" class="shop-container active">
    <div class="inventory-display">
      <div class="coins-display">
        <i class="fas fa-coins"></i>
        <span class="coins-amount" id="coins-amount"><?php echo number_format($coins); ?></span>
      </div>
      
      <h3 style="margin-bottom: 15px;">My Game Items</h3>
      
      <div class="inventory-items">
        <div class="inventory-item">
          <div class="inventory-item-icon">
            <i class="fas fa-dice"></i>
          </div>
          <div class="inventory-item-info">
            <div class="inventory-item-name">Special Dice</div>
            <div class="inventory-item-count">Owned: <?php echo $inventory['special-dice']; ?></div>
          </div>
        </div>
        
        <div class="inventory-item">
          <div class="inventory-item-icon">
            <i class="fas fa-images"></i>
          </div>
          <div class="inventory-item-info">
            <div class="inventory-item-name">Memory Card Packs</div>
            <div class="inventory-item-count">Owned: <?php echo $inventory['memory-cards']; ?></div>
          </div>
        </div>
        
        <div class="inventory-item">
          <div class="inventory-item-icon">
            <i class="fas fa-redo"></i>
          </div>
          <div class="inventory-item-info">
            <div class="inventory-item-name">Spin Wheels</div>
            <div class="inventory-item-count">Owned: <?php echo $inventory['spin-wheels']; ?></div>
          </div>
        </div>
        
        <div class="inventory-item">
          <div class="inventory-item-icon">
            <i class="fas fa-palette"></i>
          </div>
          <div class="inventory-item-info">
            <div class="inventory-item-name">Theme Packs</div>
            <div class="inventory-item-count">Owned: <?php echo $inventory['theme-packs']; ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Shop Tab -->
  <div id="shop" class="shop-container">
    <div class="shop-items">
      <?php foreach ($shop_items as $item): ?>
      <div class="shop-item">
        <img src="<?php echo $item['image']; ?>" class="shop-item-image" alt="<?php echo $item['name']; ?>">
        <h3 class="shop-item-name"><?php echo $item['name']; ?></h3>
        <p class="shop-item-description">
          <?php echo $item['description']; ?>
        </p>
        <div class="shop-item-footer">
          <span class="shop-item-price">
            <i class="fas fa-coins"></i> <?php echo $item['price']; ?>
          </span>
          <button class="buy-btn" onclick="buyItem('<?php echo $item['id']; ?>', <?php echo $item['price']; ?>)">
            Buy Now
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    // Tab Navigation
    function openTab(tabId) {
      document.querySelectorAll('.shop-container').forEach(tab => {
        tab.classList.remove('active');
      });
      document.getElementById(tabId).classList.add('active');
      
      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      event.currentTarget.classList.add('active');
    }

    // Inventory System - Using PHP variables as initial state
    let coins = <?php echo $coins; ?>;
    let inventory = <?php echo json_encode($inventory); ?>;

    // Update UI
    function updateInventoryUI() {
      document.getElementById('coins-amount').textContent = coins.toLocaleString();
      
      // In a real app, you would update all inventory item counts here
      // This could be enhanced with AJAX calls to update the server-side data
    }

    // Buy Item Function
    function buyItem(itemId, price) {
      if (coins >= price) {
        coins -= price;
        inventory[itemId] = (inventory[itemId] || 0) + 1;
        
        updateInventoryUI();
        alert(`Purchase successful! You now have ${inventory[itemId]} ${getItemName(itemId)}.`);
        
        // In a real application, you would send an AJAX request here to update the server-side data
        // updateServerData(itemId, price);
        
        // Disable button if can't afford another
        if (coins < price) {
          event.target.disabled = true;
        }
      } else {
        alert("Not enough coins! Play more games to earn coins.");
      }
    }

    function getItemName(itemId) {
      const names = {
        'memory-cards': 'Memory Card Packs',
        'special-dice': 'Special Dice',
        'spin-wheels': 'Spin Wheels',
        'theme-packs': 'Theme Packs',
        'power-ups': 'Power-ups',
        'avatars': 'Avatars'
      };
      return names[itemId] || itemId;
    }

    // Function to update server-side data (example for future implementation)
    function updateServerData(itemId, price) {
      // This would be an AJAX call to a PHP endpoint that updates the database
      /*
      fetch('update_purchase.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          itemId: itemId,
          price: price
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          console.log('Purchase saved to server');
        }
      });
      */
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

    // Initialize
    updateInventoryUI();
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</body>
</html>