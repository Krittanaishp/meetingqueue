<?php
require_once 'config.php';

/**
 * Bookings API
 * GET: Fetch bookings
 * POST: Create booking (Standard or External)
 */

use App\Repository\BookingRepository;

$method = $_SERVER['REQUEST_METHOD'];
$repo = new BookingRepository();

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 401);
}

try {
    if ($method === 'GET') {
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'booking_id' => $_GET['booking_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'only_trashed' => $_GET['only_trashed'] ?? null,
            'start' => $_GET['start'] ?? null,
            'end' => $_GET['end'] ?? null,
            'room_id' => $_GET['room_id'] ?? null
        ];
        
        $bookings = $repo->getAll($filters);

        jsonResponse([
            'success' => true,
            'bookings' => $bookings
        ]);

    } elseif ($method === 'POST') {
        $title = $_POST['title'] ?? null;
        $roomId = $_POST['room_id'] ?? null;
        $meetingDate = $_POST['meeting_date'] ?? null;
        $startTime = $_POST['start_time'] ?? null;
        $endTime = $_POST['end_time'] ?? null;
        $participants = $_POST['participants_count'] ?? 0;
        $phone = $_POST['phone'] ?? null;
        $description = $_POST['description'] ?? '';
        $equipments = $_POST['equipments'] ?? '';
        if (!empty($equipments)) {
            $description .= ($description ? "\n\n" : "") . "อุปกรณ์ที่ต้องการ: " . $equipments;
        }
        $isExternal = isset($_POST['is_external']) ? (bool)$_POST['is_external'] : false;
        $externalOrg = $_POST['external_org'] ?? null;
        // Use form-submitted department, fall back to session
        $department = !empty($_POST['department']) ? $_POST['department'] : ($_SESSION['user_data']['dept_name'] ?? null);

        if (!$title || !$startTime || !$endTime) {
            jsonResponse(['success' => false, 'message' => "กรุณากรอกข้อมูลให้ครบถ้วน"], 400);
        }

        // Full start/end times
        // Safeguard: normalize meeting_date to CE (ค.ศ.) in case frontend sent BE year (พ.ศ.)
        $meetingDate = preg_replace_callback('/^(\d{4})/', function($m) {
            $year = (int)$m[1];
            return $year > 2400 ? ($year - 543) : $year;
        }, $meetingDate ?? '');

        $fullStart = $meetingDate . ' ' . $startTime;
        $fullEnd   = $meetingDate . ' ' . $endTime;

        // Handle File Upload
        $attachmentPath = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/bookings/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileExt = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExt;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
                $attachmentPath = 'uploads/bookings/' . $fileName;
            }
        }

        $success = $repo->create([
            'room_id' => $isExternal ? null : $roomId,
            'user_id' => $_SESSION['user_id'],
            'title' => $title,
            'description' => $description,
            'start_time' => $fullStart,
            'end_time' => $fullEnd,
            'participants_count' => $participants,
            'phone' => $phone,
            'attachment_path' => $attachmentPath,
            'department_name' => $department,
            'is_external' => $isExternal ? 1 : 0,
            'external_org' => $externalOrg
        ]);

        if ($success) {
            jsonResponse([
                'success' => true,
                'message' => 'ส่งคำขอจองห้องประชุมสำเร็จ รอเจ้าหน้าที่อนุมัติ',
                'id' => \App\Core\Database::getInstance()->getConnection()->lastInsertId()
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้'], 500);
        }
    } elseif ($method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['booking_id'] ?? null;
        $action = $input['action'] ?? 'update_status';

        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'], 400);
        }

        // Only admins can approve/reject/restore
        if (($_SESSION['user_data']['role'] ?? 'user') !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ'], 403);
        }

        if ($action === 'restore') {
            $success = $repo->restore($id);
            $message = 'กู้คืนข้อมูลสำเร็จ';
        } elseif ($action === 'update_time') {
            $start_time = $input['start_time'] ?? null;
            $end_time = $input['end_time'] ?? null;
            $room_id = $input['room_id'] ?? null;
            
            if (!$start_time || !$end_time) {
                jsonResponse(['success' => false, 'message' => 'ข้อมูลเวลาไม่ครบถ้วน'], 400);
            }
            
            // Check for overlap here if we had a method, or assume it's checked by the user
            $success = \App\Core\Database::getInstance()->getConnection()->prepare(
                "UPDATE bookings SET start_time = ?, end_time = ?, room_id = ? WHERE id = ?"
            )->execute([$start_time, $end_time, $room_id, $id]);
            $message = 'อัปเดตเวลาและสถานที่สำเร็จ';
        } else {
            $status = $input['status'] ?? null;
            if (!$status) jsonResponse(['success' => false, 'message' => 'ไม่ระบุสถานะ'], 400);
            $success = $repo->updateStatus($id, $status);
            $message = 'อัปเดตสถานะสำเร็จ';
        }
        
        if ($success) {
            jsonResponse(['success' => true, 'message' => $message]);
        } else {
            jsonResponse(['success' => false, 'message' => 'ไม่สามารถดำเนินการได้'], 500);
        }
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['booking_id'] ?? null;
        $permanent = $input['permanent'] ?? false;

        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'ไม่ระบุ ID'], 400);
        }

        if (($_SESSION['user_data']['role'] ?? 'user') !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'ไม่มีสิทธิ์ดำเนินการ'], 403);
        }

        if ($permanent) {
            $success = $repo->permanentDelete($id);
            $message = 'ลบข้อมูลถาวรสำเร็จ';
        } else {
            $success = $repo->delete($id);
            $message = 'ย้ายไปถังขยะเรียบร้อย';
        }
        
        if ($success) {
            jsonResponse(['success' => true, 'message' => $message]);
        } else {
            jsonResponse(['success' => false, 'message' => 'ไม่สามารถลบข้อมูลได้'], 500);
        }
    }
} catch (Exception $e) {
    $code = $e->getCode();
    if ($code < 400 || $code >= 600) $code = 500;
    jsonResponse(['success' => false, 'message' => 'ข้อผิดพลาด: ' . $e->getMessage()], $code);
}
?>
