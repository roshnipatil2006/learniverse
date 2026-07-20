<?php
class Game {
    private $conn;
    private $table_sessions = 'game_sessions';
    private $table_leaderboard = 'leaderboard';
    private $table_achievements = 'user_achievements';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Save game session
    public function saveGameSession($user_id, $game_type, $subject, $level, $score, $duration) {
        $query = "INSERT INTO " . $this->table_sessions . " 
                  (user_id, game_type, subject, level, score, duration) 
                  VALUES (:user_id, :game_type, :subject, :level, :score, :duration)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':game_type', $game_type);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':score', $score);
        $stmt->bindParam(':duration', $duration);
        
        if ($stmt->execute()) {
            $this->updateLeaderboard($user_id);
            $this->checkAchievements($user_id, $game_type, $score, $level);
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Update leaderboard
    private function updateLeaderboard($user_id) {
        // Calculate total score and games played
        $query = "INSERT INTO " . $this->table_leaderboard . " 
                  (user_id, total_score, games_played, average_score, last_played) 
                  SELECT 
                      user_id,
                      SUM(score) as total_score,
                      COUNT(*) as games_played,
                      AVG(score) as average_score,
                      NOW()
                  FROM " . $this->table_sessions . " 
                  WHERE user_id = :user_id
                  ON DUPLICATE KEY UPDATE 
                      total_score = VALUES(total_score),
                      games_played = VALUES(games_played),
                      average_score = VALUES(average_score),
                      last_played = VALUES(last_played)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    // Check and unlock achievements
    private function checkAchievements($user_id, $game_type, $score, $level) {
        $achievements = [
            // Score-based achievements
            ['first_game', 'First Game', 'Complete your first game', "SELECT COUNT(*) FROM game_sessions WHERE user_id = :user_id"],
            ['score_1000', 'Score Master', 'Score 1000 points in a single game', "SELECT MAX(score) FROM game_sessions WHERE user_id = :user_id AND score >= 1000"],
            ['level_5', 'Level Expert', 'Reach level 5 in any game', "SELECT MAX(level) FROM game_sessions WHERE user_id = :user_id AND level >= 5"],
            
            // Game-specific achievements
            ['memory_master', 'Memory Master', 'Complete all memory game levels', "SELECT COUNT(DISTINCT level) FROM game_sessions WHERE user_id = :user_id AND game_type = 'memory' AND level >= 6"],
            ['wheel_expert', 'Wheel Expert', 'Play 10 wheel games', "SELECT COUNT(*) FROM game_sessions WHERE user_id = :user_id AND game_type = 'wheel'"],
            
            // Consistency achievements
            ['consistent_player', 'Consistent Player', 'Play 20 games total', "SELECT COUNT(*) FROM game_sessions WHERE user_id = :user_id"],
            ['high_scorer', 'High Scorer', 'Achieve total score of 5000', "SELECT SUM(score) FROM game_sessions WHERE user_id = :user_id"]
        ];

        foreach ($achievements as $achievement) {
            $check_query = $achievement[3];
            $stmt = $this->conn->prepare($check_query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_COLUMN);

            if ($result && $result > 0) {
                $this->unlockAchievement($user_id, $achievement[0], $achievement[1], $achievement[2]);
            }
        }
    }

    // Unlock achievement
    private function unlockAchievement($user_id, $type, $name, $description) {
        // Check if already unlocked
        $check_query = "SELECT id FROM " . $this->table_achievements . " 
                       WHERE user_id = :user_id AND achievement_type = :type";
        $stmt = $this->conn->prepare($check_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':type', $type);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            $insert_query = "INSERT INTO " . $this->table_achievements . " 
                           (user_id, achievement_type, achievement_name, achievement_description) 
                           VALUES (:user_id, :type, :name, :description)";
            $stmt = $this->conn->prepare($insert_query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        }
        return false;
    }

    // Get leaderboard data
    public function getLeaderboard($limit = 50) {
        $query = "SELECT 
                    l.*,
                    u.nickname,
                    u.avatar_url,
                    (@rank := @rank + 1) as rank
                  FROM " . $this->table_leaderboard . " l
                  JOIN users u ON l.user_id = u.id
                  CROSS JOIN (SELECT @rank := 0) r
                  WHERE u.is_active = true
                  ORDER BY l.total_score DESC, l.average_score DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user's rank
    public function getUserRank($user_id) {
        $query = "SELECT rank FROM (
                    SELECT 
                        user_id,
                        (@rank := @rank + 1) as rank
                    FROM leaderboard l
                    CROSS JOIN (SELECT @rank := 0) r
                    ORDER BY total_score DESC, average_score DESC
                  ) ranked
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['rank'] : null;
    }

    // Get user's game statistics
    public function getUserStats($user_id) {
        $query = "SELECT 
                    game_type,
                    COUNT(*) as games_played,
                    MAX(score) as high_score,
                    AVG(score) as average_score,
                    MAX(level) as max_level,
                    SUM(duration) as total_play_time
                  FROM " . $this->table_sessions . " 
                  WHERE user_id = :user_id
                  GROUP BY game_type";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>