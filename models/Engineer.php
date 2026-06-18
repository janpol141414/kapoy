<?php
require_once __DIR__ . '/../config/database.php';

class Engineer {
    private $conn;
    private $table = 'engineers';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($filters = []) {
        $query = "SELECT e.*, u.name, u.email, u.phone, u.profile_photo, u.address, u.bio as user_bio,
                         c.name as company_name, c.logo as company_logo
                  FROM " . $this->table . " e
                  JOIN users u ON e.user_id = u.id
                  LEFT JOIN companies c ON e.company_id = c.id
                  WHERE u.is_active = 1";

        if (!empty($filters['availability'])) {
            $query .= " AND e.availability_status = :availability";
        }
        if (!empty($filters['company_id'])) {
            $query .= " AND e.company_id = :company_id";
        }
        if (!empty($filters['search'])) {
            $query .= " AND (u.name LIKE :search OR e.specialization LIKE :search OR c.name LIKE :search)";
        }

        $query .= " ORDER BY e.rating DESC";

        $stmt = $this->conn->prepare($query);

        if (!empty($filters['availability'])) {
            $stmt->bindParam(':availability', $filters['availability']);
        }
        if (!empty($filters['company_id'])) {
            $stmt->bindParam(':company_id', $filters['company_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $stmt->bindParam(':search', $search);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT e.*, u.name, u.email, u.phone, u.profile_photo, u.address, u.bio as user_bio, u.created_at as member_since,
                         c.name as company_name, c.logo as company_logo, c.address as company_address
                  FROM " . $this->table . " e
                  JOIN users u ON e.user_id = u.id
                  LEFT JOIN companies c ON e.company_id = c.id
                  WHERE e.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUserId($user_id) {
        $query = "SELECT e.*, u.name, u.email, u.phone, u.profile_photo, u.address,
                         c.name as company_name
                  FROM " . $this->table . " e
                  JOIN users u ON e.user_id = u.id
                  LEFT JOIN companies c ON e.company_id = c.id
                  WHERE e.user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, company_id, license_number, specialization, experience_years, availability_status, bio, skills, certifications, hourly_rate)
                  VALUES (:user_id, :company_id, :license_number, :specialization, :experience_years, :availability_status, :bio, :skills, :certifications, :hourly_rate)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
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

    public function updateAvailability($engineer_id, $status) {
        $query = "UPDATE " . $this->table . " SET availability_status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $engineer_id);
        return $stmt->execute();
    }

    public function updateRating($engineer_id) {
        $query = "UPDATE " . $this->table . " e
                  SET e.rating = (SELECT AVG(f.rating) FROM feedback f WHERE f.engineer_id = e.id),
                      e.total_reviews = (SELECT COUNT(*) FROM feedback f WHERE f.engineer_id = e.id)
                  WHERE e.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $engineer_id);
        return $stmt->execute();
    }

    public function getReviews($engineer_id) {
        $query = "SELECT f.*, u.name as client_name, u.profile_photo as client_photo
                  FROM feedback f
                  JOIN users u ON f.client_id = u.id
                  WHERE f.engineer_id = :engineer_id AND f.is_public = 1
                  ORDER BY f.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':engineer_id', $engineer_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableSlots($engineer_id, $month = null, $year = null) {
        $query = "SELECT * FROM schedules 
                  WHERE engineer_id = :engineer_id AND is_available = 1 AND date >= CURDATE()";
        if ($month && $year) {
            $query .= " AND MONTH(date) = :month AND YEAR(date) = :year";
        }
        $query .= " ORDER BY date, start_time";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':engineer_id', $engineer_id);
        if ($month && $year) {
            $stmt->bindParam(':month', $month);
            $stmt->bindParam(':year', $year);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
