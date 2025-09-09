<?php
require_once 'config.php';

// Setup script for casino database
echo "<h1>Casino Database Setup</h1>";

// Check if MySQL is available
if (!function_exists('mysqli_connect')) {
    die("<p>❌ Error: PHP MySQL extension is not installed.</p>\n" .
        "<p>Please install PHP with MySQL support first.</p>\n" .
        "<p><a href='README.md'>Read the setup instructions</a></p>");
}

try {
    // Check if database exists
    if (!checkDatabaseExists()) {
        echo "<p>Database 'casino_db' does not exist. Creating...</p>";
        if (createDatabaseIfNotExists()) {
            echo "<p>✅ Database created successfully!</p>";
        } else {
            echo "<p>❌ Failed to create database!</p>";
            exit;
        }
    } else {
        echo "<p>✅ Database 'casino_db' already exists.</p>";
    }
    
    // Connect to database
    $pdo = getDatabaseConnection();
    echo "<p>✅ Connected to database successfully!</p>";
    
    // Read and execute SQL schema
    $sqlFile = 'database_schema.sql';
    if (file_exists($sqlFile)) {
        echo "<p>📖 Reading SQL schema file...</p>";
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        echo "<p>🔧 Executing database schema...</p>";
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (PDOException $e) {
                    $errorCount++;
                    echo "<p>❌ Error executing statement: " . $e->getMessage() . "</p>";
                }
            }
        }
        
        echo "<p>✅ Schema execution completed: $successCount successful, $errorCount errors</p>";
        
        // Create sample admin user
        echo "<p>👤 Creating sample admin user...</p>";
        try {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO admin_users (username, password_hash, email, role, permissions) 
                VALUES ('admin', :password, 'admin@casino.com', 'super_admin', '{}')
                ON DUPLICATE KEY UPDATE username = username
            ");
            $stmt->execute([':password' => $adminPassword]);
            echo "<p>✅ Sample admin user created (username: admin, password: admin123)</p>";
        } catch (PDOException $e) {
            echo "<p>⚠️ Admin user creation: " . $e->getMessage() . "</p>";
        }
        
        // Create sample regular user
        echo "<p>👤 Creating sample regular user...</p>";
        try {
            $userPassword = password_hash('user123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password_hash, username, first_name, last_name, is_verified, is_active) 
                VALUES ('user@casino.com', :password, 'demo_user', 'Demo', 'User', 1, 1)
                ON DUPLICATE KEY UPDATE username = username
            ");
            $stmt->execute([':password' => $userPassword]);
            
            // Get user ID
            $userId = $pdo->lastInsertId();
            if ($userId) {
                // Create wallet with ZERO balance for new users
                $pdo->exec("INSERT INTO user_wallets (user_id, balance, bonus_balance, locked_balance, total_deposited, total_withdrawn) VALUES ($userId, 0.00, 0.00, 0.00, 0.00, 0.00)");
                
                // Create VIP stats starting at Bronze level
                $pdo->exec("INSERT INTO user_vip_stats (user_id, vip_level_id, total_points, lifetime_wagered, lifetime_won, lifetime_lost) VALUES ($userId, 1, 0, 0.00, 0.00, 0.00)");
                
                // Create preferences
                $pdo->exec("INSERT INTO user_preferences (user_id) VALUES ($userId)");
                
                echo "<p>✅ Sample user created (username: demo_user, password: user123, balance: $0.00)</p>";
            }
        } catch (PDOException $e) {
            echo "<p>⚠️ Sample user creation: " . $e->getMessage() . "</p>";
        }
        
        // Create sample transactions
        echo "<p>💰 Creating sample transactions...</p>";
        try {
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, transaction_type, amount, balance_before, balance_after, status, description) 
                VALUES 
                (1, 'deposit', 500.00, 0.00, 500.00, 'completed', 'Initial deposit'),
                (1, 'bet', 50.00, 500.00, 450.00, 'completed', 'Game bet'),
                (1, 'win', 75.00, 450.00, 525.00, 'completed', 'Game win')
            ");
            $stmt->execute();
            echo "<p>✅ Sample transactions created</p>";
        } catch (PDOException $e) {
            echo "<p>⚠️ Sample transactions: " . $e->getMessage() . "</p>";
        }
        
        // Create sample game sessions
        echo "<p>🎮 Creating sample game sessions...</p>";
        try {
            $stmt = $pdo->prepare("
                INSERT INTO game_sessions (user_id, game_type, total_wagered, total_won, total_lost, games_played) 
                VALUES 
                (1, 'slots', 200.00, 150.00, 50.00, 10),
                (1, 'blackjack', 300.00, 250.00, 50.00, 5)
            ");
            $stmt->execute();
            echo "<p>✅ Sample game sessions created</p>";
        } catch (PDOException $e) {
            echo "<p>⚠️ Sample game sessions: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>❌ SQL schema file not found: $sqlFile</p>";
    }
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<p><strong>Database:</strong> casino_db</p>";
    echo "<p><strong>Admin User:</strong> admin / admin123</p>";
    echo "<p><strong>Demo User:</strong> demo_user / user123</p>";
    echo "<p><strong>Tables Created:</strong> users, vip_levels, user_vip_stats, user_wallets, transactions, game_sessions, game_bets, user_bonuses, user_preferences, user_referrals, admin_users, user_activity_logs</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the admin dashboard at <a href='admin.html'>admin.html</a></li>";
    echo "<li>Login with admin/admin123</li>";
    echo "<li>Check the User Management tab to see database users</li>";
    echo "<li>Test adding balance to users</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ Setup failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config.php</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}

h1, h2, h3 {
    color: #333;
}

p {
    margin: 10px 0;
    padding: 10px;
    background: white;
    border-radius: 5px;
    border-left: 4px solid #ddd;
}

p:contains("✅") {
    border-left-color: #4CAF50;
}

p:contains("❌") {
    border-left-color: #f44336;
}

p:contains("⚠️") {
    border-left-color: #ff9800;
}

a {
    color: #2196F3;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

ol {
    background: white;
    padding: 20px;
    border-radius: 5px;
}

li {
    margin: 10px 0;
}
</style>
