// Database Configuration for Casino App
// This file helps integrate real databases for internet deployment

const { Pool } = require('pg'); // For PostgreSQL
const fs = require('fs');
const path = require('path');

// Database configuration
const dbConfig = {
  // For Railway/Render PostgreSQL
  host: process.env.DB_HOST || 'localhost',
  port: process.env.DB_PORT || 5432,
  database: process.env.DB_NAME || 'casino_db',
  user: process.env.DB_USER || 'postgres',
  password: process.env.DB_PASSWORD || '',
  
  // Connection pool settings
  max: 20,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 2000,
};

// Create database pool
const pool = new Pool(dbConfig);

// Database initialization
async function initializeDatabase() {
  try {
    // Create users table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        balance DECIMAL(10,2) DEFAULT 1000.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create game_history table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS game_history (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        game_type VARCHAR(50) NOT NULL,
        bet_amount DECIMAL(10,2) NOT NULL,
        win_amount DECIMAL(10,2) NOT NULL,
        result VARCHAR(50) NOT NULL,
        played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create transactions table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS transactions (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        type VARCHAR(20) NOT NULL, -- 'deposit' or 'withdraw'
        amount DECIMAL(10,2) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP
      )
    `);

    console.log('✅ Database initialized successfully');
  } catch (error) {
    console.error('❌ Database initialization failed:', error);
    throw error;
  }
}

// User management functions
async function createUser(username, passwordHash) {
  try {
    const result = await pool.query(
      'INSERT INTO users (username, password_hash) VALUES ($1, $2) RETURNING id, username, balance',
      [username, passwordHash]
    );
    return result.rows[0];
  } catch (error) {
    if (error.code === '23505') { // Unique violation
      throw new Error('Username already exists');
    }
    throw error;
  }
}

async function getUserByUsername(username) {
  const result = await pool.query(
    'SELECT * FROM users WHERE username = $1',
    [username]
  );
  return result.rows[0];
}

async function updateUserBalance(userId, newBalance) {
  await pool.query(
    'UPDATE users SET balance = $1 WHERE id = $2',
    [newBalance, userId]
  );
}

async function addGameHistory(userId, gameType, betAmount, winAmount, result) {
  await pool.query(
    'INSERT INTO game_history (user_id, game_type, bet_amount, win_amount, result) VALUES ($1, $2, $3, $4, $5)',
    [userId, gameType, betAmount, winAmount, result]
  );
}

async function getGameHistory(userId, limit = 50) {
  const result = await pool.query(
    'SELECT * FROM game_history WHERE user_id = $1 ORDER BY played_at DESC LIMIT $2',
    [userId, limit]
  );
  return result.rows;
}

// Migration from file storage to database
async function migrateFromFileStorage() {
  try {
    const storagePath = path.join(__dirname, 'data', 'keys', 'storage.json');
    
    if (fs.existsSync(storagePath)) {
      const fileData = JSON.parse(fs.readFileSync(storagePath, 'utf8'));
      
      for (const [username, userData] of Object.entries(fileData.users || {})) {
        try {
          await createUser(username, userData.password || 'migrated');
          console.log(`✅ Migrated user: ${username}`);
        } catch (error) {
          console.log(`⚠️ User ${username} already exists or failed to migrate`);
        }
      }
      
      console.log('✅ Migration completed');
    }
  } catch (error) {
    console.error('❌ Migration failed:', error);
  }
}

// Export functions
module.exports = {
  pool,
  initializeDatabase,
  createUser,
  getUserByUsername,
  updateUserBalance,
  addGameHistory,
  getGameHistory,
  migrateFromFileStorage
};
