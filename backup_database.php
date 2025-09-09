<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $backupType = $input['type'] ?? 'full';
    $includeData = $input['include_data'] ?? true;
    $compress = $input['compress'] ?? false;
    
    $pdo = getDatabaseConnection();
    
    // Create backup directory if it doesn't exist
    $backupDir = 'backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Generate backup filename
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = "{$backupDir}/casino_backup_{$backupType}_{$timestamp}.sql";
    
    // Start backup process
    $backupContent = generateBackup($pdo, $backupType, $includeData);
    
    // Write backup to file
    if (file_put_contents($backupFile, $backupContent) === false) {
        throw new Exception('Failed to write backup file');
    }
    
    $fileSize = filesize($backupFile);
    
    // Compress if requested
    if ($compress && function_exists('gzopen')) {
        $compressedFile = $backupFile . '.gz';
        $compressedContent = gzencode($backupContent, 9);
        
        if (file_put_contents($compressedFile, $compressedContent) === false) {
            throw new Exception('Failed to create compressed backup');
        }
        
        $compressedSize = filesize($compressedFile);
        $compressionRatio = round((1 - $compressedSize / $fileSize) * 100, 2);
        
        // Remove uncompressed file
        unlink($backupFile);
        
        $backupFile = $compressedFile;
        $fileSize = $compressedSize;
    }
    
    // Log backup creation
    logBackupActivity($pdo, $backupType, $backupFile, $fileSize);
    
    // Clean old backups (keep last 10)
    cleanOldBackups($backupDir);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database backup created successfully',
        'backup_info' => [
            'filename' => basename($backupFile),
            'type' => $backupType,
            'size' => formatBytes($fileSize),
            'compressed' => $compress,
            'created_at' => date('Y-m-d H:i:s'),
            'download_url' => $backupFile
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Backup failed: ' . $e->getMessage()]);
}

function generateBackup($pdo, $backupType, $includeData) {
    $backup = "-- Casino Database Backup\n";
    $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- Type: " . $backupType . "\n";
    $backup .= "-- Database: casino_db\n\n";
    
    $backup .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    // Get table list
    $tables = getTableList($pdo);
    
    foreach ($tables as $table) {
        if ($backupType === 'structure' && !in_array($table, ['users', 'user_wallets', 'user_vip_stats'])) {
            continue; // Skip data-heavy tables for structure-only backup
        }
        
        // Table structure
        $backup .= "-- Table structure for table `{$table}`\n";
        $backup .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $backup .= $createTable['Create Table'] . ";\n\n";
        
        // Table data
        if ($includeData) {
            $backup .= "-- Data for table `{$table}`\n";
            
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $backup .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";
                
                $insertValues = [];
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $insertValues[] = "(" . implode(', ', $values) . ")";
                }
                
                $backup .= implode(",\n", $insertValues) . ";\n\n";
            }
        }
    }
    
    $backup .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    return $backup;
}

function getTableList($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    return $tables;
}

function logBackupActivity($pdo, $backupType, $filename, $fileSize) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, created_at) 
            VALUES (1, 'admin_action', :description, :ip_address, CURRENT_TIMESTAMP)
        ");
        
        $description = "Database backup created: {$backupType} backup - " . basename($filename) . " (" . formatBytes($fileSize) . ")";
        $stmt->execute([
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Log to error log if database logging fails
        error_log("Failed to log backup activity: " . $e->getMessage());
    }
}

function cleanOldBackups($backupDir) {
    $files = glob($backupDir . '/casino_backup_*.sql*');
    
    // Sort by modification time (newest first)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Keep only the last 10 backups
    if (count($files) > 10) {
        $filesToDelete = array_slice($files, 10);
        foreach ($filesToDelete as $file) {
            unlink($file);
        }
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>

