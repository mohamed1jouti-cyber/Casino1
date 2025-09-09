@echo off
echo ========================================
echo    Casino App - Fly.io Deployment
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

echo Step 2: Installing Fly.io CLI...
powershell -Command "iwr https://fly.io/install.ps1 -useb | iex"
if %errorlevel% neq 0 (
    echo ❌ Failed to install Fly.io CLI
    echo Please install manually from: https://fly.io/docs/hands-on/install-flyctl/
    pause
    exit /b 1
)
echo ✅ Fly.io CLI installed successfully
echo.

echo Step 3: Testing local server...
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

echo Step 4: Preparing for Fly.io deployment...
echo.
echo 🚀 Ready to deploy to Fly.io!
echo.
echo Next steps:
echo 1. Sign up at https://fly.io/
echo 2. Run: fly auth login
echo 3. Run: fly launch
echo 4. Choose your app name
echo 5. Choose region
echo 6. Create PostgreSQL database: fly postgres create
echo 7. Attach database: fly postgres attach your-db-name
echo 8. Deploy: fly deploy
echo.
echo Your app will be live at: https://your-app-name.fly.dev
echo.
echo 📖 For detailed instructions, see: ALL_DEPLOYMENT_OPTIONS.md
echo.
pause
