<?php
require_once __DIR__ . '/../config/database.php';

class Payment {
    private $conn;
    private $table = 'payments';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (appointment_id, client_id, amount, payment_method, reference_number, proof_image)
                  VALUES (:appointment_id, :client_id, :amount, :payment_method, :reference_number, :proof_image)";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute($data)) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getAll($filters = []) {
        $query = "SELECT p.*, 
                         u.name as client_name, u.email as client_email, u.profile_photo as client_photo,
                         a.service_type, a.appointment_date, a.confirmation_code,
                         eu.name as engineer_name
                  FROM " . $this->table . " p
                  JOIN users u ON p.client_id = u.id
                  JOIN appointments a ON p.appointment_id = a.id
                  JOIN engineers e ON a.engineer_id = e.id
                  JOIN users eu ON e.user_id = eu.id
                  WHERE 1=1";

        if (!empty($filters['status'])) {
            $query .= " AND p.status = :status";
        }

        $query .= " ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        if (!empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByClientId($client_id) {
        $query = "SELECT p.*, a.service_type, a.appointment_date, a.confirmation_code, eu.name as engineer_name
                  FROM " . $this->table . " p
                  JOIN appointments a ON p.appointment_id = a.id
                  JOIN engineers e ON a.engineer_id = e.id
                  JOIN users eu ON e.user_id = eu.id
                  WHERE p.client_id = :client_id
                  ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':client_id', $client_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT p.*, u.name as client_name, a.service_type, a.confirmation_code
                  FROM " . $this->table . " p
                  JOIN users u ON p.client_id = u.id
                  JOIN appointments a ON p.appointment_id = a.id
                  WHERE p.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verify($id, $status, $admin_id, $notes = '') {
        $query = "UPDATE " . $this->table . " 
                  SET status = :status, verified_by = :admin_id, verified_at = NOW(), admin_notes = :notes, updated_at = NOW()
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'verified' THEN amount ELSE 0 END) as total_revenue
                  FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
