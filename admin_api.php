<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Get database connection
try {
    $pdo = getDatabaseConnection();
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle different actions
switch($action) {
    case 'get_stats':
        getDashboardStats($pdo);
        break;
    case 'get_users':
        getUsers($pdo);
        break;
    case 'get_user_details':
        getUserDetails($pdo);
        break;
    case 'get_vip_stats':
        getVIPStats($pdo);
        break;
    case 'get_transactions':
        getTransactions($pdo);
        break;
    case 'get_game_stats':
        getGameStats($pdo);
        break;
    case 'update_user_status':
        updateUserStatus($pdo);
        break;
    case 'add_user_balance':
        addUserBalance($pdo);
        break;
    case 'reset_all_balances':
        resetAllBalances($pdo);
        break;
    case 'get_user_activity':
        getUserActivity($pdo);
        break;
    case 'search_users':
        searchUsers($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getDashboardStats($pdo) {
    try {
        // Get total users
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE is_active = 1");
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
        
        // Get VIP users count
        $stmt = $pdo->query("SELECT COUNT(*) as vip_users FROM user_vip_stats WHERE vip_level_id > 1");
        $vipUsers = $stmt->fetch(PDO::FETCH_ASSOC)['vip_users'];
        
        // Get total deposits
        $stmt = $pdo->query("SELECT COUNT(*) as total_deposits, SUM(amount) as total_amount FROM transactions WHERE transaction_type = 'deposit' AND status = 'completed'");
        $deposits = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get total withdrawals
        $stmt = $pdo->query("SELECT COUNT(*) as total_withdrawals, SUM(amount) as total_amount FROM transactions WHERE transaction_type = 'withdrawal' AND status = 'completed'");
        $withdrawals = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get pending requests
        $stmt = $pdo->query("SELECT COUNT(*) as pending_requests FROM transactions WHERE status = 'pending'");
        $pendingRequests = $stmt->fetch(PDO::FETCH_ASSOC)['pending_requests'];
        
        // Get today's stats
        $stmt = $pdo->query("SELECT COUNT(*) as today_users FROM users WHERE DATE(created_at) = CURDATE()");
        $todayUsers = $stmt->fetch(PDO::FETCH_ASSOC)['today_users'];
        
        $stats = [
            'total_users' => (int)$totalUsers,
            'vip_users' => (int)$vipUsers,
            'total_deposits' => (int)$deposits['total_deposits'],
            'total_deposits_amount' => (float)$deposits['total_amount'] ?? 0,
            'total_withdrawals' => (int)$withdrawals['total_withdrawals'],
            'total_withdrawals_amount' => (float)$withdrawals['total_amount'] ?? 0,
            'pending_requests' => (int)$pendingRequests,
            'today_new_users' => (int)$todayUsers
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get dashboard stats: ' . $e->getMessage()]);
    }
}

function getUsers($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.is_verified, u.is_active, 
                   u.created_at, u.last_login, v.total_points, vl.level_name as vip_level,
                   w.balance, w.bonus_balance, w.total_deposited, w.total_withdrawn
            FROM users u
            LEFT JOIN user_vip_stats v ON u.id = v.user_id
            LEFT JOIN vip_levels vl ON v.vip_level_id = vl.id
            LEFT JOIN user_wallets w ON u.id = w.user_id
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true, 
            'data' => $users,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get users: ' . $e->getMessage()]);
    }
}

function getUserDetails($pdo) {
    try {
        $userId = $_GET['user_id'] ?? null;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            return;
        }
        
        // Get user basic info
        $stmt = $pdo->prepare("
            SELECT u.*, v.total_points, v.lifetime_wagered, v.lifetime_won, v.lifetime_lost,
                   vl.level_name as vip_level, vl.cashback_percentage, vl.bonus_multiplier,
                   w.balance, w.bonus_balance, w.locked_balance, w.total_deposited, w.total_withdrawn
            FROM users u
            LEFT JOIN user_vip_stats v ON u.id = v.user_id
            LEFT JOIN vip_levels vl ON v.vip_level_id = vl.id
            LEFT JOIN user_wallets w ON u.id = w.user_id
            WHERE u.id = :user_id
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Get recent transactions
        $stmt = $pdo->prepare("
            SELECT * FROM transactions 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get game stats
        $stmt = $pdo->prepare("
            SELECT game_type, COUNT(*) as games_played, 
                   SUM(total_wagered) as total_wagered,
                   SUM(total_won) as total_won,
                   SUM(total_lost) as total_lost
            FROM game_sessions 
            WHERE user_id = :user_id 
            GROUP BY game_type
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $gameStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $user['recent_transactions'] = $transactions;
        $user['game_stats'] = $gameStats;
        
        echo json_encode(['success' => true, 'data' => $user]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get user details: ' . $e->getMessage()]);
    }
}

function getVIPStats($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT vl.level_name, vl.min_points_required, vl.cashback_percentage,
                   COUNT(uvs.user_id) as user_count,
                   AVG(uvs.total_points) as avg_points,
                   SUM(uvs.lifetime_wagered) as total_wagered
            FROM vip_levels vl
            LEFT JOIN user_vip_stats uvs ON vl.id = uvs.vip_level_id
            GROUP BY vl.id, vl.level_name, vl.min_points_required, vl.cashback_percentage
            ORDER BY vl.min_points_required ASC
        ");
        
        $vipStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $vipStats]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get VIP stats: ' . $e->getMessage()]);
    }
}

function getTransactions($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($type) {
            $whereClause .= " AND transaction_type = :type";
            $params[':type'] = $type;
        }
        
        if ($status) {
            $whereClause .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $stmt = $pdo->prepare("
            SELECT t.*, u.username, u.email
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            $whereClause
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions t JOIN users u ON t.user_id = u.id $whereClause");
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true, 
            'data' => $transactions,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get transactions: ' . $e->getMessage()]);
    }
}

function getGameStats($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT game_type, 
                   COUNT(*) as total_sessions,
                   COUNT(DISTINCT user_id) as unique_players,
                   SUM(total_wagered) as total_wagered,
                   SUM(total_won) as total_won,
                   SUM(total_lost) as total_lost,
                   AVG(total_wagered) as avg_wagered
            FROM game_sessions 
            GROUP BY game_type
            ORDER BY total_wagered DESC
        ");
        
        $gameStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $gameStats]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get game stats: ' . $e->getMessage()]);
    }
}

function updateUserStatus($pdo) {
    try {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $status = $input['status'] ?? null;
        
        if (!$userId || !$status) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and status are required']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE users SET is_active = :status WHERE id = :user_id");
        $stmt->bindParam(':status', $status, PDO::PARAM_BOOL);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found or no changes made']);
        }
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update user status: ' . $e->getMessage()]);
    }
}

function addUserBalance($pdo) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $amount = $input['amount'] ?? null;
        $reason = $input['reason'] ?? 'Admin balance addition';
        
        if (!$userId || !$amount) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and amount are required']);
            return;
        }
        
        if ($amount <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Amount must be greater than 0']);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update wallet balance
            $stmt = $pdo->prepare("
                UPDATE user_wallets 
                SET balance = balance + :amount, 
                    total_deposited = total_deposited + :amount,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Record transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, transaction_type, amount, balance_before, balance_after, status, description)
                SELECT :user_id, 'bonus', :amount, balance - :amount, balance, 'completed', :reason
                FROM user_wallets WHERE user_id = :user_id
            ");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':reason', $reason);
            $stmt->execute();
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Balance added successfully']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add user balance: ' . $e->getMessage()]);
    }
}

