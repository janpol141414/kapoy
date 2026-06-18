<?php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $conn;
    private $table = 'notifications';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $title, $message, $type = 'system', $link = '') {
        $query = "INSERT INTO " . $this->table . " (user_id, title, message, type, link)
                  VALUES (:user_id, :title, :message, :type, :link)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':link', $link);
        return $stmt->execute();
    }

    public function getByUserId($user_id, $limit = 20) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function markAllRead($user_id) {
        $query = "UPDATE " . $this->table . " SET is_read = 1 WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    public function markRead($id) {
        $query = "UPDATE " . $this->table . " SET is_read = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
