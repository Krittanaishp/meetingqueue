<?php
require_once __DIR__ . '/api/config.php';

function mockBookingsLog(string $message): void
{
    if (!defined('MOCK_BOOKINGS_QUIET')) {
        echo $message . "\n";
    }
}

try {
    $pdo = getLocalDB();
    
    mockBookingsLog('Cleaning up old data...');
    $pdo->exec('DELETE FROM bookings');
    $pdo->exec('ALTER TABLE bookings AUTO_INCREMENT = 1');
    
    // Get all users
    $stmt = $pdo->query("SELECT id, dept_name FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($users)) {
        throw new RuntimeException('No users found. Please add users first.');
    }
    
    // Get all rooms
    $stmt = $pdo->query("SELECT id, capacity FROM rooms");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rooms)) {
        throw new RuntimeException('No rooms found.');
    }
    
    $titles = [
        'ประชุมประจำเดือน', 'อบรมวิจัย', 'ประชุมกลุ่มหัวหน้างาน ฝ่ายการพยาบาล',
        'ตรวจเท้าเบาหวาน', 'ประชุม กกบ.', 'ปฐมนิเทศพยาบาลใหม่',
        'ประชุมชี้แจงแนวทาง Palliative Care', 'ประชุมคณะกรรมการตรวจรับงานจ้าง',
        'ประชุม HA', 'กิจกรรม วันข้าราชการพลเรือน', 'พิจารณาผลการประกวดราคา',
        'กายภาพบำบัดฟื้นฟูสมรรถภาพปอด', 'ประชุมองค์กรแพทย์', 'ประชุม RLU',
        'ประชุม PCT med', 'กิจกรรมกลุ่มผู้สูงอายุ', 'ประชุม ENV',
        'ประชุมคณะกรรมการบริหาร รพ.', 'วางแผนกลยุทธ์ 2569', 'อบรมป้องกันอัคคีภัย',
        'ประชุมคุณภาพความปลอดภัย', 'อบรมความปลอดภัยด้านข้อมูล'
    ];
    
    $currentYear = (int) date('Y');
    $currentMonth = (int) date('n');
    $today = date('Y-m-d');
    
    mockBookingsLog("Generating mock data for Year {$currentYear}...");
    
    for ($month = 1; $month < $currentMonth; $month++) {
        $bookingsPerMonth = rand(15, 25);
        for ($i = 0; $i < $bookingsPerMonth; $i++) {
            $day = rand(1, 28);
            $date = sprintf("%04d-%02d-%02d", $currentYear, $month, $day);
            
            // Skip if it's today or future
            if ($date >= $today) continue;

            $hour = rand(8, 15);
            $duration = rand(1, 4);
            $start = "$date " . sprintf("%02d:00:00", $hour);
            $end = "$date " . sprintf("%02d:00:00", $hour + $duration);
            
            $room = $rooms[array_rand($rooms)];
            $user = $users[array_rand($users)];
            $status = (rand(0, 10) > 2) ? 'completed' : 'rejected';

            $pdo->prepare("INSERT INTO bookings (room_id, user_id, title, start_time, end_time, participants_count, department_name, status) 
                    VALUES (:room_id, :user_id, :title, :start_time, :end_time, :participants_count, :department_name, :status)")
                ->execute([
                    ':room_id' => $room['id'],
                    ':user_id' => $user['id'],
                    ':title' => $titles[array_rand($titles)],
                    ':start_time' => $start,
                    ':end_time' => $end,
                    ':participants_count' => rand(5, $room['capacity']),
                    ':department_name' => $user['dept_name'] ?: 'ทั่วไป',
                    ':status' => $status
                ]);
        }
    }

    // Insert TODAY's special bookings for demo
    mockBookingsLog("Generating special mock data for TODAY...");
    foreach ($rooms as $room) {
        // Active Meeting
        $start_active = date('Y-m-d H:i:s', strtotime('-45 minutes'));
        $end_active = date('Y-m-d H:i:s', strtotime('+75 minutes'));
        $user = $users[array_rand($users)];
        
        $pdo->prepare("INSERT INTO bookings (room_id, user_id, title, start_time, end_time, participants_count, department_name, status) 
                VALUES (:room_id, :user_id, :title, :start_time, :end_time, :participants_count, :department_name, 'approved')")
            ->execute([
                ':room_id' => $room['id'],
                ':user_id' => $user['id'],
                ':title' => "กำลังดำเนินอยู่: " . $titles[array_rand($titles)],
                ':start_time' => $start_active,
                ':end_time' => $end_active,
                ':participants_count' => rand(5, $room['capacity']),
                ':department_name' => $user['dept_name'] ?: 'ทั่วไป'
            ]);

        // Upcoming Meeting today
        $start_next = date('Y-m-d H:i:s', strtotime('+2 hours'));
        $end_next = date('Y-m-d H:i:s', strtotime('+4 hours'));
        $user = $users[array_rand($users)];

        $pdo->prepare("INSERT INTO bookings (room_id, user_id, title, start_time, end_time, participants_count, department_name, status) 
                VALUES (:room_id, :user_id, :title, :start_time, :end_time, :participants_count, :department_name, 'approved')")
            ->execute([
                ':room_id' => $room['id'],
                ':user_id' => $user['id'],
                ':title' => "รายการถัดไป: " . $titles[array_rand($titles)],
                ':start_time' => $start_next,
                ':end_time' => $end_next,
                ':participants_count' => rand(5, $room['capacity']),
                ':department_name' => $user['dept_name'] ?: 'ทั่วไป'
            ]);
    }

    for ($month = $currentMonth; $month <= 12; $month++) {
        $bookingsPerMonth = rand(10, 20);
        for ($i = 0; $i < $bookingsPerMonth; $i++) {
            $day = rand(1, 28);
            $date = sprintf("%04d-%02d-%02d", $currentYear, $month, $day);
            $hour = rand(8, 15);
            $duration = rand(1, 4);
            $start = "$date " . sprintf("%02d:00:00", $hour);
            $end = "$date " . sprintf("%02d:00:00", $hour + $duration);
            
            $room = $rooms[array_rand($rooms)];
            $user = $users[array_rand($users)];
            $status = (rand(0, 10) > 4) ? 'approved' : 'pending';

            $pdo->prepare("INSERT INTO bookings (room_id, user_id, title, start_time, end_time, participants_count, department_name, status) 
                    VALUES (:room_id, :user_id, :title, :start_time, :end_time, :participants_count, :department_name, :status)")
                ->execute([
                    ':room_id' => $room['id'],
                    ':user_id' => $user['id'],
                    ':title' => $titles[array_rand($titles)],
                    ':start_time' => $start,
                    ':end_time' => $end,
                    ':participants_count' => rand(5, $room['capacity']),
                    ':department_name' => $user['dept_name'] ?: 'ทั่วไป',
                    ':status' => $status
                ]);
        }
    }
    
    mockBookingsLog('Successfully generated full mock dataset!');
    
} catch (Exception $e) {
    if (defined('MOCK_BOOKINGS_QUIET')) {
        throw $e;
    }
    die('Error: ' . $e->getMessage() . "\n");
}
