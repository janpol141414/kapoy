<?php
require_once __DIR__ . '/../config/database.php';

class Company {
    private $conn;
    private $table = 'companies';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($search = '') {
        $query = "SELECT c.*, 
                         COUNT(e.id) as engineer_count,
                         AVG(e.rating) as avg_rating
                  FROM " . $this->table . " c
                  LEFT JOIN engineers e ON c.id = e.company_id
                  WHERE c.is_active = 1";
        if (!empty($search)) {
            $query .= " AND (c.name LIKE :search OR c.services LIKE :search OR c.address LIKE :search)";
        }
        $query .= " GROUP BY c.id ORDER BY c.name";
        $stmt = $this->conn->prepare($query);
        if (!empty($search)) {
            $s = '%' . $search . '%';
            $stmt->bindParam(':search', $s);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEngineers($company_id) {
        $query = "SELECT e.*, u.name, u.email, u.phone, u.profile_photo
                  FROM engineers e
                  JOIN users u ON e.user_id = u.id
                  WHERE e.company_id = :company_id AND u.is_active = 1
                  ORDER BY e.rating DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (name, description, logo, address, phone, email, website, services)
                  VALUES (:name, :description, :logo, :address, :phone, :email, :website, :services)";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute($data)) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
