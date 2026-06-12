<?php
/**
 * Demo configuration — Meeting Queue System
 * Copy config.example.php values or set environment variables for production.
 */

declare(strict_types=1);

define('APP_DEMO_MODE', true);

// Local Database (Meeting Queue)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'meetingqueue_db');

// ZK BioTime — disabled in demo (use seed_users.php instead)
define('ZK_ENABLED', filter_var(getenv('ZK_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('ZK_HOST', getenv('ZK_HOST') ?: 'localhost');
define('ZK_USER', getenv('ZK_USER') ?: 'root');
define('ZK_PASS', getenv('ZK_PASS') ?: '');
define('ZK_NAME', getenv('ZK_NAME') ?: 'zkbiotime');

/**
 * Default avatar when no profile photo is stored.
 */
function getUserPhotoUrl(array $user, int $size = 80): string
{
    if (!empty($user['photo'])) {
        return (string) $user['photo'];
    }

    $name = trim((string) ($user['first_name'] ?? $user['username'] ?? 'User'));

    return 'https://ui-avatars.com/api/?name=' . urlencode($name)
        . '&background=6A5243&color=fff&size=' . $size;
}

function getLocalDB(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int) $e->getCode());
    }
}

function getZKDB(): PDO
{
    if (!ZK_ENABLED) {
        throw new RuntimeException('ZK BioTime is disabled in demo mode. Run seed_users.php instead.');
    }

    $dsn = 'mysql:host=' . ZK_HOST . ';dbname=' . ZK_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, ZK_USER, ZK_PASS, $options);
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int) $e->getCode());
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function cleanName($name)
{
    if (!$name) {
        return $name;
    }

    return trim(preg_replace('/\s*\d{10,}\s*/', '', (string) $name));
}

if (isset($_SESSION['user_data'])) {
    if (isset($_SESSION['user_data']['first_name'])) {
        $_SESSION['user_data']['first_name'] = cleanName($_SESSION['user_data']['first_name']);
    }
    if (isset($_SESSION['user_data']['last_name'])) {
        $_SESSION['user_data']['last_name'] = cleanName($_SESSION['user_data']['last_name']);
    }
}

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/BookingRepository.php';
require_once __DIR__ . '/core/RoomRepository.php';

function jsonResponse($data, $status = 200): void
{
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}
