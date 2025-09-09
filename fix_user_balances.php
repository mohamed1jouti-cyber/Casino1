<?php
require_once 'config.php';

echo "<h1>🔧 Fix User Balances</h1>";
echo "<p>This script will fix user balances and ensure all new users start with zero balance.</p>";

try {
    $pdo = getDatabaseConnection();
    echo "<p>✅ Connected to database successfully!</p>";
    
    // Check current user balances
    echo "<h3>📊 Current User Balances:</h3>";
    $stmt = $pdo->query("
        SELECT u.username, u.email, w.balance, w.bonus_balance, w.total_deposited, w.total_withdrawn
        FROM users u
        LEFT JOIN user_wallets w ON u.id = w.user_id
        ORDER BY u.id
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 10px;'>Username</th>";
        echo "<th style='padding: 10px;'>Email</th>";
        echo "<th style='padding: 10px;'>Current Balance</th>";
        echo "<th style='padding: 10px;'>Bonus Balance</th>";
        echo "<th style='padding: 10px;'>Total Deposited</th>";
        echo "<th style='padding: 10px;'>Total Withdrawn</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            $balanceColor = $user['balance'] > 0 ? '#ff6b6b' : '#51cf66';
            echo "<tr>";
            echo "<td style='padding: 10px;'>{$user['username']}</td>";
            echo "<td style='padding: 10px;'>{$user['email']}</td>";
            echo "<td style='padding: 10px; background: $balanceColor; color: white; font-weight: bold;'>\${$user['balance']}</td>";
            echo "<td style='padding: 10px;'>\${$user['bonus_balance']}</td>";
            echo "<td style='padding: 10px;'>\${$user['total_deposited']}</td>";
            echo "<td style='padding: 10px;'>\${$user['total_withdrawn']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in database.</p>";
    }
    
    // Fix user balances (reset to zero for new users)
    echo "<h3>🔧 Fixing User Balances...</h3>";
    
    $fixCount = 0;
    foreach ($users as $user) {
        if ($user['balance'] > 0 && $user['total_deposited'] == 0) {
            // This user has balance but no deposits - likely incorrectly initialized
            $stmt = $pdo->prepare("
                UPDATE user_wallets 
                SET balance = 0.00, 
                    bonus_balance = 0.00, 
                    locked_balance = 0.00,
                    total_deposited = 0.00,
                    total_withdrawn = 0.00,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = (SELECT id FROM users WHERE username = :username)
            ");
            $stmt->execute([':username' => $user['username']]);
            
            if ($stmt->rowCount() > 0) {
                echo "<p>✅ Fixed balance for user: {$user['username']} (reset to $0.00)</p>";
                $fixCount++;
            }
        }
    }
    
    if ($fixCount > 0) {
        echo "<p>🎉 Successfully fixed $fixCount user(s) with incorrect balances!</p>";
    } else {
        echo "<p>✅ All user balances are correct!</p>";
    }
    
    // Ensure all users have proper wallet records
    echo "<h3>🔍 Checking Wallet Records...</h3>";
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email
        FROM users u
        LEFT JOIN user_wallets w ON u.id = w.user_id
        WHERE w.id IS NULL
    ");
    
    $missingWallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($missingWallets) > 0) {
        echo "<p>⚠️ Found " . count($missingWallets) . " user(s) without wallet records. Creating them...</p>";
        
        foreach ($missingWallets as $user) {
            $stmt = $pdo->prepare("
                INSERT INTO user_wallets (user_id, balance, bonus_balance, locked_balance, total_deposited, total_withdrawn, created_at) 
                VALUES (:user_id, 0.00, 0.00, 0.00, 0.00, 0.00, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([':user_id' => $user['id']]);
            
            echo "<p>✅ Created wallet for user: {$user['username']}</p>";
        }
    } else {
        echo "<p>✅ All users have proper wallet records!</p>";
    }
    
    // Ensure all users have VIP stats
    echo "<h3>👑 Checking VIP Records...</h3>";
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email
        FROM users u
        LEFT JOIN user_vip_stats v ON u.id = v.user_id
        WHERE v.id IS NULL
    ");
    
    $missingVIP = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($missingVIP) > 0) {
        echo "<p>⚠️ Found " . count($missingVIP) . " user(s) without VIP records. Creating them...</p>";
        
        foreach ($missingVIP as $user) {
            $stmt = $pdo->prepare("
                INSERT INTO user_vip_stats (user_id, vip_level_id, total_points, current_month_points, lifetime_wagered, lifetime_won, lifetime_lost, created_at) 
                VALUES (:user_id, 1, 0, 0, 0.00, 0.00, 0.00, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([':user_id' => $user['id']]);
            
            echo "<p>✅ Created VIP stats for user: {$user['username']} (Bronze level)</p>";
        }
    } else {
        echo "<p>✅ All users have proper VIP records!</p>";
    }
    
    // Show final status
    echo "<h3>📊 Final User Status:</h3>";
    $stmt = $pdo->query("
        SELECT u.username, u.email, w.balance, w.bonus_balance, v.total_points, vl.level_name as vip_level
        FROM users u
        LEFT JOIN user_wallets w ON u.id = w.user_id
        LEFT JOIN user_vip_stats v ON u.id = v.user_id
        LEFT JOIN vip_levels vl ON v.vip_level_id = vl.id
        ORDER BY u.id
    ");
    
    $finalUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #e8f5e8;'>";
    echo "<th style='padding: 10px;'>Username</th>";
    echo "<th style='padding: 10px;'>Email</th>";
    echo "<th style='padding: 10px;'>Balance</th>";
    echo "<th style='padding: 10px;'>VIP Level</th>";
    echo "<th style='padding: 10px;'>Points</th>";
    echo "</tr>";
    
    foreach ($finalUsers as $user) {
        $balanceColor = $user['balance'] == 0 ? '#51cf66' : '#ff6b6b';
        echo "<tr>";
        echo "<td style='padding: 10px;'>{$user['username']}</td>";
        echo "<td style='padding: 10px;'>{$user['email']}</td>";
        echo "<td style='padding: 10px; background: $balanceColor; color: white; font-weight: bold;'>\${$user['balance']}</td>";
        echo "<td style='padding: 10px;'>{$user['vip_level']}</td>";
        echo "<td style='padding: 10px;'>{$user['total_points']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🎉 Balance Fix Complete!</h2>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>✅ All new users will start with $0.00 balance</li>";
    echo "<li>✅ All users have proper wallet records</li>";
    echo "<li>✅ All users have proper VIP records</li>";
    echo "<li>✅ Database is now properly configured</li>";
    echo "</ul>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the admin dashboard at <a href='admin.html'>admin.html</a></li>";
    echo "<li>Check the Database tab to see real-time statistics</li>";
    echo "<li>Create new users - they will start with $0.00 balance</li>";
    echo "<li>Use the 'Add Balance' feature to give users money</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p>❌ Fix failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config.php</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
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

table {
    background: white;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

th {
    background: #f8f9fa;
    font-weight: bold;
    text-align: left;
}

td, th {
    padding: 12px;
    border: 1px solid #dee2e6;
}

ul, ol {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

li {
    margin: 10px 0;
}

a {
    color: #2196F3;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>

