# 🚀 Casino App Deployment Guide

## Quick Deploy Options

### Option 1: Vercel (Recommended - Free)

1. **Install Vercel CLI**:
   ```bash
   npm install -g vercel
   ```

2. **Deploy**:
   ```bash
   vercel
   ```

3. **Follow the prompts**:
   - Link to existing project? → No
   - Project name → casino-app
   - Directory → ./
   - Override settings? → No

4. **Your app will be live at**: `https://your-app-name.vercel.app`

### Option 2: Railway (Alternative)

1. **Go to**: https://railway.app/
2. **Sign up with GitHub**
3. **Create new project** → Deploy from GitHub repo
4. **Connect your repository**
5. **Deploy automatically**

### Option 3: Render

1. **Go to**: https://render.com/
2. **Sign up and create new Web Service**
3. **Connect GitHub repository**
4. **Build Command**: `npm install`
5. **Start Command**: `npm start`

## 🎯 Before Deploying

### 1. Test Locally
```bash
node server.js
```
Visit: http://localhost:8000

### 2. Check Files
Make sure you have:
- ✅ `server.js`
- ✅ `vercel.json` (for Vercel)
- ✅ `package.json`
- ✅ All your HTML/CSS/JS files

### 3. Data Storage
- **Local**: Data stored in `data/keys/storage.json`
- **Production**: Consider using a database (MongoDB, PostgreSQL)

## 🌐 Production Considerations

### 1. Database (Recommended for Production)
For better data persistence, consider:
- **MongoDB Atlas** (free tier available)
- **PostgreSQL** (Railway/Render provide this)
- **Supabase** (free tier available)

### 2. Environment Variables
Add to your hosting platform:
```
NODE_ENV=production
PORT=8000
```

### 3. Custom Domain
- **Vercel**: Add custom domain in dashboard
- **Railway**: Configure in project settings
- **Render**: Add custom domain in service settings

## 🔧 Troubleshooting

### Common Issues:
1. **Port Issues**: Most platforms set their own PORT
2. **File Permissions**: Ensure server can write to data directory
3. **CORS**: Already configured in server.js
4. **HTTPS**: Automatically handled by hosting platforms

### Debug Commands:
```bash
# Check if server starts locally
node server.js

# Check Node.js version
node --version

# Test storage API
curl http://localhost:8000/storage_api.php?action=get_all
```

## 📊 Hosting Comparison

| Platform | Free Tier | Ease | Database | Custom Domain |
|----------|-----------|------|----------|---------------|
| **Vercel** | ✅ Yes | ⭐⭐⭐⭐⭐ | ❌ No | ✅ Yes |
| **Railway** | ✅ Yes | ⭐⭐⭐⭐ | ✅ Yes | ✅ Yes |
| **Render** | ✅ Yes | ⭐⭐⭐ | ✅ Yes | ✅ Yes |
| **Heroku** | ❌ No | ⭐⭐⭐ | ✅ Yes | ✅ Yes |

## 🎉 After Deployment

1. **Test your app**: Visit the provided URL
2. **Create test account**: Verify registration works
3. **Test from different devices**: Ensure cross-device sync works
4. **Monitor logs**: Check for any errors
5. **Share URL**: Your app is now live on the internet!

## 🔒 Security Notes

- Your app uses file-based storage (good for demo)
- For production, consider database storage
- HTTPS is automatically provided by hosting platforms
- CORS is configured for cross-origin requests

