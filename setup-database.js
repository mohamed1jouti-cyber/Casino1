#!/usr/bin/env node

// Database Setup Script for Casino App
// This script initializes the database and migrates existing data

const { 
  initializeDatabase, 
  migrateFromFileStorage,
  pool 
} = require('./database-setup.js');

async function main() {
  console.log('🚀 Casino App Database Setup');
  console.log('=============================\n');

  try {
    // Step 1: Initialize database tables
    console.log('Step 1: Initializing database tables...');
    await initializeDatabase();
    console.log('✅ Database tables created successfully\n');

    // Step 2: Migrate existing data
    console.log('Step 2: Migrating existing user data...');
    await migrateFromFileStorage();
    console.log('✅ Data migration completed\n');

    // Step 3: Test database connection
    console.log('Step 3: Testing database connection...');
    const result = await pool.query('SELECT COUNT(*) as user_count FROM users');
    console.log(`✅ Database connection successful`);
    console.log(`📊 Found ${result.rows[0].user_count} users in database\n`);

    console.log('🎉 Database setup completed successfully!');
    console.log('\nYour casino app is now ready for internet deployment with:');
    console.log('✅ PostgreSQL database configured');
    console.log('✅ User accounts migrated');
    console.log('✅ Game history tracking enabled');
    console.log('✅ Transaction logging ready');
    console.log('\nNext steps:');
    console.log('1. Deploy to Railway/Render/Vercel');
    console.log('2. Configure environment variables');
    console.log('3. Your app will be live on the internet!');

  } catch (error) {
    console.error('❌ Database setup failed:', error.message);
    console.log('\nTroubleshooting:');
    console.log('1. Check your database connection settings');
    console.log('2. Verify environment variables are set');
    console.log('3. Ensure database server is running');
    process.exit(1);
  } finally {
    await pool.end();
  }
}

// Check if database connection is available
if (!process.env.DB_HOST && !process.env.DATABASE_URL) {
  console.log('⚠️  No database connection configured');
  console.log('This script will run when you deploy to a hosting platform');
  console.log('with database environment variables set.');
  console.log('\nFor local testing, set these environment variables:');
  console.log('DB_HOST=localhost');
  console.log('DB_PORT=5432');
  console.log('DB_NAME=casino_db');
  console.log('DB_USER=postgres');
  console.log('DB_PASSWORD=your_password');
  process.exit(0);
}

main();
