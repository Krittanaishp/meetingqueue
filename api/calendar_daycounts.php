<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

try {
    $db = \App\Core\Database::getInstance()->getConnection();

    $start = $_GET['start'] ?? date('Y-m-01');
    $end   = $_GET['end']   ?? date('Y-m-t');
    $room_id = $_GET['room_id'] ?? null;

    $room_clause = "";
    $params = [':start' => $start, ':end' => $end . ' 23:59:59'];

    if (!empty($room_id) && $room_id !== 'all') {
        if (strpos($room_id, ',') !== false) {
            $ids = array_map('intval', explode(',', $room_id));
            $placeholders = [];
            foreach ($ids as $idx => $id) {
                $key = ":room_id_" . $idx;
                $placeholders[] = $key;
                $params[$key] = $id;
            }
            $room_clause = " AND room_id IN (" . implode(',', $placeholders) . ")";
        } else {
            $room_clause = " AND room_id = :room_id";
            $params[':room_id'] = intval($room_id);
        }
    }

    $sql = "
        SELECT
            DATE(start_time) AS day,
            COUNT(*) AS total,
            SUM(status IN ('approved', 'completed')) AS approved,
            SUM(status = 'pending')   AS pending,
            SUM(status = 'rejected')  AS rejected
        FROM bookings
        WHERE start_time >= :start
          AND start_time <= :end
          AND status != 'cancelled'
          $room_clause
        GROUP BY DATE(start_time)
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Index by date string
    $result = [];
    foreach ($rows as $r) {
        $result[$r['day']] = [
            'total'    => (int)$r['total'],
            'approved' => (int)$r['approved'],
            'pending'  => (int)$r['pending'],
            'rejected' => (int)$r['rejected'],
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
