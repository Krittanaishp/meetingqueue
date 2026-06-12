<?php
require_once 'config.php';

/**
 * Rooms API
 * Method: GET
 */

use App\Repository\RoomRepository;

$method = $_SERVER['REQUEST_METHOD'];
$repo = new RoomRepository();

// Admins only for POST, PATCH, DELETE
if ($method !== 'GET') {
    if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    }
}

try {
    if ($method === 'GET') {
        $status = $_GET['status'] ?? null;
        $rooms = $repo->getAll($status);
        jsonResponse([
            'success' => true,
            'rooms' => $rooms
        ]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['name']) || empty($input['capacity'])) {
            jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'], 400);
        }
        $success = $repo->create($input);
        jsonResponse(['success' => $success]);
    } elseif ($method === 'PATCH' || $method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['id'])) {
            jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'], 400);
        }
        $success = $repo->update($input['id'], $input);
        jsonResponse(['success' => $success]);
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['id'])) {
            jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'], 400);
        }
        $success = $repo->delete($input['id']);
        jsonResponse(['success' => $success]);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
?>
