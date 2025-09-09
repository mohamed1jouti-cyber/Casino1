-- Casino Website Database Schema
-- This file contains all the necessary tables for user management, authentication, and VIP stats

-- Users table for authentication and basic user info
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    date_of_birth DATE,
    phone_number VARCHAR(20),
    country VARCHAR(100),
    city VARCHAR(100),
    address TEXT,
    postal_code VARCHAR(20),
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    email_verification_token VARCHAR(255),
    email_verification_expires TIMESTAMP NULL,
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP NULL
);

-- VIP levels and benefits
CREATE TABLE vip_levels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level_name VARCHAR(50) NOT NULL,
    min_points_required INT NOT NULL,
    cashback_percentage DECIMAL(5,2) DEFAULT 0.00,
    bonus_multiplier DECIMAL(3,2) DEFAULT 1.00,
    monthly_bonus DECIMAL(10,2) DEFAULT 0.00,
    withdrawal_limit DECIMAL(12,2) DEFAULT 0.00,
    priority_support BOOLEAN DEFAULT FALSE,
    exclusive_games_access BOOLEAN DEFAULT FALSE,
    personal_account_manager BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User VIP stats and progression
CREATE TABLE user_vip_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    vip_level_id INT NOT NULL,
    total_points INT DEFAULT 0,
    current_month_points INT DEFAULT 0,
    lifetime_wagered DECIMAL(12,2) DEFAULT 0.00,
    lifetime_won DECIMAL(12,2) DEFAULT 0.00,
    lifetime_lost DECIMAL(12,2) DEFAULT 0.00,
    current_month_wagered DECIMAL(12,2) DEFAULT 0.00,
    current_month_won DECIMAL(12,2) DEFAULT 0.00,
    current_month_lost DECIMAL(12,2) DEFAULT 0.00,
    vip_level_achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_points_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vip_level_id) REFERENCES vip_levels(id),
    UNIQUE KEY unique_user_vip (user_id)
);

-- User wallet and financial transactions
CREATE TABLE user_wallets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0.00,
    bonus_balance DECIMAL(12,2) DEFAULT 0.00,
    locked_balance DECIMAL(12,2) DEFAULT 0.00,
    total_deposited DECIMAL(12,2) DEFAULT 0.00,
    total_withdrawn DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_wallet (user_id)
);

-- Transaction history
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'bet', 'win', 'bonus', 'cashback', 'vip_bonus', 'refund') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    balance_before DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    reference_id VARCHAR(255),
    description TEXT,
    game_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Game sessions and betting history
CREATE TABLE game_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_type ENUM('slots', 'blackjack', 'roulette', 'poker', 'baccarat', 'craps', 'keno') NOT NULL,
    session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_end TIMESTAMP NULL,
    total_wagered DECIMAL(12,2) DEFAULT 0.00,
    total_won DECIMAL(12,2) DEFAULT 0.00,
    total_lost DECIMAL(12,2) DEFAULT 0.00,
    games_played INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Individual game bets
CREATE TABLE game_bets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_session_id INT NOT NULL,
    game_type ENUM('slots', 'blackjack', 'roulette', 'poker', 'baccarat', 'craps', 'keno') NOT NULL,
    bet_amount DECIMAL(10,2) NOT NULL,
    win_amount DECIMAL(10,2) DEFAULT 0.00,
    bet_details JSON,
    bet_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    result ENUM('win', 'loss', 'push', 'pending') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_session_id) REFERENCES game_sessions(id) ON DELETE CASCADE
);

-- User bonuses and promotions
CREATE TABLE user_bonuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bonus_type ENUM('welcome', 'deposit', 'reload', 'cashback', 'vip', 'loyalty', 'referral') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    wagering_requirement DECIMAL(10,2) DEFAULT 0.00,
    wagered_amount DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User preferences and settings
CREATE TABLE user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    language VARCHAR(10) DEFAULT 'en',
    currency VARCHAR(3) DEFAULT 'USD',
    timezone VARCHAR(50) DEFAULT 'UTC',
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE,
    push_notifications BOOLEAN DEFAULT TRUE,
    responsible_gambling_limits JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preferences (user_id)
);

-- Referral system
CREATE TABLE user_referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    referral_code VARCHAR(50) NOT NULL,
    status ENUM('pending', 'active', 'completed') DEFAULT 'pending',
    bonus_paid BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_referral (referred_id)
);

