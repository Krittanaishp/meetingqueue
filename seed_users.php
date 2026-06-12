<?php
require_once __DIR__ . '/api/config.php';

/**
 * Seed Local Users for Testing
 * Use this when you cannot connect to the hospital's ZK BioTime database
 */

try {
    $pdo = getLocalDB();
    
    $users = [
        [
            'emp_code' => '1001',
            'username' => 'Somchai',
            'password' => '1234567890123', // CID Mock
            'first_name' => 'Somchai',
            'last_name' => '1234567890123',
            'position_name' => 'นายแพทย์ชำนาญการ',
            'dept_name' => 'องค์กรแพทย์',
            'role' => 'user'
        ],
        [
            'emp_code' => '1002',
            'username' => 'Somsak',
            'password' => '1111111111111',
            'first_name' => 'Somsak',
            'last_name' => '1111111111111',
            'position_name' => 'พยาบาลวิชาชีพชำนาญการ',
            'dept_name' => 'ฝ่ายการพยาบาล',
            'role' => 'user'
        ],
        [
            'emp_code' => '1003',
            'username' => 'Kittipong',
            'password' => '2222222222222',
            'first_name' => 'Kittipong',
            'last_name' => '2222222222222',
            'position_name' => 'นักจัดการงานทั่วไป',
            'dept_name' => 'บริหารงานทั่วไป',
            'role' => 'user'
        ],
        [
            'emp_code' => '1004',
            'username' => 'Nattaya',
            'password' => '3333333333333',
            'first_name' => 'Nattaya',
            'last_name' => '3333333333333',
            'position_name' => 'เภสัชกรชำนาญการ',
            'dept_name' => 'กลุ่มงานเภสัชกรรม',
            'role' => 'user'
        ],
        [
            'emp_code' => '0000',
            'username' => 'admin',
            'password' => 'admin',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'position_name' => 'IT Admin',
            'dept_name' => 'IT Department',
            'role' => 'admin'
        ]
    ];

    echo "<h1>🌱 Seeding Local Users...</h1>";
    
    $sql = "INSERT INTO users (emp_code, username, password, first_name, last_name, position_name, dept_name, role)
            VALUES (:emp_code, :username, :password, :first_name, :last_name, :position_name, :dept_name, :role)
            ON DUPLICATE KEY UPDATE
                username = VALUES(username),
                password = VALUES(password),
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                position_name = VALUES(position_name),
                dept_name = VALUES(dept_name),
                role = VALUES(role)";
                
    $stmt = $pdo->prepare($sql);
    
    $count = 0;
    foreach ($users as $user) {
        $stmt->execute($user);
        $count++;
        echo "<p>✅ Added: <b>{$user['first_name']}</b> ({$user['dept_name']})</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎉 Successfully seeded $count users!</h3>";
    echo "<p>You can now test the login page.</p>";
    echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";

} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Seeding Failed</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
