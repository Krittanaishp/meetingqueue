<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? 'user';

// Security check: only admins can change roles
$current_role = $_SESSION['user_data']['role'];
if ($role !== $current_role && $current_role !== 'admin') {
    $role = $current_role; // Revert to current role if not admin
}

try {
    $pdo = getLocalDB();
    $photo_base64 = null;
    
    // Handle Photo Upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type']);
            exit;
        }

        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) { // 2MB limit
            echo json_encode(['success' => false, 'message' => 'File size too large (max 2MB)']);
            exit;
        }

        $image_data = file_get_contents($_FILES['photo']['tmp_name']);
        $photo_base64 = 'data:' . $_FILES['photo']['type'] . ';base64,' . base64_encode($image_data);
    }

    // Update Database
    if ($photo_base64) {
        $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ?, photo = ? WHERE id = ?");
        $stmt->execute([$email, $role, $photo_base64, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
        $stmt->execute([$email, $role, $user_id]);
    }

    // Refresh Session Data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['user_data'] = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
