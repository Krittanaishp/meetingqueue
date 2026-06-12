<?php
namespace App\Repository;

use App\Core\Database;

class RoomRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($status = null) {
        $sql = "SELECT * FROM rooms";
        if ($status) {
            $sql .= " WHERE status = :status";
        }
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        if ($status) {
            $stmt->execute([':status' => $status]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function update($id, array $data) {
        $sql = "UPDATE rooms SET name = :name, capacity = :capacity, location = :location, status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':capacity' => $data['capacity'],
            ':location' => $data['location'] ?? '',
            ':status' => $data['status'] ?? 'available'
        ]);
    }

    public function create(array $data) {
        $sql = "INSERT INTO rooms (name, capacity, location, status) VALUES (:name, :capacity, :location, :status)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':capacity' => $data['capacity'],
            ':location' => $data['location'] ?? '',
            ':status' => $data['status'] ?? 'available'
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
