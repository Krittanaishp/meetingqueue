<?php
require_once 'config.php';

/**
 * Departments API
 * GET: Return distinct dept_name values from users
 */

try {
    $pdo = getLocalDB();
    $stmt = $pdo->query("SELECT DISTINCT dept_name FROM users WHERE dept_name IS NOT NULL AND dept_name != '' ORDER BY dept_name ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    jsonResponse([
        'success' => true,
        'departments' => array_values($rows)
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
?>
