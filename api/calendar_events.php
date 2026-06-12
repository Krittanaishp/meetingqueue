<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

use App\Repository\BookingRepository;

$repo = new BookingRepository();

try {
    $filters = [
        'start' => $_GET['start'] ?? null,
        'end' => $_GET['end'] ?? null,
        'room_id' => $_GET['room_id'] ?? null,
        'exclude_status' => 'cancelled'
    ];
    
    $events = $repo->getAll($filters);
    
    // Fetch rooms sorted by capacity descending for color mapping
    $rooms = \App\Core\Database::getInstance()->getConnection()->query("SELECT id FROM rooms ORDER BY capacity DESC")->fetchAll(PDO::FETCH_COLUMN);
    
    // Rainbow colors: Purple, Indigo, Blue, Green, Yellow, Orange, Red
    $rainbow_colors = [
        '#a855f7', // ม่วง
        '#6366f1', // คราม
        '#3b82f6', // น้ำเงิน
        '#10b981', // เขียว
        '#eab308', // เหลือง
        '#f97316', // แสด
        '#ef4444', // แดง
        '#ec4899', // ชมพู (fallback)
    ];
    
    $room_colors = [];
    foreach ($rooms as $index => $room_id) {
        $room_colors[$room_id] = $rainbow_colors[$index % count($rainbow_colors)];
    }
    // External meetings take the next available color
    $external_color = $rainbow_colors[count($rooms) % count($rainbow_colors)];
    
    $formattedEvents = array_map(function($e) use ($room_colors, $external_color) {
        $roomId = $e['room_id'];
        $baseColor = $roomId && isset($room_colors[$roomId]) ? $room_colors[$roomId] : $external_color;
        
        // If pending, make it slightly faded/different if we wanted, but we'll stick to the base color
        // so it matches the room strictly.
        $color = $baseColor;
        
        return [
            'id' => $e['id'],
            'resourceId' => $roomId ?: 'external',
            'title' => ($e['room_name'] ?? 'ภายนอก') . ': ' . $e['title'],
            'start' => $e['start_time'],
            'end' => $e['end_time'],
            'backgroundColor' => $color,
            'borderColor' => $color,
            'className' => 'event-status-' . $e['status'], // to allow custom CSS based on status later if needed
            'extendedProps' => [
                'status' => $e['status'],
                'room_id' => $roomId,
                'room' => $e['room_name'] ?? 'ภายนอกสถานที่',
                'user' => $e['first_name'] . ' ' . ($e['last_name'] ?? ''),
                'original_title' => $e['title'],
                'participants' => $e['participants_count'] ?? 0
            ]
        ];
    }, $events);
    
    header('Content-Type: application/json');
    echo json_encode($formattedEvents);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
