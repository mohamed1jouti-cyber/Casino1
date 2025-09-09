@echo off
echo ========================================
echo    Casino App - Vercel + Supabase
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

echo Step 2: Installing Vercel CLI...
npm install -g vercel
if %errorlevel% neq 0 (
    echo ❌ Failed to install Vercel CLI
    pause
    exit /b 1
)
echo ✅ Vercel CLI installed successfully
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

echo Step 4: Preparing for deployment...
echo.
echo 🚀 Ready to deploy to Vercel + Supabase!
echo.
echo Next steps:
echo.
echo PART 1 - SUPABASE DATABASE:
echo 1. Go to https://supabase.com/
echo 2. Sign up and create new project
echo 3. Get database connection details from Settings ^> Database
echo 4. Note down: Host, Database, User, Password
echo.
echo PART 2 - VERCEL DEPLOYMENT:
echo 1. Run: vercel
echo 2. Follow the prompts to deploy
echo 3. In Vercel dashboard, go to Project Settings ^> Environment Variables
echo 4. Add these variables:
echo    DB_HOST=your-supabase-host
echo    DB_PORT=5432
echo    DB_NAME=postgres
echo    DB_USER=postgres
echo    DB_PASSWORD=your-supabase-password
echo.
echo Your app will be live at: https://your-app-name.vercel.app
echo.
echo 📖 For detailed instructions, see: ALL_DEPLOYMENT_OPTIONS.md
echo.
pause
