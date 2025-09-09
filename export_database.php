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
    $exportType = $input['type'] ?? 'all';
    $format = $input['format'] ?? 'json';
    
    $pdo = getDatabaseConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        $exportData = [];
        
        switch ($exportType) {
            case 'users':
                $exportData = exportUsers($pdo);
                break;
            case 'transactions':
                $exportData = exportTransactions($pdo);
                break;
            case 'vip_stats':
                $exportData = exportVIPStats($pdo);
                break;
            case 'game_sessions':
                $exportData = exportGameSessions($pdo);
                break;
            case 'all':
            default:
                $exportData = exportAllData($pdo);
                break;
        }
        
        $pdo->commit();
        
        // Generate filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "casino_export_{$exportType}_{$timestamp}";
        
        switch ($format) {
            case 'csv':
                $csvData = arrayToCSV($exportData);
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                echo $csvData;
                break;
                
            case 'xml':
                $xmlData = arrayToXML($exportData, $exportType);
                header('Content-Type: application/xml');
                header('Content-Disposition: attachment; filename="' . $filename . '.xml"');
                echo $xmlData;
                break;
                
            case 'json':
            default:
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '.json"');
                echo json_encode([
                    'success' => true,
                    'export_type' => $exportType,
                    'format' => $format,
                    'timestamp' => $timestamp,
                    'total_records' => count($exportData),
                    'data' => $exportData
                ], JSON_PRETTY_PRINT);
                break;
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
}

function exportUsers($pdo) {
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.is_verified, u.is_active, 
               u.created_at, u.last_login, u.country, u.city,
               w.balance, w.bonus_balance, w.total_deposited, w.total_withdrawn,
               v.total_points, v.lifetime_wagered, v.lifetime_won, v.lifetime_lost,
               vl.level_name as vip_level
        FROM users u
        LEFT JOIN user_wallets w ON u.id = w.user_id
        LEFT JOIN user_vip_stats v ON u.id = v.user_id
        LEFT JOIN vip_levels vl ON v.vip_level_id = vl.id
        ORDER BY u.created_at DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportTransactions($pdo) {
    $stmt = $pdo->query("
        SELECT t.id, t.transaction_type, t.amount, t.balance_before, t.balance_after, 
               t.status, t.description, t.created_at,
               u.username, u.email
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportVIPStats($pdo) {
    $stmt = $pdo->query("
        SELECT vl.level_name, vl.min_points_required, vl.cashback_percentage, vl.bonus_multiplier,
               COUNT(uvs.user_id) as user_count,
               AVG(uvs.total_points) as avg_points,
               SUM(uvs.lifetime_wagered) as total_wagered,
               SUM(uvs.lifetime_won) as total_won,
               SUM(uvs.lifetime_lost) as total_lost
        FROM vip_levels vl
        LEFT JOIN user_vip_stats uvs ON vl.id = uvs.vip_level_id
        GROUP BY vl.id, vl.level_name, vl.min_points_required, vl.cashback_percentage, vl.bonus_multiplier
        ORDER BY vl.min_points_required ASC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportGameSessions($pdo) {
    $stmt = $pdo->query("
        SELECT gs.id, gs.game_type, gs.total_wagered, gs.total_won, gs.total_lost, 
               gs.games_played, gs.created_at,
               u.username, u.email
        FROM game_sessions gs
        JOIN users u ON gs.user_id = u.id
        ORDER BY gs.created_at DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportAllData($pdo) {
    return [
        'users' => exportUsers($pdo),
        'transactions' => exportTransactions($pdo),
        'vip_stats' => exportVIPStats($pdo),
        'game_sessions' => exportGameSessions($pdo),
        'vip_levels' => $pdo->query("SELECT * FROM vip_levels")->fetchAll(PDO::FETCH_ASSOC),
        'export_metadata' => [
            'exported_at' => date('Y-m-d H:i:s'),
            'database_name' => 'casino_db',
            'total_tables' => 12
        ]
    ];
}

function arrayToCSV($data) {
    if (empty($data)) return '';
    
    $output = fopen('php://output', 'w');
    
    // Handle nested arrays (for 'all' export type)
    if (isset($data['users'])) {
        foreach ($data as $tableName => $tableData) {
            if ($tableName === 'export_metadata') continue;
            
            fputcsv($output, ['=== ' . strtoupper($tableName) . ' ===']);
            if (!empty($tableData)) {
                fputcsv($output, array_keys($tableData[0]));
                foreach ($tableData as $row) {
                    fputcsv($output, $row);
                }
            }
            fputcsv($output, []); // Empty line between tables
        }
    } else {
        // Single table export
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    return ob_get_clean();
}

function arrayToXML($data, $rootName) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><casino_export></casino_export>');
    
    if (isset($data['users'])) {
        // Multiple tables
        foreach ($data as $tableName => $tableData) {
            if ($tableName === 'export_metadata') continue;
            
            $tableNode = $xml->addChild($tableName);
            foreach ($tableData as $row) {
                $rowNode = $tableNode->addChild('record');
                foreach ($row as $key => $value) {
                    $rowNode->addChild($key, htmlspecialchars($value));
                }
            }
        }
    } else {
        // Single table
        $tableNode = $xml->addChild($rootName);
        foreach ($data as $row) {
            $rowNode = $tableNode->addChild('record');
            foreach ($row as $key => $value) {
                $rowNode->addChild($key, htmlspecialchars($value));
            }
        }
    }
    
    return $xml->asXML();
}
?>

