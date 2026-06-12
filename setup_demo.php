<?php
require_once __DIR__ . '/api/config.php';

/**
 * One-click demo setup: create DB, schema, seed users, sample bookings.
 */
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Setup | Meeting Queue</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #111827; }
        .ok { color: #059669; }
        .warn { color: #d97706; }
        .err { color: #dc2626; }
        .card { background: #f9fafb; border-radius: 12px; padding: 1.25rem; margin: 1rem 0; }
        a.btn { display: inline-block; margin-top: 1rem; padding: 12px 24px; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Meeting Queue — Demo Setup</h1>

<?php
$steps = [];

try {
    $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $tmpPdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tmpPdo->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $steps[] = ['ok', 'Database <code>' . htmlspecialchars(DB_NAME) . '</code> is ready.'];

    $pdo = getLocalDB();
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('Cannot read schema.sql');
    }

    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS.*;/i', '', $sql);
    $sql = preg_replace('/USE .*;/i', '', $sql);

    foreach (explode(';', $sql) as $query) {
        $query = trim($query);
        if ($query !== '') {
            $pdo->exec($query);
        }
    }
    $steps[] = ['ok', 'Tables and default rooms created.'];

    $users = [
        ['emp_code' => '1001', 'username' => 'Somchai', 'password' => '1234567890123', 'first_name' => 'Somchai', 'last_name' => 'Demo', 'position_name' => 'นายแพทย์ชำนาญการ', 'dept_name' => 'องค์กรแพทย์', 'role' => 'user'],
        ['emp_code' => '1002', 'username' => 'Somsak', 'password' => '1111111111111', 'first_name' => 'Somsak', 'last_name' => 'Demo', 'position_name' => 'พยาบาลวิชาชีพชำนาญการ', 'dept_name' => 'ฝ่ายการพยาบาล', 'role' => 'user'],
        ['emp_code' => '1003', 'username' => 'Kittipong', 'password' => '2222222222222', 'first_name' => 'Kittipong', 'last_name' => 'Demo', 'position_name' => 'นักจัดการงานทั่วไป', 'dept_name' => 'บริหารงานทั่วไป', 'role' => 'user'],
        ['emp_code' => '1004', 'username' => 'Nattaya', 'password' => '3333333333333', 'first_name' => 'Nattaya', 'last_name' => 'Demo', 'position_name' => 'เภสัชกรชำนาญการ', 'dept_name' => 'กลุ่มงานเภสัชกรรม', 'role' => 'user'],
        ['emp_code' => '0000', 'username' => 'admin', 'password' => 'admin', 'first_name' => 'System', 'last_name' => 'Administrator', 'position_name' => 'IT Admin', 'dept_name' => 'IT Department', 'role' => 'admin'],
    ];

    $upsert = $pdo->prepare(
        'INSERT INTO users (emp_code, username, password, first_name, last_name, position_name, dept_name, role)
         VALUES (:emp_code, :username, :password, :first_name, :last_name, :position_name, :dept_name, :role)
         ON DUPLICATE KEY UPDATE
            username = VALUES(username), password = VALUES(password),
            first_name = VALUES(first_name), last_name = VALUES(last_name),
            position_name = VALUES(position_name), dept_name = VALUES(dept_name), role = VALUES(role)'
    );
    foreach ($users as $user) {
        $upsert->execute($user);
    }
    $steps[] = ['ok', 'Demo users seeded (admin / admin + 4 sample staff).'];

    define('MOCK_BOOKINGS_QUIET', true);
    include __DIR__ . '/mock_bookings.php';
    $steps[] = ['ok', 'Sample bookings generated for the calendar.'];

} catch (Throwable $e) {
    $steps[] = ['err', 'Setup failed: ' . htmlspecialchars($e->getMessage())];
}

foreach ($steps as [$type, $message]) {
    echo '<p class="' . $type . '">• ' . $message . '</p>';
}
?>

    <div class="card">
        <h2>Demo login</h2>
        <ul>
            <li><strong>Admin:</strong> username <code>admin</code> / password <code>admin</code></li>
            <li><strong>User:</strong> username <code>Somchai</code> / password <code>1234567890123</code></li>
        </ul>
    </div>

    <a class="btn" href="index.php">Open Login Page</a>
</body>
</html>