function getUserActivity($pdo) {
    try {
        $userId = $_GET['user_id'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM user_activity_logs 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $activities]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get user activity: ' . $e->getMessage()]);
    }
}

function searchUsers($pdo) {
    try {
        $query = $_GET['q'] ?? '';
        $limit = $_GET['limit'] ?? 20;
        
        if (!$query) {
            http_response_code(400);
            echo json_encode(['error' => 'Search query is required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.is_active,
                   v.total_points, vl.level_name as vip_level,
                   w.balance
            FROM users u
            LEFT JOIN user_vip_stats v ON u.id = v.user_id
            LEFT JOIN vip_levels vl ON v.vip_level_id = vl.id
            LEFT JOIN user_wallets w ON u.id = w.user_id
            WHERE u.username LIKE :query 
               OR u.email LIKE :query 
               OR u.first_name LIKE :query 
               OR u.last_name LIKE :query
            ORDER BY u.created_at DESC
            LIMIT :limit
        ");
        
        $searchTerm = "%$query%";
        $stmt->bindParam(':query', $searchTerm);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $users]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to search users: ' . $e->getMessage()]);
    }
}

function resetAllBalances($pdo) {
    try {
        // Check if this is a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Reset all user balances to zero
            $stmt = $pdo->prepare("
                UPDATE user_wallets 
                SET balance = 0.00, 
                    bonus_balance = 0.00, 
                    locked_balance = 0.00,
                    total_deposited = 0.00,
                    total_withdrawn = 0.00,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute();
            
            $affectedRows = $stmt->rowCount();
            
            // Reset all VIP points to zero
            $stmt = $pdo->prepare("
                UPDATE user_vip_stats 
                SET total_points = 0, 
                    current_month_points = 0,
                    lifetime_wagered = 0.00,
                    lifetime_won = 0.00,
                    lifetime_lost = 0.00,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute();
            
            // Log this action
            $stmt = $pdo->prepare("
                INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, created_at) 
                VALUES (1, 'admin_action', 'Admin reset all user balances to zero', :ip_address, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => "Successfully reset $affectedRows user balances to $0.00",
                'affected_users' => $affectedRows
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to reset all balances: ' . $e->getMessage()]);
    }
}
?>
