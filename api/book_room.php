<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getLocalDB();
    
    // Get form data
    $room_id = $_POST['room_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $meeting_date = $_POST['meeting_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $participants_count = $_POST['participants_count'] ?? 0;
    $description = $_POST['description'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $equipments = $_POST['equipments'] ?? null;
    
    // Validate required fields
    if (!$room_id || !$title || !$meeting_date || !$start_time || !$end_time || !$participants_count || !$phone) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Construct datetimes
    $start_datetime = $meeting_date . ' ' . $start_time . ':00';
    $end_datetime = $meeting_date . ' ' . $end_time . ':00';
    
    // Handle file upload
    $attachment_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_info = pathinfo($_FILES['attachment']['name']);
        $ext = strtolower($file_info['extension']);
        // Allowed extensions (prevent execution)
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        
        if (in_array($ext, $allowed)) {
            $filename = uniqid('book_') . '.' . $ext;
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
                $attachment_path = 'uploads/' . $filename;
            }
        }
    }
    
    // Insert into database
    $sql = "INSERT INTO bookings (room_id, user_id, title, description, start_time, end_time, participants_count, phone, attachment_path, status, is_external, equipments) 
            VALUES (:room_id, :user_id, :title, :description, :start_time, :end_time, :participants_count, :phone, :attachment_path, 'pending', 0, :equipments)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':room_id' => $room_id,
        ':user_id' => $_SESSION['user_id'],
        ':title' => $title,
        ':description' => $description,
        ':start_time' => $start_datetime,
        ':end_time' => $end_datetime,
        ':participants_count' => $participants_count,
        ':phone' => $phone,
        ':attachment_path' => $attachment_path,
        ':equipments' => $equipments
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Booking submitted successfully',
        'booking_id' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
