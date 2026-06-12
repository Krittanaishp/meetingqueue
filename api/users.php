<?php
require_once 'config.php';

// Security Check: If not logged in or not admin, deny
if (!isset($_SESSION['user_id']) || ($_SESSION['user_data']['role'] ?? '') !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
}

try {
    $pdo = getLocalDB();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST to update user role
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        $userId = $data['user_id'] ?? null;
        $newRole = $data['role'] ?? null;

        if (!$userId || !in_array($newRole, ['admin', 'user'])) {
            jsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
        }

        // Prevent self-demotion
        if ($userId == $_SESSION['user_id']) {
            jsonResponse(['success' => false, 'message' => 'คุณไม่สามารถเปลี่ยนสิทธิ์ของตนเองได้'], 400);
        }

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);

        jsonResponse([
            'success' => true,
            'message' => 'อัปเดตสิทธิ์สำเร็จ!'
        ]);
    } else {
        // GET method (fetch user list)
        $stmt = $pdo->query("SELECT id, emp_code, username, first_name, last_name, position_name, dept_name, role FROM users ORDER BY first_name ASC");
        $users = $stmt->fetchAll();
        
        foreach ($users as &$u) {
            if (isset($u['first_name'])) $u['first_name'] = cleanName($u['first_name']);
            if (isset($u['last_name'])) $u['last_name'] = cleanName($u['last_name']);
        }

        jsonResponse([
            'success' => true,
            'data' => $users
        ]);
    }

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ], 500);
}
?>
