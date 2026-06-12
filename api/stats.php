<?php
require_once 'config.php';

use App\Repository\BookingRepository;

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$repo = new BookingRepository();
$type = $_GET['type'] ?? 'room_usage';

try {
    if ($type === 'room_usage') {
        $stats = $repo->getRoomUsageStats();
    } elseif ($type === 'department') {
        $stats = $repo->getDepartmentStats();
    } else {
        $stats = [];
    }

    jsonResponse([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
