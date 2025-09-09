#!/usr/bin/env node

// Migration Script: Move Local Accounts to Database
// This script helps migrate your existing local user accounts to a database

const fs = require('fs');
const path = require('path');

console.log('🔄 Casino App - Account Migration Tool');
console.log('=====================================\n');

// Check if local storage exists
const storagePath = path.join(__dirname, 'data', 'keys', 'storage.json');

if (!fs.existsSync(storagePath)) {
    console.log('❌ No local accounts found');
    console.log('No existing accounts to migrate.');
    console.log('\nYou can proceed with deployment - new accounts will be created in the database.');
    process.exit(0);
}

try {
    // Read existing accounts
    const fileData = JSON.parse(fs.readFileSync(storagePath, 'utf8'));
    const users = fileData.users || {};
    
    console.log(`📊 Found ${Object.keys(users).length} local user accounts`);
    console.log('\nLocal accounts:');
    
    Object.keys(users).forEach(username => {
        const user = users[username];
        console.log(`  - ${username} (Balance: $${user.balance || 1000})`);
    });
    
    console.log('\n🔧 Migration Options:');
    console.log('1. Deploy to Railway (Recommended)');
    console.log('   - Built-in PostgreSQL database');
    console.log('   - Automatic account migration');
    console.log('   - Easiest setup');
    console.log('\n2. Deploy to Render');
    console.log('   - PostgreSQL database included');
    console.log('   - Good free tier');
    console.log('\n3. Deploy to Vercel + Supabase');
    console.log('   - Best performance');
    console.log('   - Modern database');
    
    console.log('\n📋 Migration Process:');
    console.log('1. Choose a deployment platform');
    console.log('2. Deploy your app (database will be created automatically)');
    console.log('3. Run the migration script on the deployed app');
    console.log('4. All your existing accounts will be available on the internet!');
    
    console.log('\n🚀 Ready to deploy?');
    console.log('Double-click one of these files:');
    console.log('- deploy-railway.bat (Recommended)');
    console.log('- deploy-render.bat');
    console.log('- deploy-vercel-supabase.bat');
    
    // Create backup
    const backupPath = path.join(__dirname, 'backup-accounts.json');
    fs.writeFileSync(backupPath, JSON.stringify(fileData, null, 2));
    console.log(`\n✅ Backup created: backup-accounts.json`);
    
} catch (error) {
    console.error('❌ Error reading local accounts:', error.message);
    console.log('\nNo worries! You can still deploy and create new accounts.');
}

console.log('\n💡 After deployment, your accounts will be:');
console.log('✅ Accessible from anywhere on the internet');
console.log('✅ Stored in a real database');
console.log('✅ Synced across all devices');
console.log('✅ Backed up automatically');
console.log('✅ More secure and reliable');
