<?php
class StudyGroup {
    private $conn;
    private $table = 'study_groups';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAvailableGroups($user_id) {
        $query = "SELECT sg.*, 
                  CASE WHEN sgm.user_id IS NOT NULL THEN 1 ELSE 0 END as is_member
                  FROM study_groups sg
                  LEFT JOIN study_group_members sgm ON sg.id = sgm.group_id AND sgm.user_id = :user_id
                  WHERE sg.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGroupMembers($group_id) {
        $query = "SELECT u.id, u.nickname, u.avatar_url
                  FROM study_group_members sgm
                  INNER JOIN users u ON sgm.user_id = u.id
                  WHERE sgm.group_id = :group_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function joinGroup($user_id, $group_id) {
        // Check if already a member
        $checkQuery = "SELECT id FROM study_group_members 
                      WHERE user_id = :user_id AND group_id = :group_id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':user_id', $user_id);
        $checkStmt->bindParam(':group_id', $group_id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() == 0) {
            $query = "INSERT INTO study_group_members (user_id, group_id) 
                      VALUES (:user_id, :group_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':group_id', $group_id);
            return $stmt->execute();
        }
        return false;
    }
}
?>