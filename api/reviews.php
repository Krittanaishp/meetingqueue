<?php
require_once 'config.php';
use App\Core\Database;

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance()->getConnection();

try {
    if ($method === 'GET') {
        $booking_id = $_GET['booking_id'] ?? null;
        if ($booking_id) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM meeting_reviews WHERE booking_id = :booking_id AND user_id = :user_id");
                $stmt->execute([':booking_id' => $booking_id, ':user_id' => $_SESSION['user_id']]);
                $count = $stmt->fetchColumn();
                jsonResponse(['success' => true, 'has_review' => $count > 0]);
            } catch (Exception $tableErr) {
                // Table may not exist yet
                jsonResponse(['success' => true, 'has_review' => false]);
            }
        } else {
            jsonResponse(['success' => false, 'message' => 'Missing booking_id'], 400);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $booking_id = $input['booking_id'] ?? null;
        $rating = $input['rating'] ?? 0;
        $comment = $input['comment'] ?? '';
        $user_id = $_SESSION['user_id'];

        if (!$booking_id || $rating < 1 || $rating > 5) {
            jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง'], 400);
        }

        // Insert or update review
        $sql = "INSERT INTO meeting_reviews (booking_id, user_id, rating, comment) 
                VALUES (:booking_id, :user_id, :rating, :comment)
                ON DUPLICATE KEY UPDATE rating = :rating_upd, comment = :comment_upd";
        
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([
            ':booking_id' => $booking_id,
            ':user_id' => $user_id,
            ':rating' => $rating,
            ':comment' => $comment,
            ':rating_upd' => $rating,
            ':comment_upd' => $comment
        ]);

        jsonResponse(['success' => $success]);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
