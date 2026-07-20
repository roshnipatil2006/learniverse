<?php
class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $email;
    public $nickname;
    public $password_hash;
    public $avatar_url;
    public $avatar_id;
    public $current_level;
    public $current_streak;
    public $performance_score;
    public $is_active;
    public $created_at;
    public $updated_at;
    public $coins;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new user
    public function create() {
        $query = 'INSERT INTO ' . $this->table . '
                SET email = :email,
                    nickname = :nickname,
                    password_hash = :password_hash,
                    avatar_url = :avatar_url,
                    avatar_id = :avatar_id,
                    current_level = :current_level,
                    current_streak = :current_streak,
                    performance_score = :performance_score,
                    is_active = :is_active,
                    coins = :coins';

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nickname = htmlspecialchars(strip_tags($this->nickname));

        // Bind data
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':nickname', $this->nickname);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':avatar_url', $this->avatar_url);
        $stmt->bindParam(':avatar_id', $this->avatar_id);
        $stmt->bindParam(':current_level', $this->current_level);
        $stmt->bindParam(':current_streak', $this->current_streak);
        $stmt->bindParam(':performance_score', $this->performance_score);
        $stmt->bindParam(':is_active', $this->is_active);
        $stmt->bindParam(':coins', $this->coins);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Get user by email
    public function getByEmail($email) {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE email = :email AND is_active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt;
    }

    // Get user by nickname
    public function getByNickname($nickname) {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE nickname = :nickname AND is_active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nickname', $nickname);
        $stmt->execute();
        return $stmt;
    }

    // Get user by ID
    public function getById($id) {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE id = :id AND is_active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt;
    }

    // Update user avatar
    public function updateAvatar($user_id, $avatar_url, $avatar_id) {
        $query = 'UPDATE ' . $this->table . '
                SET avatar_url = :avatar_url,
                    avatar_id = :avatar_id,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :user_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':avatar_url', $avatar_url);
        $stmt->bindParam(':avatar_id', $avatar_id);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // Update user profile
    public function updateProfile($user_id, $data) {
        $query = 'UPDATE ' . $this->table . ' SET ';
        $updates = [];
        
        if (isset($data['nickname'])) {
            $updates[] = 'nickname = :nickname';
        }
        if (isset($data['current_level'])) {
            $updates[] = 'current_level = :current_level';
        }
        if (isset($data['current_streak'])) {
            $updates[] = 'current_streak = :current_streak';
        }
        if (isset($data['performance_score'])) {
            $updates[] = 'performance_score = :performance_score';
        }
        if (isset($data['coins'])) {
            $updates[] = 'coins = :coins';
        }
        
        $updates[] = 'updated_at = CURRENT_TIMESTAMP';
        $query .= implode(', ', $updates) . ' WHERE id = :user_id';

        $stmt = $this->conn->prepare($query);
        
        if (isset($data['nickname'])) {
            $stmt->bindParam(':nickname', $data['nickname']);
        }
        if (isset($data['current_level'])) {
            $stmt->bindParam(':current_level', $data['current_level']);
        }
        if (isset($data['current_streak'])) {
            $stmt->bindParam(':current_streak', $data['current_streak']);
        }
        if (isset($data['performance_score'])) {
            $stmt->bindParam(':performance_score', $data['performance_score']);
        }
        if (isset($data['coins'])) {
            $stmt->bindParam(':coins', $data['coins']);
        }
        
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // Update coins
    public function updateCoins($user_id, $coins) {
        $query = 'UPDATE ' . $this->table . '
                SET coins = :coins,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :user_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':coins', $coins);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // Update level and performance
    public function updateProgress($user_id, $level, $performance_score, $streak = null) {
        $query = 'UPDATE ' . $this->table . '
                SET current_level = :current_level,
                    performance_score = :performance_score,
                    updated_at = CURRENT_TIMESTAMP';
        
        if ($streak !== null) {
            $query .= ', current_streak = :current_streak';
        }
        
        $query .= ' WHERE id = :user_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':current_level', $level);
        $stmt->bindParam(':performance_score', $performance_score);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($streak !== null) {
            $stmt->bindParam(':current_streak', $streak);
        }

        return $stmt->execute();
    }

    // Deactivate user account
    public function deactivate($user_id) {
        $query = 'UPDATE ' . $this->table . '
                SET is_active = 0,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :user_id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // Check if email exists
    public function emailExists($email) {
        $query = 'SELECT id FROM ' . $this->table . ' WHERE email = :email AND is_active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Check if nickname exists
    public function nicknameExists($nickname) {
        $query = 'SELECT id FROM ' . $this->table . ' WHERE nickname = :nickname AND is_active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nickname', $nickname);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Get user stats for dashboard
    public function getUserStats($user_id) {
        $query = 'SELECT 
                    current_level, 
                    current_streak, 
                    performance_score, 
                    coins,
                    created_at
                  FROM ' . $this->table . ' 
                  WHERE id = :user_id AND is_active = 1';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt;
    }
}

?>