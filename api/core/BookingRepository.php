<?php
namespace App\Repository;

use App\Core\Database;
use PDO;

class BookingRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(array $filters = []) {
        $sql = "SELECT b.*, r.name as room_name, r.location, u.first_name, u.last_name, u.emp_code, u.dept_name as user_dept,
                GROUP_CONCAT(DISTINCT CONCAT(bi.id, ':', bi.image_path) SEPARATOR '|') as image_list
                FROM bookings b 
                LEFT JOIN rooms r ON b.room_id = r.id 
                JOIN users u ON b.user_id = u.id 
                LEFT JOIN booking_images bi ON b.id = bi.booking_id
                WHERE 1=1";
        $params = [];
        
        // Search Filter
        if (!empty($filters['search'])) {
            $sql .= " AND (b.title LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.emp_code LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Trash logic
        if (!empty($filters['only_trashed'])) {
            $sql .= " AND b.deleted_at IS NOT NULL";
        } else {
            $sql .= " AND b.deleted_at IS NULL";
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND b.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['booking_id'])) {
            $sql .= " AND b.id = :booking_id";
            $params[':booking_id'] = $filters['booking_id'];
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $sql .= " AND b.status IN ('pending', 'approved')";
            } else {
                $sql .= " AND b.status = :status";
                $params[':status'] = $filters['status'];
            }
        }

        if (!empty($filters['room_id']) && $filters['room_id'] !== 'all') {
            if (strpos($filters['room_id'], ',') !== false) {
                $ids = array_map('intval', explode(',', $filters['room_id']));
                $placeholders = [];
                foreach ($ids as $idx => $id) {
                    $key = ":room_id_" . $idx;
                    $placeholders[] = $key;
                    $params[$key] = $id;
                }
                $sql .= " AND b.room_id IN (" . implode(',', $placeholders) . ")";
            } else {
                $sql .= " AND b.room_id = :room_id";
                $params[':room_id'] = $filters['room_id'];
            }
        }

        if (!empty($filters['start']) && !empty($filters['end'])) {
            $sql .= " AND b.start_time >= :start AND b.end_time <= :end";
            $params[':start'] = $filters['start'];
            $params[':end'] = $filters['end'];
        }

        if (!empty($filters['month']) && !empty($filters['year'])) {
            $sql .= " AND MONTH(b.start_time) = :month AND YEAR(b.start_time) = :year";
            $params[':month'] = $filters['month'];
            $params[':year'] = $filters['year'];
        }

        if (!empty($filters['upcoming'])) {
            $sql .= " AND b.start_time >= NOW() AND b.status IN ('approved', 'pending')";
        }

        if (!empty($filters['upcoming'])) {
            $sql .= " GROUP BY b.id ORDER BY b.start_time ASC";
        } else {
            $sql .= " GROUP BY b.id ORDER BY b.start_time DESC";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        foreach ($results as &$r) {
            if (isset($r['first_name'])) $r['first_name'] = cleanName($r['first_name']);
            if (isset($r['last_name'])) $r['last_name'] = cleanName($r['last_name']);
        }
        
        return $results;
    }

    public function checkConflicts($roomId, $startTime, $endTime) {
        $sql = "SELECT COUNT(*) FROM bookings 
                WHERE room_id = :room_id 
                AND deleted_at IS NULL
                AND status IN ('pending', 'approved')
                AND (start_time < :end_time AND end_time > :start_time)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':room_id' => $roomId,
            ':start_time' => $startTime,
            ':end_time' => $endTime
        ]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data) {
        // Run conflict check if not external
        if (!($data['is_external'] ?? false) && !empty($data['room_id'])) {
            if ($this->checkConflicts($data['room_id'], $data['start_time'], $data['end_time'])) {
                throw new \Exception('ห้องนี้ถูกจองไปแล้วในช่วงเวลาดังกล่าว', 409);
            }
        }

        $sql = "INSERT INTO bookings (room_id, user_id, title, description, start_time, end_time, participants_count, phone, attachment_path, department_name, is_external, external_org, status) 
                VALUES (:room_id, :user_id, :title, :description, :start_time, :end_time, :participants_count, :phone, :attachment_path, :department_name, :is_external, :external_org, 'pending')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':room_id' => $data['room_id'] ?? null,
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? '',
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'],
            ':participants_count' => $data['participants_count'] ?? 0,
            ':phone' => $data['phone'] ?? '',
            ':attachment_path' => $data['attachment_path'] ?? null,
            ':department_name' => $data['department_name'] ?? null,
            ':is_external' => $data['is_external'] ?? 0,
            ':external_org' => $data['external_org'] ?? null
        ]);
    }

    public function updateStatus($id, $status) {
        $sql = "UPDATE bookings SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status
        ]);
    }

    public function delete($id) {
        $sql = "UPDATE bookings SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function restore($id) {
        $sql = "UPDATE bookings SET deleted_at = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function permanentDelete($id) {
        $sql = "DELETE FROM bookings WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function getRoomUsageStats($startDate = null, $endDate = null, $booker = '', $roomId = '') {
        $sql = "SELECT r.name, COUNT(b.id) as total_bookings, SUM(TIMESTAMPDIFF(MINUTE, b.start_time, b.end_time)) / 60 as total_hours 
                FROM rooms r 
                LEFT JOIN bookings b ON r.id = b.room_id AND b.deleted_at IS NULL";
        
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " AND DATE(b.start_time) >= :start_date AND DATE(b.start_time) <= :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        } elseif ($startDate) {
            $sql .= " AND DATE(b.start_time) = :date";
            $params[':date'] = $startDate;
        }

        if (!empty($booker)) {
            $sql .= " AND (b.title LIKE :booker OR EXISTS (SELECT 1 FROM users u WHERE b.user_id = u.id AND (u.first_name LIKE :booker OR u.last_name LIKE :booker OR u.emp_code LIKE :booker)))";
            $params[':booker'] = '%' . $booker . '%';
        }

        if (!empty($roomId) && $roomId !== 'all') {
            $sql .= " AND b.room_id = :room_id";
            $params[':room_id'] = $roomId;
        }

        $sql .= " GROUP BY r.id, r.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDepartmentStats($startDate = null, $endDate = null, $booker = '', $roomId = '') {
        $sql = "SELECT b.department_name, COUNT(b.id) as total_bookings 
                FROM bookings b
                WHERE b.department_name IS NOT NULL AND b.deleted_at IS NULL";
        
        $params = [];
        if ($startDate && $endDate) {
            $sql .= " AND DATE(b.start_time) >= :start_date AND DATE(b.start_time) <= :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        } elseif ($startDate) {
            $sql .= " AND DATE(b.start_time) = :date";
            $params[':date'] = $startDate;
        }

        if (!empty($booker)) {
            $sql .= " AND (b.title LIKE :booker OR EXISTS (SELECT 1 FROM users u WHERE b.user_id = u.id AND (u.first_name LIKE :booker OR u.last_name LIKE :booker OR u.emp_code LIKE :booker)))";
            $params[':booker'] = '%' . $booker . '%';
        }

        if (!empty($roomId) && $roomId !== 'all') {
            $sql .= " AND b.room_id = :room_id";
            $params[':room_id'] = $roomId;
        }

        $sql .= " GROUP BY b.department_name ORDER BY total_bookings DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
