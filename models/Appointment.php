<?php
require_once __DIR__ . '/../config/database.php';

class Appointment {
    private $conn;
    private $table = 'appointments';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $data['confirmation_code'] = 'CONF-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $query = "INSERT INTO " . $this->table . " 
                  (client_id, engineer_id, schedule_id, service_type, location, appointment_date, appointment_time, notes, total_amount, ai_suggested, confirmation_code)
                  VALUES (:client_id, :engineer_id, :schedule_id, :service_type, :location, :appointment_date, :appointment_time, :notes, :total_amount, :ai_suggested, :confirmation_code)";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute($data)) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getById($id) {
        $query = "SELECT a.*, 
                         u.name as client_name, u.email as client_email, u.phone as client_phone, u.profile_photo as client_photo,
                         eu.name as engineer_name, eu.email as engineer_email, eu.phone as engineer_phone, eu.profile_photo as engineer_photo,
                         e.specialization, e.license_number, e.company_id,
                         c.name as company_name
                  FROM " . $this->table . " a
                  JOIN users u ON a.client_id = u.id
                  JOIN engineers e ON a.engineer_id = e.id
                  JOIN users eu ON e.user_id = eu.id
                  LEFT JOIN companies c ON e.company_id = c.id
                  WHERE a.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByClientId($client_id) {
        $query = "SELECT a.*, 
                         eu.name as engineer_name, eu.profile_photo as engineer_photo,
                         eu.id as engineer_user_id,
                         e.specialization, e.rating as engineer_rating
                  FROM " . $this->table . " a
                  JOIN engineers e ON a.engineer_id = e.id
                  JOIN users eu ON e.user_id = eu.id
                  WHERE a.client_id = :client_id
                  ORDER BY a.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':client_id', $client_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByEngineerId($engineer_id) {
        $query = "SELECT a.*, 
                         u.name as client_name, u.email as client_email, u.phone as client_phone, u.profile_photo as client_photo
                  FROM " . $this->table . " a
                  JOIN users u ON a.client_id = u.id
                  WHERE a.engineer_id = :engineer_id
                  ORDER BY a.appointment_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':engineer_id', $engineer_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($filters = []) {
        $query = "SELECT a.*, 
                         u.name as client_name, u.profile_photo as client_photo,
                         eu.name as engineer_name, eu.profile_photo as engineer_photo
                  FROM " . $this->table . " a
                  JOIN users u ON a.client_id = u.id
                  JOIN engineers e ON a.engineer_id = e.id
                  JOIN users eu ON e.user_id = eu.id
                  WHERE 1=1";

        if (!empty($filters['status'])) {
            $query .= " AND a.status = :status";
        }
        if (!empty($filters['date'])) {
            $query .= " AND a.appointment_date = :date";
        }

        $query .= " ORDER BY a.created_at DESC";

        $stmt = $this->conn->prepare($query);
        if (!empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        if (!empty($filters['date'])) {
            $stmt->bindParam(':date', $filters['date']);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getProgressUpdates($appointment_id) {
        $query = "SELECT pu.*, eu.name as engineer_name, eu.profile_photo as engineer_photo
                  FROM progress_updates pu
                  JOIN engineers e ON pu.engineer_id = e.id
                  JOIN users eu ON e.user_id = eu.id
                  WHERE pu.appointment_id = :appointment_id
                  ORDER BY pu.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':appointment_id', $appointment_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addProgressUpdate($data) {
        $query = "INSERT INTO progress_updates (appointment_id, engineer_id, status, description, photo)
                  VALUES (:appointment_id, :engineer_id, :status, :description, :photo)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                  FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function autoAssignEngineer($service_type, $date, $time) {
        $query = "SELECT e.id, u.name, e.rating, e.availability_status
                  FROM engineers e
                  JOIN users u ON e.user_id = u.id
                  JOIN schedules s ON e.id = s.engineer_id
                  WHERE e.availability_status = 'available'
                  AND s.date = :date
                  AND s.is_available = 1
                  AND s.start_time <= :time
                  AND s.end_time >= :time
                  AND e.id NOT IN (
                      SELECT engineer_id FROM appointments 
                      WHERE appointment_date = :date2 AND appointment_time = :time2 
                      AND status NOT IN ('cancelled')
                  )
                  ORDER BY e.rating DESC
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':date2', $date);
        $stmt->bindParam(':time2', $time);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
