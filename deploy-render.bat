@echo off
echo ========================================
echo    Casino App - Render Deployment
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

echo Step 3: Preparing for Render deployment...
echo.
echo 🚀 Ready to deploy to Render!
echo.
echo Next steps:
echo 1. Go to https://render.com/
echo 2. Sign up with GitHub
echo 3. Create new Web Service
echo 4. Connect your GitHub repository
echo 5. Configure settings:
echo    - Name: casino-app
echo    - Build Command: npm install
echo    - Start Command: npm start
echo    - Environment: Node
echo 6. Create PostgreSQL database service
echo 7. Add environment variables
echo.
echo Your app will be live at: https://your-app-name.onrender.com
echo.
echo 📖 For detailed instructions, see: ALL_DEPLOYMENT_OPTIONS.md
echo.
pause
