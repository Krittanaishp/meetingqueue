<?php
require_once 'config.php';

/**
 * Sync Users from ZK BioTime to Local DB
 */

try {
    $zkPdo = getZKDB();
    $localPdo = getLocalDB();

    $query = "
        WITH cte AS (
            SELECT
                pe.first_name,        
                pe.last_name AS cid, 
                pp.position_name,          
                pd.dept_name,           
                i.emp_code,             
                pe.photo,                          
                ROW_NUMBER() OVER (
                    PARTITION BY i.emp_code
                    ORDER BY i.punch_time DESC         
                ) AS rn
            FROM iclock_transaction AS i    
            LEFT JOIN personnel_employee AS pe ON i.emp_code = pe.emp_code
            LEFT JOIN personnel_department AS pd ON pe.department_id = pd.dept_code
            LEFT JOIN personnel_position AS pp ON pe.position_id = pp.id 
            WHERE pe.`status` = 0
              AND pe.last_name IS NOT NULL 
              AND pe.last_name != ''
              AND (
                    ( i.punch_time >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                      AND i.punch_time <  DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                    )
                    OR i.punch_time = DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 DAY)
                  )
        )
        SELECT  
            first_name,
            cid,
            position_name,
            dept_name,
            emp_code,
            photo
        FROM cte
        WHERE rn = 1 
        ORDER BY first_name ASC
    ";

    $stmt = $zkPdo->query($query);
    $zkUsers = $stmt->fetchAll();

    $upsertCount = 0;
    $errorCount = 0;

    $upsertSql = "
        INSERT INTO users (emp_code, username, password, first_name, last_name, position_name, dept_name, photo)
        VALUES (:emp_code, :username, :password, :first_name, :last_name, :position_name, :dept_name, :photo)
        ON DUPLICATE KEY UPDATE
            username = VALUES(username),
            password = VALUES(password),
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            position_name = VALUES(position_name),
            dept_name = VALUES(dept_name),
            photo = VALUES(photo)
    ";

    $upsertStmt = $localPdo->prepare($upsertSql);

    foreach ($zkUsers as $user) {
        try {
            $upsertStmt->execute([
                ':emp_code' => $user['emp_code'],
                ':username' => $user['first_name'], // first_name as username
                ':password' => $user['cid'],        // cid as password
                ':first_name' => $user['first_name'],
                ':last_name' => '', // Don't show 13-digit CID as last_name
                ':position_name' => $user['position_name'],
                ':dept_name' => $user['dept_name'],
                ':photo' => $user['photo']
            ]);
            $upsertCount++;
        } catch (Exception $e) {
            $errorCount++;
        }
    }

    jsonResponse([
        'success' => true,
        'message' => "ซิงค์ข้อมูลสำเร็จ: อัปเดตผู้ใช้ $upsertCount ราย, ข้อผิดพลาด $errorCount ราย",
        'total_found' => count($zkUsers)
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'การซิงค์ล้มเหลว: ' . $e->getMessage()
    ], 500);
}
?>
