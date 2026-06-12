<?php
require_once 'config.php';

use App\Core\Database;

$method = $_SERVER['REQUEST_METHOD'];

// Admins only
if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
}

$db = Database::getInstance()->getConnection();

try {
    if ($method === 'POST') {
        $roomId = $_POST['room_id'] ?? null;
        if (!$roomId) {
            jsonResponse(['success' => false, 'message' => 'Room ID is required'], 400);
        }

        // Handle File Upload
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['success' => false, 'message' => 'No image uploaded or upload error'], 400);
        }

        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            jsonResponse(['success' => false, 'message' => 'Invalid file type'], 400);
        }

        // Limit size to 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            jsonResponse(['success' => false, 'message' => 'File too large (Max 5MB)'], 400);
        }

        $uploadDir = '../assets/images/rooms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'room_' . $roomId . '_' . time() . '_' . uniqid() . '.' . $ext;
        $dest = $uploadDir . $fileName;
        $dbPath = 'assets/images/rooms/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // Get existing images
            $stmt = $db->prepare("SELECT images FROM rooms WHERE id = :id");
            $stmt->execute([':id' => $roomId]);
            $room = $stmt->fetch();
            
            $images = [];
            if ($room && !empty($room['images'])) {
                $images = json_decode($room['images'], true) ?: [];
            }
            
            $images[] = $dbPath;
            
            // Update DB
            $updateStmt = $db->prepare("UPDATE rooms SET images = :images WHERE id = :id");
            $updateStmt->execute([
                ':images' => json_encode($images),
                ':id' => $roomId
            ]);
            
            jsonResponse(['success' => true, 'path' => $dbPath]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to move uploaded file'], 500);
        }

    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $roomId = $input['room_id'] ?? null;
        $imagePath = $input['image_path'] ?? null;

        if (!$roomId || !$imagePath) {
            jsonResponse(['success' => false, 'message' => 'Missing data'], 400);
        }

        // Get existing images
        $stmt = $db->prepare("SELECT images FROM rooms WHERE id = :id");
        $stmt->execute([':id' => $roomId]);
        $room = $stmt->fetch();
        
        if ($room && !empty($room['images'])) {
            $images = json_decode($room['images'], true) ?: [];
            $newImages = array_filter($images, function($img) use ($imagePath) {
                return $img !== $imagePath;
            });
            
            // Update DB
            $updateStmt = $db->prepare("UPDATE rooms SET images = :images WHERE id = :id");
            $updateStmt->execute([
                ':images' => json_encode(array_values($newImages)),
                ':id' => $roomId
            ]);

            // Try to delete physical file
            $physicalPath = '../' . $imagePath;
            if (file_exists($physicalPath)) {
                unlink($physicalPath);
            }
            
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Room not found or no images'], 404);
        }
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
