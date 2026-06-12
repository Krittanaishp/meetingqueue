<?php
require_once 'config.php';

/**
 * Login API
 * Method: POST
 * JSON Payload: { username, password }
 */

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || !isset($input['password'])) {
    jsonResponse(['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน'], 400);
}

$username = trim($input['username']);
$password = trim($input['password']);

try {
    $pdo = getLocalDB();
    
    // In a production app, password should be hashed. 
    // Here we use plain text as requested (first_name as user, cid as pass).
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password LIMIT 1");
    $stmt->execute([':username' => $username, ':password' => $password]);
    $user = $stmt->fetch();

    if ($user) {
        // Remove password from session data
        unset($user['password']);
        
        // Clean out any 13-digit IDs embedded in names
        if (isset($user['first_name'])) $user['first_name'] = cleanName($user['first_name']);
        if (isset($user['last_name'])) $user['last_name'] = cleanName($user['last_name']);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_data'] = $user;

        jsonResponse([
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'user' => $user
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง'], 401);
    }

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'ข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage()], 500);
}
?>
