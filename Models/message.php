<?php
class Message {
    private $conn;
    private $table = 'messages';

    public $id;
    public $sender_id;
    public $receiver_id;
    public $message;
    public $sent_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function send() {
        $query = "INSERT INTO messages (sender_id, receiver_id, message) 
                  VALUES (:sender_id, :receiver_id, :message)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sender_id', $this->sender_id);
        $stmt->bindParam(':receiver_id', $this->receiver_id);
        $stmt->bindParam(':message', $this->message);
        
        return $stmt->execute();
    }

    public function getMessages($user1_id, $user2_id) {
        $query = "SELECT m.*, u.nickname as sender_name 
                  FROM messages m
                  INNER JOIN users u ON m.sender_id = u.id
                  WHERE (sender_id = :user1_id AND receiver_id = :user2_id) 
                  OR (sender_id = :user2_id AND receiver_id = :user1_id)
                  ORDER BY m.sent_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1_id', $user1_id);
        $stmt->bindParam(':user2_id', $user2_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>