<?php
require_once 'config.php';

// Ensure only admin can access
if (!isset($_SESSION['user_data']) || ($_SESSION['user_data']['role'] ?? 'user') !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized Access. Admin only.'], 403);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$backups_dir = __DIR__ . '/../backups/';

if (!is_dir($backups_dir)) {
    mkdir($backups_dir, 0755, true);
}

/**
 * Sanitize filename to prevent path traversal
 */
function sanitizeFilename(string $filename): string {
    return basename(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename));
}

switch ($action) {

    // ─── CREATE BACKUP ────────────────────────────────────────────────
    case 'backup':
        try {
            $db = getLocalDB();
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $sqlScript  = "-- ══════════════════════════════════════════\n";
            $sqlScript .= "-- Database Backup\n";
            $sqlScript .= "-- Host    : " . DB_HOST . "\n";
            $sqlScript .= "-- Database: " . DB_NAME . "\n";
            $sqlScript .= "-- Created : " . date('Y-m-d H:i:s') . "\n";
            $sqlScript .= "-- Tables  : " . count($tables) . "\n";
            $sqlScript .= "-- ══════════════════════════════════════════\n\n";
            $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $sqlScript .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
            $sqlScript .= "SET NAMES utf8mb4;\n\n";

            $totalRows = 0;

            foreach ($tables as $table) {
                // Table structure
                $createResult = $db->query("SHOW CREATE TABLE `$table`");
                $createRow = $createResult->fetch(PDO::FETCH_NUM);
                $sqlScript .= "-- ── Table: `$table` ──\n";
                $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
                $sqlScript .= $createRow[1] . ";\n\n";

                // Table data using batched inserts for performance
                $dataResult = $db->query("SELECT * FROM `$table`");
                $columnCount = $dataResult->columnCount();
                $rowCount = 0;
                $batchValues = [];

                while ($row = $dataResult->fetch(PDO::FETCH_NUM)) {
                    $rowCount++;
                    $totalRows++;
                    $vals = [];
                    for ($j = 0; $j < $columnCount; $j++) {
                        if ($row[$j] === null) {
                            $vals[] = "NULL";
                        } else {
                            $escaped = addslashes($row[$j]);
                            $escaped = str_replace("\n", "\\n", $escaped);
                            $escaped = str_replace("\r", "\\r", $escaped);
                            $vals[] = "'" . $escaped . "'";
                        }
                    }
                    $batchValues[] = "(" . implode(',', $vals) . ")";

                    // Flush every 100 rows
                    if (count($batchValues) >= 100) {
                        $sqlScript .= "INSERT INTO `$table` VALUES\n" . implode(",\n", $batchValues) . ";\n";
                        $batchValues = [];
                    }
                }

                // Flush remaining
                if (!empty($batchValues)) {
                    $sqlScript .= "INSERT INTO `$table` VALUES\n" . implode(",\n", $batchValues) . ";\n";
                }

                $sqlScript .= "\n";
            }

            $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $backup_file_name = 'backup_' . DB_NAME . '_' . date('Ymd_His') . '.sql';
            file_put_contents($backups_dir . $backup_file_name, $sqlScript);

            jsonResponse([
                'success'  => true,
                'message'  => 'สร้างไฟล์สำรองข้อมูลสำเร็จ',
                'filename' => $backup_file_name,
                'tables'   => count($tables),
                'rows'     => $totalRows,
                'size'     => filesize($backups_dir . $backup_file_name)
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
        }
        break;

    // ─── LIST BACKUPS ─────────────────────────────────────────────────
    case 'list':
        try {
            $files = [];
            if ($handle = opendir($backups_dir)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry !== '.' && $entry !== '..' && pathinfo($entry, PATHINFO_EXTENSION) === 'sql') {
                        $fullpath = $backups_dir . $entry;
                        
                        // Read first few lines to get metadata
                        $tableCount = '?';
                        $fh = fopen($fullpath, 'r');
                        if ($fh) {
                            $headerLines = '';
                            for ($i = 0; $i < 10; $i++) {
                                $line = fgets($fh);
                                if ($line === false) break;
                                $headerLines .= $line;
                            }
                            fclose($fh);
                            if (preg_match('/Tables\s*:\s*(\d+)/', $headerLines, $m)) {
                                $tableCount = (int)$m[1];
                            }
                        }

                        $files[] = [
                            'name'   => $entry,
                            'size'   => filesize($fullpath),
                            'date'   => filemtime($fullpath),
                            'tables' => $tableCount
                        ];
                    }
                }
                closedir($handle);
            }

            usort($files, fn($a, $b) => $b['date'] - $a['date']);

            jsonResponse(['success' => true, 'files' => $files]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
        break;

    // ─── DELETE BACKUP ────────────────────────────────────────────────
    case 'delete':
        $filename = sanitizeFilename($_POST['filename'] ?? '');
        if (empty($filename)) {
            jsonResponse(['success' => false, 'message' => 'ชื่อไฟล์ไม่ถูกต้อง'], 400);
            exit;
        }

        $filepath = $backups_dir . $filename;
        if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
            unlink($filepath);
            jsonResponse(['success' => true, 'message' => 'ลบไฟล์สำเร็จ']);
        } else {
            jsonResponse(['success' => false, 'message' => 'ไม่พบไฟล์ดังกล่าว'], 404);
        }
        break;

    // ─── RESTORE BACKUP ───────────────────────────────────────────────
    case 'restore':
        $filename = sanitizeFilename($_POST['filename'] ?? '');
        if (empty($filename)) {
            jsonResponse(['success' => false, 'message' => 'ชื่อไฟล์ไม่ถูกต้อง'], 400);
            exit;
        }

        $filepath = $backups_dir . $filename;
        if (!file_exists($filepath)) {
            jsonResponse(['success' => false, 'message' => 'ไม่พบไฟล์ดังกล่าว'], 404);
            exit;
        }

        try {
            $db = getLocalDB();
            $sql = file_get_contents($filepath);

            // Use exec() for multi-statement SQL — much more reliable than prepare()
            $db->exec($sql);

            jsonResponse(['success' => true, 'message' => 'กู้คืนฐานข้อมูลเรียบร้อยแล้ว']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'กู้คืนไม่สำเร็จ: ' . $e->getMessage()], 500);
        }
        break;

    // ─── DOWNLOAD BACKUP ──────────────────────────────────────────────
    case 'download':
        $filename = sanitizeFilename($_GET['filename'] ?? '');
        if (empty($filename)) {
            http_response_code(400);
            die('Invalid filename');
        }

        $filepath = $backups_dir . $filename;
        if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            ob_clean();
            flush();
            readfile($filepath);
            exit;
        } else {
            http_response_code(404);
            die('File not found');
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}
