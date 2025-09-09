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
    $reportType = $input['type'] ?? 'user';
    $format = $input['format'] ?? 'json';
    $dateFrom = $input['date_from'] ?? '';
    $dateTo = $input['date_to'] ?? '';
    
    $pdo = getDatabaseConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        $reportData = [];
        
        switch ($reportType) {
            case 'user':
                $reportData = generateUserReport($pdo, $dateFrom, $dateTo);
                break;
            case 'vip':
                $reportData = generateVIPReport($pdo, $dateFrom, $dateTo);
                break;
            case 'financial':
                $reportData = generateFinancialReport($pdo, $dateFrom, $dateTo);
                break;
            case 'game':
                $reportData = generateGameReport($pdo, $dateFrom, $dateTo);
                break;
            default:
                throw new Exception('Invalid report type');
        }
        
        $pdo->commit();
        
        // Generate filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "casino_report_{$reportType}_{$timestamp}";
        
        switch ($format) {
            case 'csv':
                $csvData = arrayToCSV($reportData);
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                echo $csvData;
                break;
                
            case 'xml':
                $xmlData = arrayToXML($reportData, $reportType);
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
                    'report_type' => $reportType,
                    'format' => $format,
                    'timestamp' => $timestamp,
                    'date_range' => [
                        'from' => $dateFrom,
                        'to' => $dateTo
                    ],
                    'data' => $reportData
                ], JSON_PRETTY_PRINT);
                break;
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Report generation failed: ' . $e->getMessage()]);
}

