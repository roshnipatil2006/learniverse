<?php
class Friend {
    private $conn;
    private $table = 'friendships';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getFriends($user_id) {
        $query = "SELECT u.id, u.nickname, u.avatar_url, u.is_active as is_online
                  FROM users u
                  INNER JOIN friendships f ON (f.user_id = u.id OR f.friend_id = u.id)
                  WHERE (f.user_id = :user_id OR f.friend_id = :user_id) 
                  AND u.id != :user_id AND f.status = 'accepted'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFriendRequests($user_id) {
        $query = "SELECT f.id as friendship_id, u.id, u.nickname, u.avatar_url
                  FROM friendships f
                  INNER JOIN users u ON f.user_id = u.id
                  WHERE f.friend_id = :user_id AND f.status = 'pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOnlineFriends($user_id) {
        $query = "SELECT u.id FROM users u
                  INNER JOIN friendships f ON (f.user_id = u.id OR f.friend_id = u.id)
                  WHERE (f.user_id = :user_id OR f.friend_id = :user_id) 
                  AND u.id != :user_id AND f.status = 'accepted' AND u.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendRequest($user_id, $friend_email) {
        // Find user by email
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $friend_email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $friend = $stmt->fetch(PDO::FETCH_ASSOC);
            $friend_id = $friend['id'];
            
            // Check if request already exists
            $checkQuery = "SELECT id FROM friendships 
                          WHERE (user_id = :user_id AND friend_id = :friend_id) 
                          OR (user_id = :friend_id AND friend_id = :user_id)";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $user_id);
            $checkStmt->bindParam(':friend_id', $friend_id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() == 0) {
                $insertQuery = "INSERT INTO friendships (user_id, friend_id, status) 
                               VALUES (:user_id, :friend_id, 'pending')";
                $insertStmt = $this->conn->prepare($insertQuery);
                $insertStmt->bindParam(':user_id', $user_id);
                $insertStmt->bindParam(':friend_id', $friend_id);
                return $insertStmt->execute();
            }
        }
        return false;
    }

    public function acceptRequest($friendship_id) {
        $query = "UPDATE friendships SET status = 'accepted' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $friendship_id);
        return $stmt->execute();
    }

    public function declineRequest($friendship_id) {
        $query = "DELETE FROM friendships WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $friendship_id);
        return $stmt->execute();
    }
}
?>