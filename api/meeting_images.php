<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$db = getLocalDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $booking_id = $_GET['booking_id'] ?? null;
    if (!$booking_id) jsonResponse(['success' => false, 'message' => 'Missing booking ID'], 400);

    $stmt = $db->prepare("SELECT id, image_path FROM booking_images WHERE booking_id = ? ORDER BY created_at ASC");
    $stmt->execute([$booking_id]);
    jsonResponse(['success' => true, 'images' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;
    $action = $_POST['action'] ?? 'upload';

    if (!$booking_id) {
        jsonResponse(['success' => false, 'message' => 'Missing booking ID'], 400);
    }

    // Check permissions
    $stmt = $db->prepare("SELECT user_id FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) jsonResponse(['success' => false, 'message' => 'Booking not found'], 404);
    if ($_SESSION['user_data']['role'] !== 'admin' && $booking['user_id'] != $_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Permission denied'], 403);
    }

    if ($action === 'delete') {
        $image_id = $_POST['image_id'] ?? null;
        if (!$image_id) jsonResponse(['success' => false, 'message' => 'Missing image ID'], 400);

        $stmt = $db->prepare("SELECT image_path FROM booking_images WHERE id = ? AND booking_id = ?");
        $stmt->execute([$image_id, $booking_id]);
        $img = $stmt->fetch();

        if ($img) {
            $full_path = '../' . $img['image_path'];
            if (file_exists($full_path)) unlink($full_path);
            
            $stmt = $db->prepare("DELETE FROM booking_images WHERE id = ?");
            $stmt->execute([$image_id]);
            jsonResponse(['success' => true, 'message' => 'Image deleted']);
        }
        jsonResponse(['success' => false, 'message' => 'Image not found'], 404);
    }

    if (isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $uploaded = [];
        $errors = [];

        $target_dir = '../uploads/meeting_images/';
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        foreach ($files['name'] as $key => $val) {
            if ($files['error'][$key] !== UPLOAD_ERR_OK) continue;

            $ext = strtolower(pathinfo($files['name'][$key], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;

            $new_filename = 'meeting_' . $booking_id . '_' . bin2hex(random_bytes(4)) . '_' . time() . '.' . $ext;
            $target_file = $target_dir . $new_filename;
            $db_path = 'uploads/meeting_images/' . $new_filename;

            if (move_uploaded_file($files['tmp_name'][$key], $target_file)) {
                $stmt = $db->prepare("INSERT INTO booking_images (booking_id, image_path) VALUES (?, ?)");
                $stmt->execute([$booking_id, $db_path]);
                $uploaded[] = $db_path;
            }
        }
        
        jsonResponse(['success' => true, 'message' => count($uploaded) . ' images uploaded', 'paths' => $uploaded]);
    }
    
    jsonResponse(['success' => false, 'message' => 'No files uploaded'], 400);
}