-- Admin users table
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator', 'support') NOT NULL,
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User activity logs
CREATE TABLE user_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default VIP levels
INSERT INTO vip_levels (level_name, min_points_required, cashback_percentage, bonus_multiplier, monthly_bonus, withdrawal_limit, priority_support, exclusive_games_access, personal_account_manager) VALUES
('Bronze', 0, 0.00, 1.00, 0.00, 1000.00, FALSE, FALSE, FALSE),
('Silver', 1000, 2.00, 1.05, 25.00, 2500.00, FALSE, FALSE, FALSE),
('Gold', 5000, 5.00, 1.10, 100.00, 5000.00, TRUE, FALSE, FALSE),
('Platinum', 15000, 8.00, 1.15, 250.00, 10000.00, TRUE, TRUE, FALSE),
('Diamond', 50000, 12.00, 1.25, 500.00, 25000.00, TRUE, TRUE, TRUE),
('VIP Elite', 100000, 15.00, 1.50, 1000.00, 50000.00, TRUE, TRUE, TRUE);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_user_vip_stats_user_id ON user_vip_stats(user_id);
CREATE INDEX idx_user_vip_stats_points ON user_vip_stats(total_points);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type);
CREATE INDEX idx_transactions_created_at ON transactions(created_at);
CREATE INDEX idx_game_sessions_user_id ON game_sessions(user_id);
CREATE INDEX idx_game_sessions_game_type ON game_sessions(game_type);
CREATE INDEX idx_game_bets_user_id ON game_bets(user_id);
CREATE INDEX idx_game_bets_game_type ON game_bets(game_type);
CREATE INDEX idx_user_bonuses_user_id ON user_bonuses(user_id);
CREATE INDEX idx_user_bonuses_type ON user_bonuses(bonus_type);
CREATE INDEX idx_user_activity_logs_user_id ON user_activity_logs(user_id);
CREATE INDEX idx_user_activity_logs_created_at ON user_activity_logs(created_at);

-- Create views for common queries
CREATE VIEW user_summary AS
SELECT 
    u.id,
    u.email,
    u.username,
    u.first_name,
    u.last_name,
    u.is_verified,
    u.is_active,
    u.created_at,
    u.last_login,
    v.vip_level_id,
    vl.level_name as vip_level,
    v.total_points,
    v.lifetime_wagered,
    v.lifetime_won,
    v.lifetime_lost,
    w.balance,
    w.bonus_balance,
    w.total_deposited,
    w.total_withdrawn
FROM users u
LEFT JOIN user_vip_stats v ON u.id = v.user_id
LEFT JOIN vip_levels vl ON v.vip_level_id = vl.id
LEFT JOIN user_wallets w ON u.id = w.user_id;

CREATE VIEW vip_progression AS
SELECT 
    u.username,
    v.total_points,
    vl.level_name as current_level,
    vl.min_points_required as current_level_requirement,
    (SELECT MIN(min_points_required) FROM vip_levels WHERE min_points_required > v.total_points) as next_level_requirement,
    (SELECT level_name FROM vip_levels WHERE min_points_required > v.total_points ORDER BY min_points_required ASC LIMIT 1) as next_level,
    (SELECT MIN(min_points_required) FROM vip_levels WHERE min_points_required > v.total_points) - v.total_points as points_needed_for_next_level
FROM users u
JOIN user_vip_stats v ON u.id = v.user_id
JOIN vip_levels vl ON v.vip_level_id = vl.id;

-- Comments for documentation
/*
This database schema provides a comprehensive foundation for a casino website with:

1. User Management:
   - Secure authentication with password hashing
   - Email verification and password reset functionality
   - User profile information and preferences

2. VIP System:
   - 6 VIP levels with increasing benefits
   - Points-based progression system
   - Cashback, bonuses, and exclusive features

3. Financial Management:
   - Wallet system with multiple balance types
   - Complete transaction history
   - Deposit and withdrawal tracking

4. Gaming Features:
   - Game session tracking
   - Bet history and results
   - Wagering requirements for bonuses

5. Bonus System:
   - Multiple bonus types
   - Wagering requirements tracking
   - Expiration management

6. Security & Monitoring:
   - Activity logging
   - IP address tracking
   - Admin user management

7. Performance:
   - Proper indexing on frequently queried columns
   - Views for common queries
   - Optimized table structure

To use this schema:
1. Create a MySQL/MariaDB database
2. Run this SQL file to create all tables and initial data
3. Update your application to use these table structures
4. Consider adding additional indexes based on your specific query patterns
*/

