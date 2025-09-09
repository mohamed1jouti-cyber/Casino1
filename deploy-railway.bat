@echo off
echo ========================================
echo    Casino App - Railway Deployment
echo ========================================
echo.

echo Step 1: Installing dependencies...
npm install
if %errorlevel% neq 0 (
    echo ❌ Failed to install dependencies
    pause
    exit /b 1
)
echo ✅ Dependencies installed successfully
echo.

echo Step 2: Testing local server...
echo Starting local server for testing...
start /B node server.js
timeout /t 3 /nobreak >nul

echo Testing server response...
curl -s http://localhost:8000 >nul
if %errorlevel% neq 0 (
    echo ❌ Local server test failed
    taskkill /f /im node.exe >nul 2>&1
    pause
    exit /b 1
)
echo ✅ Local server test passed
taskkill /f /im node.exe >nul 2>&1
echo.

echo Step 3: Preparing for Railway deployment...
echo.
echo 🚀 Ready to deploy to Railway!
echo.
echo Next steps:
echo 1. Go to https://railway.app/
echo 2. Sign up with GitHub
echo 3. Create new project
echo 4. Choose "Deploy from GitHub repo"
echo 5. Select your casino app repository
echo 6. Add PostgreSQL database service
echo 7. Configure environment variables
echo.
echo Your app will be live at: https://your-app-name.railway.app
echo.
echo 📖 For detailed instructions, see: INTERNET_DEPLOYMENT_GUIDE.md
echo.
pause
