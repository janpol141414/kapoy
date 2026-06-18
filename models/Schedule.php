<?php
require_once __DIR__ . '/../config/database.php';

class Schedule {
    private $conn;
    private $table = 'schedules';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (engineer_id, date, start_time, end_time, is_available, slot_type, notes)
                  VALUES (:engineer_id, :date, :start_time, :end_time, :is_available, :slot_type, :notes)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function getByEngineerId($engineer_id, $month = null, $year = null) {
        $query = "SELECT * FROM " . $this->table . " WHERE engineer_id = :engineer_id";
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

    public function getAvailableDates($engineer_id, $month, $year) {
        $query = "SELECT date, is_available, slot_type, start_time, end_time
                  FROM " . $this->table . " 
                  WHERE engineer_id = :engineer_id 
                  AND MONTH(date) = :month 
                  AND YEAR(date) = :year
                  ORDER BY date";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':engineer_id', $engineer_id);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableSlotsByDate($engineer_id, $date) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE engineer_id = :engineer_id AND date = :date AND is_available = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':engineer_id', $engineer_id);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        // Always touch updated_at so sync polling detects the change
        if (!isset($data['updated_at'])) {
            $fields[] = "updated_at = NOW()";
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

    public function getAll() {
        $query = "SELECT s.*, u.name as engineer_name, e.specialization
                  FROM " . $this->table . " s
                  JOIN engineers e ON s.engineer_id = e.id
                  JOIN users u ON e.user_id = u.id
                  ORDER BY s.date DESC, s.start_time";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