function generateUserReport($pdo, $dateFrom, $dateTo) {
    $whereClause = "";
    $params = [];
    
    if ($dateFrom && $dateTo) {
        $whereClause = "WHERE DATE(u.created_at) BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $dateFrom;
        $params[':date_to'] = $dateTo;
    }
    
    // User registration statistics
    $stmt = $pdo->prepare("
        SELECT 
            DATE(u.created_at) as registration_date,
            COUNT(*) as new_users,
            COUNT(CASE WHEN u.is_verified = 1 THEN 1 END) as verified_users,
            COUNT(CASE WHEN u.is_active = 1 THEN 1 END) as active_users,
            COUNT(CASE WHEN u.last_login IS NOT NULL THEN 1 END) as logged_in_users
        FROM users u
        $whereClause
        GROUP BY DATE(u.created_at)
        ORDER BY registration_date DESC
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $registrationStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // User demographics
    $stmt = $pdo->prepare("
        SELECT 
            u.country,
            u.city,
            COUNT(*) as user_count,
            AVG(w.balance) as avg_balance,
            SUM(w.total_deposited) as total_deposited
        FROM users u
        LEFT JOIN user_wallets w ON u.id = w.user_id
        $whereClause
        GROUP BY u.country, u.city
        ORDER BY user_count DESC
        LIMIT 20
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $demographics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // User activity summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN u.is_verified = 1 THEN 1 END) as verified_users,
            COUNT(CASE WHEN u.is_active = 1 THEN 1 END) as active_users,
            COUNT(CASE WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_7_days,
            COUNT(CASE WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_30_days,
            AVG(w.balance) as avg_balance,
            SUM(w.total_deposited) as total_deposited,
            SUM(w.total_withdrawn) as total_withdrawn
        FROM users u
        LEFT JOIN user_wallets w ON u.id = w.user_id
    ");
    
    $userSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'report_info' => [
            'type' => 'User Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo]
        ],
        'user_summary' => $userSummary,
        'registration_statistics' => $registrationStats,
        'demographics' => $demographics
    ];
}

function generateVIPReport($pdo, $dateFrom, $dateTo) {
    $whereClause = "";
    $params = [];
    
    if ($dateFrom && $dateTo) {
        $whereClause = "WHERE DATE(uvs.created_at) BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $dateFrom;
        $params[':date_to'] = $dateTo;
    }
    
    // VIP level distribution
    $stmt = $pdo->prepare("
        SELECT 
            vl.level_name,
            vl.min_points_required,
            vl.cashback_percentage,
            vl.bonus_multiplier,
            COUNT(uvs.user_id) as user_count,
            AVG(uvs.total_points) as avg_points,
            SUM(uvs.lifetime_wagered) as total_wagered,
            SUM(uvs.lifetime_won) as total_won,
            SUM(uvs.lifetime_lost) as total_lost,
            AVG(uvs.lifetime_wagered) as avg_wagered_per_user
        FROM vip_levels vl
        LEFT JOIN user_vip_stats uvs ON vl.id = uvs.vip_level_id
        $whereClause
        GROUP BY vl.id, vl.level_name, vl.min_points_required, vl.cashback_percentage, vl.bonus_multiplier
        ORDER BY vl.min_points_required ASC
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $vipDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // VIP progression analysis
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            u.email,
            vl.level_name as current_level,
            uvs.total_points,
            uvs.lifetime_wagered,
            uvs.lifetime_won,
            uvs.lifetime_lost,
            uvs.created_at as vip_start_date,
            u.created_at as registration_date
        FROM users u
        JOIN user_vip_stats uvs ON u.id = uvs.user_id
        JOIN vip_levels vl ON uvs.vip_level_id = vl.id
        $whereClause
        ORDER BY uvs.total_points DESC
        LIMIT 50
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $topVipUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // VIP summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT uvs.user_id) as total_vip_users,
            SUM(uvs.lifetime_wagered) as total_vip_wagered,
            SUM(uvs.lifetime_won) as total_vip_won,
            SUM(uvs.lifetime_lost) as total_vip_lost,
            AVG(uvs.total_points) as avg_vip_points,
            MAX(uvs.total_points) as max_vip_points
        FROM user_vip_stats uvs
    ");
    
    $vipSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'report_info' => [
            'type' => 'VIP Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo]
        ],
        'vip_summary' => $vipSummary,
        'vip_distribution' => $vipDistribution,
        'top_vip_users' => $topVipUsers
    ];
}

function generateFinancialReport($pdo, $dateFrom, $dateTo) {
    $whereClause = "";
    $params = [];
    
    if ($dateFrom && $dateTo) {
        $whereClause = "WHERE DATE(t.created_at) BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $dateFrom;
        $params[':date_to'] = $dateTo;
    }
    
    // Transaction summary by type
    $stmt = $pdo->prepare("
        SELECT 
            t.transaction_type,
            COUNT(*) as transaction_count,
            SUM(t.amount) as total_amount,
            AVG(t.amount) as avg_amount,
            MIN(t.amount) as min_amount,
            MAX(t.amount) as max_amount,
            SUM(CASE WHEN t.status = 'completed' THEN t.amount ELSE 0 END) as completed_amount,
            SUM(CASE WHEN t.status = 'pending' THEN t.amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN t.status = 'failed' THEN t.amount ELSE 0 END) as failed_amount
        FROM transactions t
        $whereClause
        GROUP BY t.transaction_type
        ORDER BY total_amount DESC
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $transactionSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily financial trends
    $stmt = $pdo->prepare("
        SELECT 
            DATE(t.created_at) as transaction_date,
            t.transaction_type,
            COUNT(*) as transaction_count,
            SUM(t.amount) as total_amount
        FROM transactions t
        $whereClause
        GROUP BY DATE(t.created_at), t.transaction_type
        ORDER BY transaction_date DESC, total_amount DESC
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $dailyTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // User financial summary
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            u.email,
            w.balance,
            w.total_deposited,
            w.total_withdrawn,
            w.bonus_balance,
            COUNT(t.id) as total_transactions,
            SUM(CASE WHEN t.transaction_type = 'deposit' THEN t.amount ELSE 0 END) as total_deposits,
            SUM(CASE WHEN t.transaction_type = 'withdrawal' THEN t.amount ELSE 0 END) as total_withdrawals
        FROM users u
        LEFT JOIN user_wallets w ON u.id = w.user_id
        LEFT JOIN transactions t ON u.id = t.user_id
        $whereClause
        GROUP BY u.id, u.username, u.email, w.balance, w.total_deposited, w.total_withdrawn, w.bonus_balance
        ORDER BY w.total_deposited DESC
        LIMIT 50
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $userFinancials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Financial summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(amount) as total_volume,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_volume,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_volume,
            AVG(amount) as avg_transaction_size
        FROM transactions
    ");
    
    $financialSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'report_info' => [
            'type' => 'Financial Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo]
        ],
        'financial_summary' => $financialSummary,
        'transaction_summary' => $transactionSummary,
        'daily_trends' => $dailyTrends,
        'user_financials' => $userFinancials
    ];
}

function generateGameReport($pdo, $dateFrom, $dateTo) {
    $whereClause = "";
    $params = [];
    
    if ($dateFrom && $dateTo) {
        $whereClause = "WHERE DATE(gs.created_at) BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $dateFrom;
        $params[':date_to'] = $dateTo;
    }
    
    // Game performance by type
    $stmt = $pdo->prepare("
        SELECT 
            gs.game_type,
            COUNT(*) as total_sessions,
            COUNT(DISTINCT gs.user_id) as unique_players,
            SUM(gs.total_wagered) as total_wagered,
            SUM(gs.total_won) as total_won,
            SUM(gs.total_lost) as total_lost,
            AVG(gs.total_wagered) as avg_wagered_per_session,
            AVG(gs.games_played) as avg_games_per_session,
            (SUM(gs.total_won) / SUM(gs.total_wagered) * 100) as win_percentage
        FROM game_sessions gs
        $whereClause
        GROUP BY gs.game_type
        ORDER BY total_wagered DESC
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $gamePerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily game activity
    $stmt = $pdo->prepare("
        SELECT 
            DATE(gs.created_at) as game_date,
            gs.game_type,
            COUNT(*) as sessions_count,
            COUNT(DISTINCT gs.user_id) as unique_players,
            SUM(gs.total_wagered) as total_wagered,
            SUM(gs.total_won) as total_won,
            SUM(gs.total_lost) as total_lost
        FROM game_sessions gs
        $whereClause
        GROUP BY DATE(gs.created_at), gs.game_type
        ORDER BY game_date DESC, total_wagered DESC
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $dailyGameActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top players by wagering
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            u.email,
            SUM(gs.total_wagered) as total_wagered,
            SUM(gs.total_won) as total_won,
            SUM(gs.total_lost) as total_lost,
            COUNT(gs.id) as total_sessions,
            SUM(gs.games_played) as total_games_played,
            (SUM(gs.total_won) / SUM(gs.total_wagered) * 100) as win_percentage
        FROM users u
        JOIN game_sessions gs ON u.id = gs.user_id
        $whereClause
        GROUP BY u.id, u.username, u.email
        ORDER BY total_wagered DESC
        LIMIT 50
    ");
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $topPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Game summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_sessions,
            COUNT(DISTINCT user_id) as unique_players,
            SUM(total_wagered) as total_wagered,
            SUM(total_won) as total_won,
            SUM(total_lost) as total_lost,
            SUM(games_played) as total_games_played,
            AVG(total_wagered) as avg_wagered_per_session,
            (SUM(total_won) / SUM(total_wagered) * 100) as overall_win_percentage
        FROM game_sessions
    ");
    
    $gameSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'report_info' => [
            'type' => 'Game Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo]
        ],
        'game_summary' => $gameSummary,
        'game_performance' => $gamePerformance,
        'daily_game_activity' => $dailyGameActivity,
        'top_players' => $topPlayers
    ];
}

function arrayToCSV($data) {
    if (empty($data)) return '';
    
    $output = fopen('php://output', 'w');
    
    // Handle nested arrays (for reports)
    if (isset($data['report_info'])) {
        foreach ($data as $sectionName => $sectionData) {
            fputcsv($output, ['=== ' . strtoupper(str_replace('_', ' ', $sectionName)) . ' ===']);
            
            if (is_array($sectionData) && !empty($sectionData)) {
                if (isset($sectionData[0]) && is_array($sectionData[0])) {
                    // Array of objects
                    fputcsv($output, array_keys($sectionData[0]));
                    foreach ($sectionData as $row) {
                        fputcsv($output, $row);
                    }
                } else {
                    // Single object
                    fputcsv($output, array_keys($sectionData));
                    fputcsv($output, $sectionData);
                }
            }
            fputcsv($output, []); // Empty line between sections
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
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><casino_report></casino_report>');
    
    if (isset($data['report_info'])) {
        // Report structure
        foreach ($data as $sectionName => $sectionData) {
            $sectionNode = $xml->addChild($sectionName);
            
            if (is_array($sectionData) && !empty($sectionData)) {
                if (isset($sectionData[0]) && is_array($sectionData[0])) {
                    // Array of objects
                    foreach ($sectionData as $row) {
                        $rowNode = $sectionNode->addChild('record');
                        foreach ($row as $key => $value) {
                            $rowNode->addChild($key, htmlspecialchars($value));
                        }
                    }
                } else {
                    // Single object
                    foreach ($sectionData as $key => $value) {
                        $sectionNode->addChild($key, htmlspecialchars($value));
                    }
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

