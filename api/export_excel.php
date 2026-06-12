<?php
require_once 'config.php';
use App\Repository\BookingRepository;

if (!isset($_SESSION['user_id']) || ($_SESSION['user_data']['role'] ?? '') !== 'admin') {
    exit('Unauthorized');
}

$repo = new BookingRepository();

// Get filters from URL and clean them
$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['room']))   $filters['room_id'] = $_GET['room'];
if (!empty($_GET['from']))   $filters['start'] = $_GET['from'] . ' 00:00:00';
if (!empty($_GET['to']))     $filters['end'] = $_GET['to'] . ' 23:59:59';
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

$bookings = $repo->getAll($filters);

// Filename
$filename = "meeting_report_" . date('Ymd_His') . ".xls";

// Header for Excel
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output table format that Excel understands
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Thai characters
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr style="background-color: #f2f2f2; color: #000000; font-weight: bold;">
            <th>ID</th>
            <th>หัวข้อการประชุม</th>
            <th>ผู้จอง</th>
            <th>ฝ่าย/หน่วยงาน</th>
            <th>ห้องประชุม</th>
            <th>วันที่เริ่มต้น</th>
            <th>เวลาเริ่มต้น</th>
            <th>วันที่สิ้นสุด</th>
            <th>เวลาสิ้นสุด</th>
            <th>สถานะ</th>
            <th>วันที่บันทึก</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><?php echo $b['id']; ?></td>
                <td><?php echo htmlspecialchars($b['title']); ?></td>
                <td><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></td>
                <td><?php echo htmlspecialchars($b['department_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($b['room_name'] ?? 'ภายนอก'); ?></td>
                <td><?php echo date('d/m/Y', strtotime($b['start_time'])); ?></td>
                <td><?php echo date('H:i', strtotime($b['start_time'])); ?></td>
                <td><?php echo date('d/m/Y', strtotime($b['end_time'])); ?></td>
                <td><?php echo date('H:i', strtotime($b['end_time'])); ?></td>
                <td><?php 
                    $status_th = [
                        'pending' => 'รออนุมัติ',
                        'approved' => 'อนุมัติแล้ว',
                        'rejected' => 'ไม่อนุมัติ',
                        'completed' => 'เสร็จสิ้น',
                        'cancelled' => 'ยกเลิก'
                    ];
                    echo $status_th[$b['status']] ?? $b['status']; 
                ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($b['created_at'])); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
