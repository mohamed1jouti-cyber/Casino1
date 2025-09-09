# 🌐 Internet Deployment Guide for Casino App

## 🎯 Quick Start - Choose Your Platform

### **Option 1: Railway (Recommended)**
**Best for**: Complete solution with database
- ✅ Free tier available
- ✅ Built-in PostgreSQL database
- ✅ Automatic HTTPS
- ✅ Easy deployment

### **Option 2: Render**
**Best for**: Alternative with database
- ✅ Free tier available
- ✅ PostgreSQL database included
- ✅ Custom domains

### **Option 3: Vercel + Supabase**
**Best for**: Modern stack
- ✅ Vercel for hosting
- ✅ Supabase for database
- ✅ Excellent performance

---

## 🚀 Railway Deployment (Recommended)

### Step 1: Prepare Your Code
1. **Install dependencies**:
   ```bash
   npm install
   ```

2. **Test locally**:
   ```bash
   npm start
   ```
   Visit: http://localhost:8000

### Step 2: Deploy to Railway
1. **Go to**: https://railway.app/
2. **Sign up with GitHub**
3. **Create new project** → "Deploy from GitHub repo"
4. **Connect your repository**
5. **Add PostgreSQL database**:
   - Click "New" → "Database" → "PostgreSQL"
   - Railway will provide connection details automatically

### Step 3: Configure Environment Variables
In your Railway project dashboard, add these environment variables:
```
NODE_ENV=production
PORT=8000
DB_HOST=your-railway-db-host
DB_PORT=5432
DB_NAME=railway
DB_USER=postgres
DB_PASSWORD=your-railway-db-password
```

### Step 4: Deploy Web App
1. **Click "New"** → "GitHub Repo"
2. **Select your casino app repository**
3. **Railway will auto-detect Node.js**
4. **Set build command**: `npm install`
5. **Set start command**: `npm start`

### Step 5: Link Database to Web App
1. **In your web app service**
2. **Go to "Variables" tab**
3. **Add database connection variables** (Railway provides these automatically)

---

## 🚀 Render Deployment (Alternative)

### Step 1: Deploy to Render
1. **Go to**: https://render.com/
2. **Sign up and create new Web Service**
3. **Connect GitHub repository**
4. **Configure service**:
   - **Name**: casino-app
   - **Build Command**: `npm install`
   - **Start Command**: `npm start`
   - **Environment**: Node

### Step 2: Add PostgreSQL Database
1. **Create new "PostgreSQL" service**
2. **Note the connection details**
3. **Add environment variables** to your web service:
   ```
   DB_HOST=your-render-db-host
   DB_PORT=5432
   DB_NAME=your-db-name
   DB_USER=your-db-user
   DB_PASSWORD=your-db-password
   ```

---

## 🚀 Vercel + Supabase Deployment

### Step 1: Deploy to Vercel
```bash
npm install -g vercel
vercel
```

### Step 2: Set up Supabase Database
1. **Go to**: https://supabase.com/
2. **Create free account**
3. **Create new project**
4. **Get connection details** from Settings → Database

### Step 3: Configure Vercel Environment Variables
In Vercel dashboard → Your Project → Settings → Environment Variables:
```
DB_HOST=your-supabase-host
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
DB_PASSWORD=your-supabase-password
```

---

## 🔧 Database Integration

### Automatic Database Setup
Your app now includes automatic database initialization. The database will be set up automatically when you deploy.

### Manual Database Setup (if needed)
```bash
# Install PostgreSQL client
npm install pg

# Run database setup
npm run setup-db
```

### Database Schema
The app creates these tables automatically:
- **users**: User accounts and balances
- **game_history**: Game results and statistics
- **transactions**: Deposit/withdrawal records

---

## 🌐 Domain Setup

### Custom Domain (Optional)
1. **Railway**: Project → Settings → Domains
2. **Render**: Service → Settings → Custom Domains
3. **Vercel**: Project → Settings → Domains

### Free Subdomain
Your app will be available at:
- **Railway**: `https://your-app-name.railway.app`
- **Render**: `https://your-app-name.onrender.com`
- **Vercel**: `https://your-app-name.vercel.app`

---

## 🔒 Security & Environment Variables

### Required Environment Variables
```
NODE_ENV=production
PORT=8000
DB_HOST=your-database-host
DB_PORT=5432
DB_NAME=your-database-name
DB_USER=your-database-user
DB_PASSWORD=your-database-password
```

### Security Features
- ✅ HTTPS automatically enabled
- ✅ Environment variables for sensitive data
- ✅ Database connection pooling
- ✅ Input validation and sanitization

---

## 📊 Data Migration

### From File Storage to Database
Your existing user data can be migrated automatically:

1. **Deploy with database**
2. **Run migration script** (included in the app)
3. **All existing users will be preserved**

### Backup Your Data
Before deploying, backup your current data:
```bash
# Copy your current data
cp data/keys/storage.json backup-storage.json
```

---

## 🧪 Testing Your Deployment

### 1. Test Basic Functionality
- Visit your deployed URL
- Create a new account
- Test login functionality
- Play a game

### 2. Test Cross-Device Sync
- Open your app on different devices
- Login with the same account
- Verify data syncs between devices

### 3. Test Database
- Check if user data persists
- Verify game history is saved
- Test balance updates

---

## 🔧 Troubleshooting

### Common Issues:

1. **Database Connection Failed**
   - Check environment variables
   - Verify database is running
   - Check firewall settings

2. **App Won't Start**
   - Check build logs
   - Verify Node.js version
   - Check port configuration

3. **Data Not Persisting**
   - Verify database connection
   - Check database permissions
   - Run database setup script

### Debug Commands:
```bash
# Check environment variables
echo $DB_HOST
echo $DB_PASSWORD

# Test database connection
node -e "const { pool } = require('./database-setup.js'); pool.query('SELECT NOW()', (err, res) => { console.log(err || res.rows[0]); process.exit(); });"
```

---

## 📈 Performance Optimization

### Database Optimization
- Connection pooling enabled
- Indexed queries for fast lookups
- Efficient data structures

### Caching
- Static file caching
- Database query optimization
- CDN for global performance

---

## 🎉 Success Checklist

- ✅ App deployed to internet
- ✅ Database connected and working
- ✅ HTTPS enabled
- ✅ Custom domain configured (optional)
- ✅ Cross-device sync working
- ✅ User data persisting
- ✅ Game history saved
- ✅ Performance optimized

---

## 🆘 Support

### Platform Support:
- **Railway**: https://docs.railway.app/
- **Render**: https://render.com/docs
- **Vercel**: https://vercel.com/docs
- **Supabase**: https://supabase.com/docs

### Your App is Now Live! 🚀

Your casino app is now accessible worldwide with a real database that persists data between sessions and across devices!
