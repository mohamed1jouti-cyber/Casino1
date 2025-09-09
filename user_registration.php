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
    
    // Validate required fields
    $requiredFields = ['email', 'password', 'username', 'first_name', 'last_name'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }
    
    // Validate password strength
    if (strlen($input['password']) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters long']);
        exit;
    }
    
    $pdo = getDatabaseConnection();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $input['email']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $input['username']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Username already taken']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Hash password
        $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);
        
        // Create user
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, username, first_name, last_name, is_verified, is_active, created_at) 
            VALUES (:email, :password_hash, :username, :first_name, :last_name, 0, 1, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            ':email' => $input['email'],
            ':password_hash' => $passwordHash,
            ':username' => $input['username'],
            ':first_name' => $input['first_name'],
            ':last_name' => $input['last_name']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        if (!$userId) {
            throw new Exception('Failed to create user');
        }
        
        // Initialize user wallet with ZERO balance
        $stmt = $pdo->prepare("
            INSERT INTO user_wallets (user_id, balance, bonus_balance, locked_balance, total_deposited, total_withdrawn, created_at) 
            VALUES (:user_id, 0.00, 0.00, 0.00, 0.00, 0.00, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([':user_id' => $userId]);
        
        // Initialize VIP stats starting at Bronze level (level 1)
        $stmt = $pdo->prepare("
            INSERT INTO user_vip_stats (user_id, vip_level_id, total_points, current_month_points, lifetime_wagered, lifetime_won, lifetime_lost, created_at) 
            VALUES (:user_id, 1, 0, 0, 0.00, 0.00, 0.00, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([':user_id' => $userId]);
        
        // Initialize user preferences
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences (user_id, language, currency, timezone, created_at) 
            VALUES (:user_id, 'en', 'USD', 'UTC', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([':user_id' => $userId]);
        
        // Log user creation activity
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, created_at) 
            VALUES (:user_id, 'registration', 'User account created', :ip_address, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user_id' => $userId,
                'username' => $input['username'],
                'email' => $input['email'],
                'balance' => 0.00,
                'vip_level' => 'Bronze',
                'points' => 0
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
?>

