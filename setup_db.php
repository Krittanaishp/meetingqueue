<?php
require_once 'api/config.php';

/**
 * Database Setup & Initial Sync Script
 */

try {
    // 0. Create Database if not exists
    echo "<h1>⚙️ System Initializing...</h1>";
    echo "<h3>0. Creating Database...</h3>";
    
    // Connect to MySQL server without selecting a database
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $tmpPdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ Database '" . DB_NAME . "' is ready.</p>";

    // Now connect to the actual database
    $pdo = getLocalDB();
    
    // 1. Create Tables
    echo "<h3>1. Creating Tables...</h3>";
    $sql = file_get_contents('schema.sql');
    if (!$sql) throw new Exception("Cannot find schema.sql");
    
    // Clean schema.sql: remove database creation since we already did it
    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS.*;/i', '', $sql);
    $sql = preg_replace('/USE .*; /i', '', $sql);

    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) $pdo->exec($query);
    }
    echo "<p style='color: green;'>✅ Tables created successfully.</p>";

    echo '<h3>2. Demo mode — skipping ZK BioTime sync</h3>';
    echo '<p style="color: #059669;">Demo uses local seed users. Run <a href="seed_users.php">seed_users.php</a> or use <a href="setup_demo.php">setup_demo.php</a> for full demo data.</p>';

    echo "<hr>";
    echo "<h2>🎉 System is Ready!</h2>";
    echo "<p>You can now login with your First Name and CID.</p>";
    echo "<a href='index.php' style='display: inline-block; padding: 12px 25px; background: #4f46e5; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Go to Login Page</a>";

} catch (Exception $e) {
    echo "<h1>❌ Setup Failed</h1>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
