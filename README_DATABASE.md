# Casino Website Database Integration

This project now includes a complete database backend for user management, VIP stats, and casino operations.

## 🗄️ Database Features

### User Management
- **User Authentication**: Secure login with password hashing
- **User Profiles**: Complete user information storage
- **Email Verification**: Built-in verification system
- **Password Reset**: Secure password recovery

### VIP System
- **6 VIP Levels**: Bronze → Silver → Gold → Platinum → Diamond → VIP Elite
- **Points System**: Wagering-based progression
- **Benefits**: Cashback, bonuses, exclusive features
- **Account Managers**: Personal support for top tiers

### Financial Management
- **Multi-Balance Wallet**: Main, bonus, and locked balances
- **Transaction History**: Complete audit trail
- **Deposit/Withdrawal**: Secure financial operations
- **Wagering Requirements**: Bonus system management

### Gaming Features
- **Game Sessions**: Track all casino game activity
- **Bet History**: Individual bet tracking and results
- **Statistics**: Comprehensive gaming analytics
- **Points Accumulation**: VIP progression system

## 🚀 Quick Setup

### Prerequisites
- **Web Server**: Apache/Nginx with PHP support
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **PHP**: Version 7.4+ with PDO extension

### 1. Database Setup
```bash
# Create MySQL database
mysql -u root -p
CREATE DATABASE casino_db;
exit;
```

### 2. Configuration
Edit `config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'casino_db');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
```

### 3. Run Setup Script
Visit `setup_database.php` in your browser to:
- Create all database tables
- Insert VIP levels
- Create sample admin user
- Create sample data

### 4. Test Admin Dashboard
- Go to `admin.html`
- Login with: `admin` / `admin123`
- Check User Management tab

## 📁 File Structure

```
casino-full/
├── database_schema.sql      # Complete database schema
├── admin_api.php            # PHP API backend
├── admin-database.js        # JavaScript database client
├── config.php               # Database configuration
├── setup_database.php       # Database setup script
├── admin.html               # Admin dashboard (updated)
└── README_DATABASE.md       # This file
```

## 🔧 Database Schema

### Core Tables
- **users**: User accounts and profiles
- **vip_levels**: VIP tier definitions
- **user_vip_stats**: User VIP progression
- **user_wallets**: Financial balances
- **transactions**: Financial transaction history
- **game_sessions**: Gaming activity tracking
- **game_bets**: Individual bet records
- **user_bonuses**: Bonus management
- **user_preferences**: User settings
- **admin_users**: Admin account management
- **user_activity_logs**: Activity monitoring

### Views
- **user_summary**: Complete user overview
- **vip_progression**: VIP level tracking

## 🎯 API Endpoints

### Dashboard Statistics
```
GET admin_api.php?action=get_stats
```

### User Management
```
GET admin_api.php?action=get_users&page=1&limit=20
GET admin_api.php?action=get_user_details&user_id=1
GET admin_api.php?action=search_users&q=username&limit=20
POST admin_api.php?action=update_user_status
POST admin_api.php?action=add_user_balance
```

### VIP & Gaming
```
GET admin_api.php?action=get_vip_stats
GET admin_api.php?action=get_game_stats
GET admin_api.php?action=get_transactions
GET admin_api.php?action=get_user_activity&user_id=1
```

## 💡 Usage Examples

### View All Users
```javascript
const users = await adminDB.getUsers(1, 20);
console.log(users.data); // Array of users
```

### Search Users
```javascript
const results = await adminDB.searchUsers('john', 10);
console.log(results); // Matching users
```

### Add Balance to User
```javascript
await adminDB.addUserBalance(userId, 100.00, 'Bonus credit');
```

### Get User Details
```javascript
const user = await adminDB.getUserDetails(userId);
console.log(user.vip_level, user.balance);
```

## 🛡️ Security Features

- **Password Hashing**: Bcrypt password encryption
- **SQL Injection Protection**: Prepared statements
- **Input Validation**: Server-side validation
- **Access Control**: Admin-only endpoints
- **Activity Logging**: Complete audit trail

## 🔄 Data Flow

1. **User Registration** → `users` table
2. **Login** → Session creation + activity log
3. **Gaming** → `game_sessions` + `game_bets` + points
4. **VIP Progression** → `user_vip_stats` updates
5. **Financial** → `transactions` + `user_wallets`
6. **Admin Actions** → Activity logging + notifications

## 📊 Sample Data

After running setup:
- **Admin User**: admin / admin123
- **Demo User**: demo_user / user123 ($1000 balance)
- **VIP Levels**: 6 tiers with benefits
- **Sample Transactions**: Initial deposits and gaming

## 🚨 Troubleshooting

### Database Connection Issues
- Check MySQL service is running
- Verify credentials in `config.php`
- Ensure database exists: `casino_db`

### Permission Errors
- Grant MySQL user proper privileges
- Check file permissions on PHP files
- Verify web server can execute PHP

### API Errors
- Check browser console for JavaScript errors
- Verify PHP error logging is enabled
- Test API endpoints directly in browser

## 🔮 Future Enhancements

- **Real-time Updates**: WebSocket integration
- **Advanced Analytics**: Reporting dashboard
- **Multi-currency**: Support for different currencies
- **API Rate Limiting**: Request throttling
- **Backup System**: Automated database backups
- **Audit Reports**: Compliance and security reports

## 📞 Support

For database issues:
1. Check error logs in browser console
2. Verify database connection in `config.php`
3. Run `setup_database.php` to reset database
4. Check MySQL error logs for detailed errors

## 📝 License

This database integration is part of the casino website project. Ensure compliance with local gambling regulations when deploying.

---

**Happy Gaming! 🎰✨**

