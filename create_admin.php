<?php
require_once __DIR__ . '/api/config.php';

try {
    $pdo = getLocalDB();
    
    $username = 'admin';
    $password = 'admin'; // Plain text for now as per previous logic where cid was used as password
    $first_name = 'System';
    $last_name = 'Admin';
    $emp_code = '000000';
    $role = 'admin';

    $sql = "INSERT INTO users (emp_code, username, password, first_name, last_name, role) 
            VALUES (:emp_code, :username, :password, :first_name, :last_name, :role)
            ON DUPLICATE KEY UPDATE role = 'admin'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':emp_code' => $emp_code,
        ':username' => $username,
        ':password' => $password,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':role' => $role
    ]);
    
    echo "Admin account created/updated successfully!\n";
    echo "Username: admin\n";
    echo "Password: admin\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
